<?php
/**
 * Amorce : à inclure en première ligne de chaque page publique.
 * Définit $CONFIG dans la portée globale et charge le moteur.
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');

$CONFIG = require __DIR__ . '/config.php';

date_default_timezone_set($CONFIG['fuseau']);

require_once __DIR__ . '/Contenu.php';
require_once __DIR__ . '/Markdown.php';
require_once __DIR__ . '/gabarit.php';
