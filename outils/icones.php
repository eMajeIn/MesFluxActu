<?php
/**
 * Génération des icônes de l'application.
 *
 *     php outils/icones.php
 *
 * Écrit public/assets/icone-192.png, icone-512.png et
 * icone-maskable-512.png. À relancer seulement si le dessin change : les
 * fichiers produits sont versionnés, la production ne lance jamais ce script.
 *
 * Le motif est une une de journal — manchette, filet, puis le rail
 * chronologique et son marqueur carré, comme sur le site.
 *
 * Tout est dessiné avec GD, sans dépendance. Le tracé se fait sur une toile
 * quatre fois plus grande puis réduite : c'est ce qui donne des bords lisses,
 * GD ne sachant pas lisser une forme pleine.
 */

declare(strict_types=1);

const FACTEUR = 4;      // sur-échantillonnage
const REPERE  = 512;    // toutes les coordonnées ci-dessous sont dans ce repère

$COULEURS = [
    'encre'  => [0x15, 0x17, 0x1D],
    'papier' => [0xEC, 0xEE, 0xF1],
    'rail'   => [0xC9, 0xCE, 0xD6],
    'doux'   => [0x5C, 0x63, 0x73],
    'accent' => [0x2F, 0x27, 0xC4],
];

/**
 * Le dessin, du fond vers le détail. Chaque entrée :
 * [x, y, largeur, hauteur, rayon, couleur].
 */
const DESSIN = [
    // la page
    [ 96,  76, 320, 360, 10, 'papier'],
    // la manchette et son filet
    [128, 112, 256,  44,  0, 'encre'],
    [128, 174, 256,   6,  0, 'doux'],
    // le marqueur et le rail
    [128, 212,  30,  30,  0, 'accent'],
    [178, 212,   8, 176,  0, 'rail'],
    // les lignes de l'article
    [210, 214, 174,  20,  4, 'doux'],
    [210, 258, 130,  16,  4, 'rail'],
    [210, 298, 174,  16,  4, 'rail'],
    [210, 338, 104,  16,  4, 'rail'],
];

// Le contenu tient dans un carré centré de 320 × 360 : réduit à 80 %, il
// reste dans le cercle de sécurité des icônes maskables.
const ECHELLE_MASQUE = 0.8;


/** Rectangle à coins arrondis, en coordonnées déjà mises à l'échelle. */
function rectangle(\GdImage $im, float $x, float $y, float $l, float $h, float $r, int $couleur): void
{
    $x1 = (int) round($x);
    $y1 = (int) round($y);
    $x2 = (int) round($x + $l) - 1;
    $y2 = (int) round($y + $h) - 1;
    $r  = (int) round(min($r, $l / 2, $h / 2));

    if ($r <= 0) {
        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $couleur);
        return;
    }

    imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $couleur);
    imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $couleur);

    $d = $r * 2;
    foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
        imagefilledellipse($im, $cx, $cy, $d, $d, $couleur);
    }
}

/**
 * @param bool $maskable Fond plein et contenu resserré, pour Android.
 */
function icone(int $taille, bool $maskable = false): \GdImage
{
    global $COULEURS;

    $grand = $taille * FACTEUR;
    $im    = imagecreatetruecolor($grand, $grand);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $palette = [];
    foreach ($COULEURS as $nom => [$r, $v, $b]) {
        $palette[$nom] = imagecolorallocate($im, $r, $v, $b);
    }
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);

    $k = $grand / REPERE;   // du repère de dessin vers les pixels

    // Le fond : arrondi pour l'icône ordinaire, plein pour la maskable —
    // c'est le système qui découpe alors la forme qu'il veut.
    rectangle($im, 0, 0, $grand, $grand, $maskable ? 0 : 114 * $k, $palette['encre']);

    $echelle = $maskable ? ECHELLE_MASQUE : 1.0;
    $centre  = REPERE / 2;

    foreach (DESSIN as [$x, $y, $l, $h, $r, $couleur]) {
        rectangle(
            $im,
            ($centre + ($x - $centre) * $echelle) * $k,
            ($centre + ($y - $centre) * $echelle) * $k,
            $l * $echelle * $k,
            $h * $echelle * $k,
            $r * $echelle * $k,
            $palette[$couleur]
        );
    }

    // Réduction : c'est elle qui lisse les bords.
    $final = imagecreatetruecolor($taille, $taille);
    imagealphablending($final, false);
    imagesavealpha($final, true);
    imagecopyresampled($final, $im, 0, 0, 0, 0, $taille, $taille, $grand, $grand);

    return $final;
}


// ---------------------------------------------------------------------

if (!extension_loaded('gd')) {
    fwrite(STDERR, "L'extension GD est nécessaire pour régénérer les icônes.\n");
    exit(1);
}

$assets = dirname(__DIR__) . '/public/assets';

$fichiers = [
    'icone-192.png'          => icone(192),
    'icone-512.png'          => icone(512),
    'icone-maskable-512.png' => icone(512, true),
];

foreach ($fichiers as $nom => $image) {
    imagepng($image, $assets . '/' . $nom, 9);
    echo '  écrit  ', $nom, "\n";
}

echo count($fichiers), " icône(s) générée(s).\n";
