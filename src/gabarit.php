<?php
/**
 * Gabarit commun à toutes les pages + petits utilitaires d'affichage.
 */

/** Échappement court, utilisé partout dans les vues. */
function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/** Date longue en français, sans dépendre de l'extension intl. */
function date_longue(int $horodatage): string
{
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];
    return date('j', $horodatage) . ' ' . $mois[(int) date('n', $horodatage)] . ' ' . date('Y', $horodatage);
}

/** Libellé et couleur d'un laboratoire, avec repli si la clé est inconnue. */
function labo_infos(string $cle): array
{
    global $CONFIG;
    return $CONFIG['labos'][$cle] ?? [ucfirst($cle), '#6B7280'];
}

function url_site(string $chemin = ''): string
{
    global $CONFIG;
    return rtrim($CONFIG['url'], '/') . $chemin;
}

function entete(string $titrePage, string $description = '', string $cheminCanonique = '/'): void
{
    global $CONFIG;
    $titreComplet = $titrePage !== ''
        ? $titrePage . ' — ' . $CONFIG['titre']
        : $CONFIG['titre'];
    $description = $description !== '' ? $description : $CONFIG['description'];
    ?><!DOCTYPE html>
<html lang="<?= e($CONFIG['langue']) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titreComplet) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e(url_site($cheminCanonique)) ?>">
<meta property="og:title" content="<?= e($titreComplet) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<link rel="alternate" type="application/rss+xml" title="<?= e($CONFIG['titre']) ?>" href="<?= e(url_site('/rss.xml')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=IBM+Plex+Mono:wght@400;500&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;1,8..60,400&display=swap">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="evitement" href="#contenu">Aller au contenu</a>

<header class="bandeau">
  <div class="bandeau__interieur">
    <a class="bandeau__titre" href="/"><?= e($CONFIG['titre']) ?></a>
    <p class="bandeau__accroche"><?= e($CONFIG['accroche']) ?></p>
  </div>
</header>

<main id="contenu" class="page">
<?php
}

function pied(): void
{
    global $CONFIG;
    ?>
</main>

<footer class="pied">
  <div class="pied__interieur">
    <p class="pied__mention">
      <?= e($CONFIG['titre']) ?> — veille tenue par <?= e($CONFIG['auteur']) ?>.
      Les articles sont rédigés par un agent et relus avant publication.
    </p>
    <p class="pied__liens">
      <a href="/rss.xml">Flux RSS</a>
    </p>
  </div>
</footer>
</body>
</html>
<?php
}
