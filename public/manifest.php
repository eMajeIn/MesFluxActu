<?php
const ACCES_LIBRE = true;
require dirname(__DIR__) . '/src/amorce.php';

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo json_encode([
    'name'             => $CONFIG['titre'],
    'short_name'       => $CONFIG['titre'],
    'description'      => $CONFIG['description'],
    'lang'             => $CONFIG['langue'],
    'dir'              => 'ltr',
    'start_url'        => '/',
    'scope'            => '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait-primary',
    'background_color' => '#ECEEF1',
    'theme_color'      => '#15171D',
    'categories'       => ['news', 'productivity'],
    'icons'            => [
        ['src' => '/assets/icone-192.png',           'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/icone-512.png',           'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/icone-maskable-512.png',  'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
