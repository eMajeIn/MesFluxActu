<?php
/**
 * Service worker, généré par PHP.
 *
 * La version du cache est calculée à partir de la date de modification
 * des fichiers qui composent la coquille du site. Aucun numéro à
 * incrémenter à la main : modifier style.css suffit à invalider les
 * anciens caches chez tous les visiteurs.
 *
 * Servi à l'adresse /sw.js par une règle de réécriture, afin que sa
 * portée couvre tout le site.
 */

const ACCES_LIBRE = true;
require dirname(__DIR__) . '/src/amorce.php';

$surveilles = [
    __DIR__ . '/assets/style.css',
    __DIR__ . '/assets/icone-192.png',
    __DIR__ . '/assets/icone-512.png',
    __DIR__ . '/assets/icone-maskable-512.png',
    __DIR__ . '/manifest.php',
    __DIR__ . '/sw.php',
    dirname(__DIR__) . '/src/gabarit.php',
    dirname(__DIR__) . '/src/config.php',
];

$empreinte = '';
foreach ($surveilles as $fichier) {
    $empreinte .= is_file($fichier) ? (string) filemtime($fichier) : '0';
    $empreinte .= '|';
}
$version = substr(hash('sha256', $empreinte), 0, 12);

header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Service-Worker-Allowed: /');
?>
// Généré automatiquement — ne pas modifier à la main.
const VERSION   = '<?= $version ?>';
const COQUILLE  = `coquille-${VERSION}`;
const PAGES     = `pages-${VERSION}`;
const EXTERNES  = `externes-${VERSION}`;

// Ressources chargées d'avance, indispensables au fonctionnement hors ligne.
const PRECACHE = [
  '/assets/style.css',
  '/assets/icone-192.png',
  '/assets/icone-512.png',
  '/manifest.webmanifest',
  '/hors-ligne'
];

// Jamais mis en cache : contenu qui doit toujours être frais ou privé.
const JAMAIS = [/^\/rss\.xml$/, /^\/acces/];

self.addEventListener('install', (evenement) => {
  evenement.waitUntil(
    caches.open(COQUILLE)
      .then((cache) => cache.addAll(PRECACHE))
      // Une ressource absente ne doit pas faire échouer l'installation.
      .catch(() => undefined)
  );
});

self.addEventListener('activate', (evenement) => {
  evenement.waitUntil((async () => {
    const noms = await caches.keys();
    await Promise.all(
      noms
        .filter((nom) => !nom.endsWith(VERSION))
        .map((nom) => caches.delete(nom))
    );
    await self.clients.claim();
  })());
});

// Permet à la page de demander l'activation immédiate d'une mise à jour.
self.addEventListener('message', (evenement) => {
  if (evenement.data === 'ACTIVER_MAINTENANT') {
    self.skipWaiting();
  }
});

/** Une réponse redirigée ou partielle ne doit jamais entrer en cache. */
function stockable(reponse) {
  return reponse
    && reponse.ok
    && reponse.status === 200
    && !reponse.redirected
    && reponse.type !== 'opaqueredirect';
}

async function reseauDabord(requete, nomCache) {
  const cache = await caches.open(nomCache);
  try {
    const reponse = await fetch(requete);
    if (stockable(reponse)) {
      cache.put(requete, reponse.clone());
    }
    return reponse;
  } catch (erreur) {
    const enCache = await cache.match(requete);
    if (enCache) return enCache;
    const secours = await caches.match('/hors-ligne');
    if (secours) return secours;
    throw erreur;
  }
}

async function cacheDabord(requete, nomCache) {
  const cache   = await caches.open(nomCache);
  const enCache = await cache.match(requete);
  if (enCache) return enCache;

  const reponse = await fetch(requete);
  if (stockable(reponse)) {
    cache.put(requete, reponse.clone());
  }
  return reponse;
}

/** Sert le cache immédiatement et rafraîchit en arrière-plan. */
async function cacheEtRafraichir(requete, nomCache) {
  const cache   = await caches.open(nomCache);
  const enCache = await cache.match(requete);

  const reseau = fetch(requete)
    .then((reponse) => {
      if (stockable(reponse)) cache.put(requete, reponse.clone());
      return reponse;
    })
    .catch(() => enCache);

  return enCache || reseau;
}

self.addEventListener('fetch', (evenement) => {
  const requete = evenement.request;

  if (requete.method !== 'GET') return;

  const url = new URL(requete.url);

  // Polices Google : rarement modifiées, coûteuses à retélécharger.
  if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
    evenement.respondWith(cacheEtRafraichir(requete, EXTERNES));
    return;
  }

  if (url.origin !== self.location.origin) return;

  if (JAMAIS.some((motif) => motif.test(url.pathname))) return;

  // Fichiers statiques : le nom du cache change à chaque version,
  // donc le cache d'abord ne sert jamais une ressource périmée.
  if (url.pathname.startsWith('/assets/') || url.pathname === '/manifest.webmanifest') {
    evenement.respondWith(cacheDabord(requete, COQUILLE));
    return;
  }

  // Pages : toujours le réseau en premier, pour voir les nouveaux articles.
  if (requete.mode === 'navigate') {
    evenement.respondWith(reseauDabord(requete, PAGES));
  }
});
