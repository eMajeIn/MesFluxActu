<?php
/**
 * Configuration du site.
 *
 * Ce fichier est versionné et peut être public : il ne doit contenir
 * AUCUN secret.
 *
 * Tout ce qui est sensible — code PIN, jeton, identifiant, clé d'un
 * service tiers — se place dans un fichier séparé, situé hors du dépôt :
 *
 *     MesFluxActu_Interne/config-prive.php
 *
 * Ce fichier privé retourne un tableau dont les clés écrasent celles
 * ci-dessous. Il n'est jamais committé, jamais déployé, jamais visible.
 * Un modèle est fourni dans deploiement/config-prive.exemple.php
 */

$config = [
    // Titre affiché dans le bandeau et les flux
    'titre'       => 'Delta',

    // Sous-titre : la thèse du site, une phrase
    'accroche'    => 'Ce qui change vraiment chez Anthropic, OpenAI, Mistral et Google DeepMind.',

    // URL publique complète, sans slash final
    'url'         => 'https://exemple.fr',

    'description' => 'Veille indépendante sur les modèles de langage : nouveautés, capacités réelles, ce que ça change en pratique.',

    'langue'      => 'fr',
    'fuseau'      => 'Europe/Paris',
    'auteur'      => 'Votre nom',

    // Nombre d'articles en page d'accueil
    'par_page'    => 20,

    // Laboratoires suivis : clé technique => [libellé, couleur du marqueur]
    'labos'       => [
        'anthropic' => ['Anthropic',  '#8A6A4F'],
        'openai'    => ['OpenAI',     '#1F7A6B'],
        'mistral'   => ['Mistral',    '#C2622A'],
        'gemini'    => ['Gemini',     '#3B5BDB'],
        'autre'     => ['Écosystème', '#6B7280'],
    ],
];


// ---------------------------------------------------------------------
//  Fusion avec la configuration privée, si elle existe.
//
//  Emplacement attendu : dossier voisin du dépôt, donc hors de tout ce
//  que Git déploie et hors de la racine web.
//
//      /home/login/MesFluxActu/            <- le dépôt, public
//      /home/login/MesFluxActu_Interne/    <- les secrets, jamais servis
//
//  La variable d'environnement MESFLUXACTU_PRIVE permet de pointer
//  ailleurs si besoin.
// ---------------------------------------------------------------------

$racine = dirname(__DIR__);

$candidats = array_filter([
    getenv('MESFLUXACTU_PRIVE') ?: null,
    dirname($racine) . '/MesFluxActu_Interne/config-prive.php',
]);

foreach ($candidats as $chemin) {
    if (is_string($chemin) && is_readable($chemin)) {
        $prive = require $chemin;
        if (is_array($prive)) {
            $config = array_replace_recursive($config, $prive);
        }
        break;
    }
}

return $config;
