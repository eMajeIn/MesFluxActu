<?php
/**
 * Contrôle de tous les articles du corpus.
 *
 *   php outils/valider.php
 *
 * Code de sortie 0 si tout est conforme, 1 sinon.
 * L'agent doit lancer ce script avant chaque commit.
 *
 * Trois régimes, selon le champ « type » :
 *   actu         brève de veille, 1500 caractères minimum
 *   explication  fonctionnalité expliquée, 5000 caractères, sections et code
 *   methode      billet sur le journal, 1200 caractères, labo = autre
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');

const TYPES_AUTORISES   = ['actu', 'explication', 'methode'];
const LABOS_REELS       = ['anthropic', 'openai', 'mistral', 'gemini'];
const LABOS_AUTORISES   = ['anthropic', 'openai', 'mistral', 'gemini', 'autre'];
const STATUTS_AUTORISES = ['brouillon', 'publie'];
const CHAMPS_CONNUS     = [
    'slug', 'titre', 'date', 'maj', 'type', 'labo', 'fonctionnalite',
    'chapeau', 'corps', 'tags', 'sources', 'statut',
];

// Longueur minimale du corps, en caractères, par type.
const CORPS_MIN = [
    'actu'        => 1500,
    'explication' => 5000,
    'methode'     => 1200,
];

// Domaines de documentation attendus selon l'acteur traité.
const DOCS_ATTENDUES = [
    'anthropic' => ['platform.claude.com', 'docs.claude.com', 'code.claude.com', 'anthropic.com'],
    'openai'    => ['platform.openai.com', 'developers.openai.com', 'openai.com'],
    'mistral'   => ['docs.mistral.ai', 'mistral.ai', 'huggingface.co'],
    'gemini'    => ['ai.google.dev', 'deepmind.google', 'blog.google', 'developers.googleblog.com'],
];

$dossier = dirname(__DIR__) . '/content/posts';

$erreurs   = [];
$alertes   = [];
$slugsVus  = [];
$titresVus = [];
$sujetsVus = [];   // "labo|fonctionnalite" => fichier
$parJour   = [];   // "AAAA-MM-JJ|labo"      => fichier, pour les explications
$urlsVues  = [];   // url source            => fichier, pour les actus

$fichiers = glob($dossier . '/*.json') ?: [];

if ($fichiers === []) {
    fwrite(STDERR, "Aucun article dans content/posts/.\n");
    exit(1);
}

foreach ($fichiers as $chemin) {
    $nom = basename($chemin);

    $ajout = static function (string $message) use (&$erreurs, $nom): void {
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

    foreach (array_keys($a) as $cle) {
        if (!in_array($cle, CHAMPS_CONNUS, true)) {
            $ajout("champ inconnu « {$cle} »");
        }
    }

    foreach (['slug', 'titre', 'date', 'labo', 'chapeau', 'corps', 'sources', 'statut'] as $requis) {
        if (!isset($a[$requis]) || $a[$requis] === '' || $a[$requis] === []) {
            $ajout("champ obligatoire manquant : {$requis}");
        }
    }

    if (!isset($a['slug'], $a['titre'], $a['date'], $a['labo'])) {
        continue;
    }

    $labo = (string) $a['labo'];

    // --- Type : déduit si absent, pour les articles écrits avant son ajout
    if (!isset($a['type'])) {
        $type = $labo === 'autre' ? 'methode' : 'actu';
        $alerte("champ type absent, « {$type} » supposé — ajoutez-le explicitement");
    } else {
        $type = (string) $a['type'];
        if (!in_array($type, TYPES_AUTORISES, true)) {
            $ajout('type inconnu, valeurs admises : ' . implode(', ', TYPES_AUTORISES));
            continue;
        }
    }

    // --- Cohérence type / labo -------------------------------------------
    if (!in_array($labo, LABOS_AUTORISES, true)) {
        $ajout('labo inconnu, valeurs admises : ' . implode(', ', LABOS_AUTORISES));
    }
    if ($type === 'methode' && $labo !== 'autre') {
        $ajout('un article de type methode doit porter labo = autre');
    }
    if ($type !== 'methode' && $labo === 'autre') {
        $ajout("labo = autre est réservé au type methode");
    }
    if (!in_array($a['statut'] ?? '', STATUTS_AUTORISES, true)) {
        $ajout('statut inconnu, valeurs admises : ' . implode(', ', STATUTS_AUTORISES));
    }

    // --- Slug -------------------------------------------------------------
    $slug = (string) $a['slug'];
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

    if ($type === 'explication' && !str_starts_with($slug, $labo . '-')) {
        $alerte("le slug devrait commencer par « {$labo}- »");
    }
    if (!str_contains($nom, $slug)) {
        $alerte('le nom de fichier devrait contenir le slug');
    }

    // --- Titre ------------------------------------------------------------
    $titre = (string) $a['titre'];
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
    $horodatage = strtotime((string) $a['date']);
    if ($horodatage === false) {
        $ajout('date illisible, format attendu : 2026-07-27T08:00:00+02:00');
        $horodatage = 0;
    } elseif ($horodatage > time() + 86400) {
        $ajout('date située dans le futur');
    }
    if (isset($a['maj']) && strtotime((string) $a['maj']) === false) {
        $ajout('champ maj illisible');
    }

    if ($type === 'explication' && $horodatage > 0) {
        $cleJour = date('Y-m-d', $horodatage) . '|' . $labo;
        if (isset($parJour[$cleJour])) {
            $ajout("deuxième explication {$labo} datée du même jour que {$parJour[$cleJour]}");
        }
        $parJour[$cleJour] = $nom;
    }

    // --- Fonctionnalité : clé anti-doublon des explications ---------------
    if ($type === 'explication') {
        $fonction = trim((string) ($a['fonctionnalite'] ?? ''));
        if ($fonction === '') {
            $ajout('champ fonctionnalite obligatoire pour une explication');
        } else {
            if (mb_strlen($fonction) < 3 || mb_strlen($fonction) > 60) {
                $ajout('fonctionnalite : entre 3 et 60 caractères');
            }
            if ($fonction !== mb_strtolower($fonction)) {
                $ajout('fonctionnalite : minuscules uniquement');
            }
            $cleSujet = $labo . '|' . mb_strtolower($fonction);
            if (isset($sujetsVus[$cleSujet])) {
                $ajout("sujet déjà traité pour {$labo} dans {$sujetsVus[$cleSujet]} « {$fonction} »");
            }
            $sujetsVus[$cleSujet] = $nom;
        }
    } elseif (isset($a['fonctionnalite'])) {
        $ajout('champ fonctionnalite réservé au type explication');
    }

    // --- Chapeau ----------------------------------------------------------
    $longC = mb_strlen((string) ($a['chapeau'] ?? ''));
    if ($longC < 80 || $longC > 260) {
        $ajout("chapeau de {$longC} caractères (attendu entre 80 et 260)");
    }

    // --- Corps ------------------------------------------------------------
    $corps = (string) ($a['corps'] ?? '');
    $seuil = CORPS_MIN[$type];
    $longB = mb_strlen($corps);
    if ($longB < $seuil) {
        $ajout("corps de {$longB} caractères, minimum {$seuil} pour le type {$type}");
    }

    // Les blocs de code sont retirés AVANT le contrôle du HTML brut :
    // un exemple peut légitimement contenir des balises.
    $nbDelim  = preg_match_all('/^```/m', $corps);
    if (($nbDelim % 2) !== 0) {
        $ajout('bloc de code non refermé (nombre impair de ```)');
    }
    $horsCode = preg_replace('/```.*?```/s', '', $corps) ?? $corps;

    if ($type === 'explication') {
        if ($nbDelim < 2) {
            $ajout('explication sans bloc de code : au moins un exemple est obligatoire');
        }
        $nbSections = preg_match_all('/^##\s+\S/m', $corps);
        if ($nbSections < 3) {
            $ajout("seulement {$nbSections} section(s) en ##, la charte en demande quatre");
        }
    }

    if (preg_match('/<\s*(script|iframe|style|div|span)\b/i', $horsCode)) {
        $ajout('HTML brut hors bloc de code, seul le Markdown est accepté');
    }
    if (preg_match('/^#\s/m', $corps)) {
        $ajout('titre de niveau 1 dans le corps, commencer à ##');
    }

    // --- Tags -------------------------------------------------------------
    $tags = (array) ($a['tags'] ?? []);
    if (count($tags) < 1 || count($tags) > 5) {
        $ajout('entre 1 et 5 étiquettes attendues');
    }
    foreach ($tags as $tag) {
        if (!is_string($tag) || $tag !== mb_strtolower($tag)) {
            $ajout('étiquette « ' . (string) $tag . ' » : minuscules uniquement');
        }
    }

    // --- Sources ----------------------------------------------------------
    $sources = (array) ($a['sources'] ?? []);
    if (count($sources) < 2) {
        $ajout('deux sources minimum');
    }

    $docTrouvee = false;
    foreach ($sources as $i => $source) {
        $rang = $i + 1;
        if (!is_array($source) || empty($source['url']) || empty($source['titre'])) {
            $ajout("source {$rang} : titre et url obligatoires");
            continue;
        }
        $url = (string) $source['url'];
        if (!str_starts_with($url, 'https://')) {
            $ajout("source {$rang} : l'URL doit commencer par https://");
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $ajout("source {$rang} : URL malformée");
        }

        // En actu, réutiliser la source primaire d'une actu existante
        // signale presque toujours un doublon.
        if ($type === 'actu') {
            if (isset($urlsVues[$url])) {
                $alerte("source déjà citée dans {$urlsVues[$url]} — vérifiez qu'il ne s'agit pas d'un doublon");
            }
            $urlsVues[$url] = $nom;
        }

        $hote = (string) (parse_url($url, PHP_URL_HOST) ?: '');
        foreach (DOCS_ATTENDUES[$labo] ?? [] as $attendu) {
            if (str_ends_with($hote, $attendu)) {
                $docTrouvee = true;
            }
        }
    }
    if ($type === 'explication' && !$docTrouvee) {
        $alerte("aucune source sur un domaine officiel de {$labo}");
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
