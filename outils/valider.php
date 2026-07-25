<?php
/**
 * Contrôle de tous les articles du corpus.
 *
 *   php outils/valider.php
 *
 * Code de sortie 0 si tout est conforme, 1 sinon.
 * L'agent doit lancer ce script avant chaque commit.
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');

const LABOS_AUTORISES  = ['anthropic', 'openai', 'mistral', 'gemini', 'autre'];
const STATUTS_AUTORISES = ['brouillon', 'publie'];
const CHAMPS_CONNUS = [
    'slug', 'titre', 'date', 'maj', 'labo', 'chapeau', 'corps', 'tags', 'sources', 'statut',
];

$racine  = dirname(__DIR__);
$dossier = $racine . '/content/posts';

$erreurs = [];
$alertes = [];
$slugsVus  = [];
$titresVus = [];

$fichiers = glob($dossier . '/*.json') ?: [];

if ($fichiers === []) {
    fwrite(STDERR, "Aucun article dans content/posts/.\n");
    exit(1);
}

foreach ($fichiers as $chemin) {
    $nom = basename($chemin);
    $ajout  = static function (string $message) use (&$erreurs, $nom): void {
        $erreurs[] = $nom . ' : ' . $message;
    };
    $alerte = static function (string $message) use (&$alertes, $nom): void {
        $alertes[] = $nom . ' : ' . $message;
    };

    $brut = file_get_contents($chemin);
    if ($brut === false) {
        $ajout('fichier illisible');
        continue;
    }

    $a = json_decode($brut, true);
    if (!is_array($a)) {
        $ajout('JSON invalide (' . json_last_error_msg() . ')');
        continue;
    }

    // --- Champs inconnus ------------------------------------------------
    foreach (array_keys($a) as $cle) {
        if (!in_array($cle, CHAMPS_CONNUS, true)) {
            $ajout("champ inconnu « {$cle} »");
        }
    }

    // --- Champs obligatoires --------------------------------------------
    foreach (['slug', 'titre', 'date', 'labo', 'chapeau', 'corps', 'sources', 'statut'] as $requis) {
        if (!isset($a[$requis]) || $a[$requis] === '' || $a[$requis] === []) {
            $ajout("champ obligatoire manquant : {$requis}");
        }
    }
    // Sans ces trois champs, les contrôles suivants n'ont plus de sens.
    if (!isset($a['slug'], $a['titre'], $a['date'])) {
        continue;
    }

    // --- Slug -------------------------------------------------------------
    $slug = (string) ($a['slug'] ?? '');
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
        $ajout("slug non conforme « {$slug} » (minuscules, chiffres, tirets)");
    }
    if (mb_strlen($slug) > 80) {
        $ajout('slug trop long (80 caractères maximum)');
    }
    if (isset($slugsVus[$slug])) {
        $ajout("slug déjà utilisé par {$slugsVus[$slug]}");
    }
    $slugsVus[$slug] = $nom;

    if (!str_contains($nom, $slug)) {
        $alerte("le nom de fichier devrait contenir le slug (convention AAAA-MM-JJ-{$slug}.json)");
    }

    // --- Titre ------------------------------------------------------------
    $titre = (string) ($a['titre'] ?? '');
    $long  = mb_strlen($titre);
    if ($long < 15 || $long > 95) {
        $ajout("titre de {$long} caractères (attendu entre 15 et 95)");
    }
    if (str_contains($titre, '!')) {
        $ajout('le titre ne doit pas contenir de point d\'exclamation');
    }
    $normalise = mb_strtolower(trim($titre));
    if (isset($titresVus[$normalise])) {
        $ajout("titre identique à celui de {$titresVus[$normalise]}");
    }
    $titresVus[$normalise] = $nom;

    // --- Dates ------------------------------------------------------------
    $horodatage = strtotime((string) ($a['date'] ?? ''));
    if ($horodatage === false) {
        $ajout('date illisible, format attendu : 2026-07-25T08:00:00+02:00');
    } elseif ($horodatage > time() + 86400) {
        $ajout('date située dans le futur');
    }
    if (isset($a['maj']) && strtotime((string) $a['maj']) === false) {
        $ajout('champ maj illisible');
    }

    // --- Laboratoire et statut -------------------------------------------
    if (!in_array($a['labo'] ?? '', LABOS_AUTORISES, true)) {
        $ajout('labo inconnu, valeurs admises : ' . implode(', ', LABOS_AUTORISES));
    }
    if (!in_array($a['statut'] ?? '', STATUTS_AUTORISES, true)) {
        $ajout('statut inconnu, valeurs admises : ' . implode(', ', STATUTS_AUTORISES));
    }

    // --- Chapeau ----------------------------------------------------------
    $chapeau = (string) ($a['chapeau'] ?? '');
    $longC   = mb_strlen($chapeau);
    if ($longC < 80 || $longC > 260) {
        $ajout("chapeau de {$longC} caractères (attendu entre 80 et 260)");
    }

    // --- Corps ------------------------------------------------------------
    $corps = (string) ($a['corps'] ?? '');
    if (mb_strlen($corps) < 1200) {
        $ajout('corps trop court (1200 caractères minimum)');
    }
    if (preg_match('/<\s*(script|iframe|style|img|div|span|a)\b/i', $corps)) {
        $ajout('le corps contient du HTML brut, seul le Markdown est accepté');
    }
    if (preg_match('/^#\s/m', $corps)) {
        $ajout('le corps utilise un titre de niveau 1, commencer à ##');
    }
    // Un chiffre non sourcé est le principal risque d'erreur : on le signale.
    if (preg_match('/\b\d{2,}\s*(%|Md|milliards?|millions?)\b/iu', $corps)
        && count((array) ($a['sources'] ?? [])) < 2) {
        $alerte('chiffres présents dans le corps mais moins de deux sources');
    }

    // --- Tags -------------------------------------------------------------
    $tags = (array) ($a['tags'] ?? []);
    if (count($tags) < 1 || count($tags) > 5) {
        $ajout('entre 1 et 5 étiquettes attendues');
    }
    foreach ($tags as $tag) {
        if (!is_string($tag) || $tag !== mb_strtolower($tag)) {
            $ajout("étiquette « " . (string) $tag . " » : minuscules uniquement");
        }
    }

    // --- Sources ----------------------------------------------------------
    $sources = (array) ($a['sources'] ?? []);
    if (count($sources) < 2) {
        $ajout('deux sources minimum, dont une primaire');
    }
    foreach ($sources as $i => $source) {
        $rang = $i + 1;
        if (!is_array($source) || empty($source['url']) || empty($source['titre'])) {
            $ajout("source {$rang} : titre et url obligatoires");
            continue;
        }
        if (!str_starts_with((string) $source['url'], 'https://')) {
            $ajout("source {$rang} : l'URL doit commencer par https://");
        }
        if (filter_var($source['url'], FILTER_VALIDATE_URL) === false) {
            $ajout("source {$rang} : URL malformée");
        }
    }
}

// --- Restitution ----------------------------------------------------------

$total = count($fichiers);

foreach ($alertes as $ligne) {
    fwrite(STDOUT, "  avertissement  {$ligne}\n");
}

if ($erreurs !== []) {
    fwrite(STDERR, "\n" . count($erreurs) . " erreur(s) sur {$total} article(s) :\n\n");
    foreach ($erreurs as $ligne) {
        fwrite(STDERR, "  refus  {$ligne}\n");
    }
    fwrite(STDERR, "\nRien n'a été committé. Corrigez puis relancez la validation.\n");
    exit(1);
}

fwrite(STDOUT, "{$total} article(s) validé(s).\n");
exit(0);
