<?php
/**
 * Configuration du site. Seul fichier à modifier après le clone.
 */

return [
    // Titre affiché dans le bandeau et les flux
    'titre'       => 'Delta',

    // Sous-titre : la thèse du site, une phrase
    'accroche'    => 'Ce qui change vraiment chez Anthropic, OpenAI, Mistral et Google DeepMind.',

    // URL publique complète, sans slash final. Sert au flux RSS et aux balises canoniques.
    'url'         => 'https://exemple.fr',

    // Description pour les moteurs et le flux
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
