<?php
/**
 * Amorce : à inclure en première ligne de chaque page publique.
 * Définit $CONFIG dans la portée globale, charge le moteur, puis
 * applique le verrou d'accès si un code PIN est configuré.
 *
 * Une page qui doit rester joignable sans code — service worker,
 * manifeste, page de saisie du PIN, page hors ligne — déclare
 * `const ACCES_LIBRE = true;` AVANT d'inclure ce fichier.
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');

$CONFIG = require __DIR__ . '/config.php';

date_default_timezone_set($CONFIG['fuseau']);

require_once __DIR__ . '/Contenu.php';
require_once __DIR__ . '/Markdown.php';
require_once __DIR__ . '/acces.php';
require_once __DIR__ . '/gabarit.php';

acces_exiger();
