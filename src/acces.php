<?php
/**
 * Verrou d'accès par code PIN.
 *
 * Le verrou ne s'active que si la configuration privée définit
 * « pin_empreinte ». Sans elle, le site reste entièrement public et ce
 * fichier ne fait rien.
 *
 * Choix techniques :
 *   - le code n'est jamais stocké en clair, seulement son empreinte bcrypt ;
 *   - la vérification passe par password_verify, à temps constant ;
 *   - la session est un cookie signé par HMAC, sans fichier côté serveur :
 *     il survit au ramasse-miettes de PHP, ce qui permet une durée longue ;
 *   - les tentatives sont comptées par adresse IP, avec blocage temporaire.
 */

declare(strict_types=1);

const ACCES_COOKIE = 'mfa_acces';
const ACCES_JETON  = 'mfa_jeton';

function acces_reglages(): array
{
    global $CONFIG;
    return [
        'actif'      => !empty($CONFIG['pin_empreinte']),
        'empreinte'  => (string) ($CONFIG['pin_empreinte'] ?? ''),
        'duree'      => (int)    ($CONFIG['pin_duree_session'] ?? 2592000), // 30 jours
        'essais_max' => (int)    ($CONFIG['pin_essais_max'] ?? 8),
        'fenetre'    => (int)    ($CONFIG['pin_fenetre'] ?? 900),           // 15 minutes
        'donnees'    => (string) ($CONFIG['pin_dossier_donnees'] ?? sys_get_temp_dir()),
        'rss_public' => !empty($CONFIG['rss_public']),
        // 0 = longueur libre : le clavier propose alors une touche de validation.
        'longueur'   => max(0, (int) ($CONFIG['pin_longueur'] ?? 4)),
    ];
}

function acces_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function acces_poser_cookie(string $nom, string $valeur, int $duree): void
{
    setcookie($nom, $valeur, [
        'expires'  => $duree > 0 ? time() + $duree : 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => acces_https(),
    ]);
}

/** Signature d'une échéance, avec l'empreinte du PIN comme clé secrète. */
function acces_signer(int $echeance): string
{
    $r = acces_reglages();
    return $echeance . '.' . hash_hmac('sha256', (string) $echeance, $r['empreinte']);
}

function acces_ouvert(): bool
{
    $r = acces_reglages();
    if (!$r['actif']) {
        return true;
    }

    $brut = (string) ($_COOKIE[ACCES_COOKIE] ?? '');
    if ($brut === '' || !str_contains($brut, '.')) {
        return false;
    }

    [$echeance, $signature] = explode('.', $brut, 2);
    if (!ctype_digit($echeance)) {
        return false;
    }
    if ((int) $echeance < time()) {
        return false;
    }

    $attendue = hash_hmac('sha256', $echeance, $r['empreinte']);
    return hash_equals($attendue, $signature);
}

function acces_ouvrir(): void
{
    $r        = acces_reglages();
    $echeance = time() + $r['duree'];
    acces_poser_cookie(ACCES_COOKIE, acces_signer($echeance), $r['duree']);
}

function acces_fermer(): void
{
    acces_poser_cookie(ACCES_COOKIE, '', -3600);
}

// ---------------------------------------------------------------------
//  Limitation des tentatives
// ---------------------------------------------------------------------

function acces_fichier_essais(): ?string
{
    $r      = acces_reglages();
    $dossier = rtrim($r['donnees'], '/');

    if (!is_dir($dossier) && !@mkdir($dossier, 0700, true) && !is_dir($dossier)) {
        return null; // pas de dossier inscriptible : on renonce au comptage
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'inconnue');
    return $dossier . '/essais-' . sha1($ip) . '.txt';
}

/**
 * @return array{bloque: bool, restant: int, secondes: int}
 */
function acces_etat_essais(): array
{
    $r       = acces_reglages();
    $fichier = acces_fichier_essais();
    $defaut  = ['bloque' => false, 'restant' => $r['essais_max'], 'secondes' => 0];

    if ($fichier === null || !is_file($fichier)) {
        return $defaut;
    }

    $contenu = @file_get_contents($fichier);
    if ($contenu === false || !str_contains($contenu, '|')) {
        return $defaut;
    }

    [$nombre, $debut] = array_map('intval', explode('|', trim($contenu), 2));

    if (time() - $debut > $r['fenetre']) {
        @unlink($fichier);
        return $defaut;
    }

    $restant = max(0, $r['essais_max'] - $nombre);
    return [
        'bloque'   => $restant === 0,
        'restant'  => $restant,
        'secondes' => max(0, $debut + $r['fenetre'] - time()),
    ];
}

function acces_compter_echec(): void
{
    $r       = acces_reglages();
    $fichier = acces_fichier_essais();
    if ($fichier === null) {
        return;
    }

    $nombre = 1;
    $debut  = time();

    if (is_file($fichier)) {
        $contenu = @file_get_contents($fichier);
        if ($contenu !== false && str_contains($contenu, '|')) {
            [$n, $d] = array_map('intval', explode('|', trim($contenu), 2));
            if (time() - $d <= $r['fenetre']) {
                $nombre = $n + 1;
                $debut  = $d;
            }
        }
    }

    @file_put_contents($fichier, $nombre . '|' . $debut, LOCK_EX);
}

function acces_effacer_essais(): void
{
    $fichier = acces_fichier_essais();
    if ($fichier !== null && is_file($fichier)) {
        @unlink($fichier);
    }
}

// ---------------------------------------------------------------------
//  Jeton anti-rejeu du formulaire
// ---------------------------------------------------------------------

function acces_jeton_emettre(): string
{
    $jeton = bin2hex(random_bytes(16));
    acces_poser_cookie(ACCES_JETON, $jeton, 3600);
    return $jeton;
}

function acces_jeton_valide(string $soumis): bool
{
    $attendu = (string) ($_COOKIE[ACCES_JETON] ?? '');
    return $attendu !== '' && hash_equals($attendu, $soumis);
}

// ---------------------------------------------------------------------
//  Point d'entrée appelé par amorce.php
// ---------------------------------------------------------------------

function acces_exiger(): void
{
    $r = acces_reglages();

    if (!$r['actif'] || defined('ACCES_LIBRE') || acces_ouvert()) {
        return;
    }

    // Le flux peut rester ouvert si la configuration le demande.
    if ($r['rss_public'] && basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'rss.php') {
        return;
    }

    $cible = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: /acces?r=' . rawurlencode($cible), true, 302);
    exit;
}

/** N'accepte qu'un chemin interne, jamais une URL absolue. */
function acces_cible_sure(string $cible): string
{
    if ($cible === '' || $cible[0] !== '/' || str_starts_with($cible, '//')) {
        return '/';
    }
    if (str_starts_with($cible, '/acces')) {
        return '/';
    }
    return $cible;
}
