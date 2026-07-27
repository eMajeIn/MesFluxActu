# Charte du journal

> **Aiguillage.** Ce fichier définit comment sont **rédigés les articles**.
> Il s'adresse aux routines de veille.
>
> Si tu es ici pour travailler sur **le code** — moteur PHP, gabarits, CSS,
> validateur, déploiement — arrête-toi et lis **`PROJET.md`** à la place.
> Ne rédige aucun article et ne modifie pas `content/posts/`.

Ce journal publie **deux formats d'articles**. Ils cohabitent, ils ne se
remplacent pas.

| Format | Champ `type` | Ce que c'est | Rythme |
|---|---|---|---|
| **Actu** | `actu` | Ce qui vient de changer chez un acteur. Court, factuel, daté. | quotidien, 1 article maximum |
| **Explication** | `explication` | Une fonctionnalité expliquée à fond, avec exemples. | hebdomadaire, jusqu'à 4 articles |
| **Méthode** | `methode` | Billet sur le journal lui-même. Rare, écrit à la main. | à la demande |

Le prompt de la routine te dit lequel tu écris aujourd'hui. **Ne produis jamais
les deux formats dans la même exécution.**

Quatre acteurs sont suivis : **Anthropic, OpenAI, Mistral, Google DeepMind
(Gemini)**.

Le lecteur type est un développeur ou un praticien francophone. En actu, il veut
savoir ce qui a changé. En explication, il veut savoir s'en servir.

---

## 1. Avant toute chose, dans les deux formats

Lire `content/posts/*.json`. Construire trois listes :

- les valeurs du champ `fonctionnalite`, groupées par `labo` — ce qui est **déjà
  expliqué** et ne doit jamais être réexpliqué ;
- toutes les URL des champs `sources` — ce qui a **déjà servi de matière** ;
- les dates et titres récents, pour ne pas répéter un sujet sous un autre nom.

Règle absolue, valable partout : **si tu n'as pas ouvert la page, tu ne l'écris
pas.** Ne jamais citer une URL sans l'avoir consultée pendant cette exécution.

Si une source primaire renvoie une erreur, signale-le dans le compte rendu en
précisant si l'en-tête `x-deny-reason: host_not_allowed` est présent — cela
distingue un blocage du proxy réseau d'un refus du site.

---

## 2. Format ACTU

### Procédure

1. Relever la date de l'article `actu` le plus récent : c'est le début de la
   fenêtre à couvrir.
2. Consulter les sources primaires des quatre acteurs (section 4).
3. Établir une liste de candidats, la passer au filtre ci-dessous.
4. **Si aucun candidat ne passe : ne rien publier.** Terminer la session en
   expliquant ce qui a été consulté. C'est une issue normale et fréquente.
5. Sinon, retenir **le meilleur candidat, un seul**, vérifier chaque fait sur la
   source primaire, rédiger, valider, committer.

Une exécution en mode actu produit **zéro ou un article**. Jamais deux.

### Filtre de sélection

Retenu si le fait est établi par une source primaire et qu'il coche au moins un
point :

- un modèle est publié, mis à jour, déprécié ou retiré ;
- une capacité nouvelle apparaît dans la documentation officielle ;
- une limite technique change : contexte, modalités, latence, quotas, tarifs ;
- une publication de recherche établit un résultat vérifiable ;
- une décision réglementaire ou contractuelle modifie ce qui est permis.

Écarté si : rumeur, capture d'écran, fil de discussion sans source ; sites
d'actualité qui se citent entre eux ; annonce d'intention sans documentation ;
classement sans protocole publié ; **ou si une URL de la source primaire figure
déjà dans un article existant.**

**Sujet déjà couvert qui évolue :** ne pas créer un second article. Modifier
l'existant, ajouter une section datée à la fin du `corps`, renseigner `maj`, et
ajouter la nouvelle source. Le `slug` et la `date` d'origine ne changent jamais.

### Écriture

600 à 900 mots. Une accroche de deux ou trois paragraphes qui pose le fait, puis
deux à trois sections en `##`. La dernière répond à : *qu'est-ce que ça change en
pratique.*

Pas de champ `fonctionnalite`. Pas de bloc de code obligatoire.

---

## 3. Format EXPLICATION

### Procédure

Traiter les quatre acteurs un par un, dans cet ordre : Anthropic, OpenAI,
Mistral, Gemini. Pour chacun :

1. consulter ses sources primaires ;
2. repérer les fonctionnalités documentées absentes de la liste des
   `fonctionnalite` déjà traitées **pour ce labo** ;
3. retenir la plus substantielle, celle sur laquelle la documentation permet
   d'écrire un article complet avec exemples ;
4. si aucune ne convient, **passer à l'acteur suivant sans rien écrire**.

Une exécution en mode explication produit **zéro à quatre** articles. Trois est
un résultat normal. Ne jamais compléter à quatre en dégradant un sujet.

### Ce qui fait un bon sujet

La documentation officielle doit permettre de répondre aux **cinq questions**.
Si l'une reste sans réponse, le sujet n'est pas mûr :

1. Qu'est-ce que ça fait, en une phrase ?
2. Comment on l'active ou on l'appelle, concrètement ?
3. À quoi ça sert dans un cas réel ?
4. Qu'est-ce que ça coûte — jetons, latence, complexité ?
5. Où sont les limites, les cas non couverts, les pièges ?

**La nouveauté n'est pas requise.** Une fonctionnalité disponible depuis des mois
mais jamais expliquée ici est un excellent sujet. Ce format comble des lacunes,
il ne court pas après le calendrier — c'est ce qui le distingue de l'actu.

### Structure

Toujours la même charpente, quatre sections en `##` :

1. **Ce que c'est** — sans jargon non expliqué.
2. **Comment ça marche** — mécanisme, paramètres, valeurs possibles.
3. **En pratique** — les exemples (section 6). La section la plus longue.
4. **Ce que ça change, et où ça s'arrête** — apport réel, coût, limites
   documentées, cas où il ne faut pas s'en servir.

900 à 1400 mots. Champ `fonctionnalite` obligatoire.

---

## 4. Sources

**Primaires.**

| Acteur | À consulter |
|---|---|
| Anthropic | `platform.claude.com/docs`, `docs.claude.com`, `anthropic.com/news`, `code.claude.com/docs` |
| OpenAI | `platform.openai.com/docs`, `developers.openai.com`, `openai.com/news` |
| Mistral | `docs.mistral.ai`, `mistral.ai/news`, `huggingface.co/mistralai` |
| Google DeepMind | `ai.google.dev/gemini-api/docs`, `deepmind.google/discover/blog`, `blog.google/technology/google-deepmind` |
| Transverse | `arxiv.org` (cs.CL, cs.LG), fiches de modèle, registres officiels |

En **actu**, le billet d'annonce fait foi pour la date de l'événement.
En **explication**, la **documentation technique prime** : un billet dit qu'une
chose existe, la documentation dit comment elle marche.

**Secondaires** — pour corroborer uniquement, jamais pour établir un fait.

**Jamais :** agrégateurs de contenu généré, réseaux sociaux sans lien officiel,
sites sans auteur identifiable.

---

## 5. Écriture, règles communes

**Langue.** Français. Les termes techniques anglais restent en anglais quand la
traduction n'existe pas, mais sont expliqués à leur première apparition.

**Précision.** Nommer les versions exactes. Distinguer « annoncé », « en accès
limité » et « disponible pour tous ». Donner les dates. Les chiffres proviennent
d'une source citée, jamais d'une estimation.

**Incertitude.** Quand la documentation ne dit rien, l'écrire : « la
documentation ne précise pas ». Une zone d'ombre signalée vaut mieux qu'une
affirmation confortable.

**Interdits de vocabulaire.** révolutionnaire · game changer · incroyable ·
bluffant · la course à l'IA · à la vitesse de l'éclair · plus fort que jamais ·
ce n'est que le début · l'avenir de. Pas de point d'exclamation. Pas de question
rhétorique en ouverture. Pas de conclusion qui résume ce qui vient d'être dit.

**Neutralité.** Décrire ce que fait un produit, pas ce qu'il vaut. Aucun acteur
n'est favorisé, y compris Anthropic. Les limites sont mentionnées au même titre
que les capacités.

**Markdown.** `##`, `###`, `**gras**`, `*italique*`, listes, `> citation`, blocs
de code. Aucun titre de niveau `#`. Aucun HTML brut hors des blocs de code.

**Reprise de texte.** Ne jamais recopier plus de quinze mots consécutifs de prose
issue d'une source. Reformuler intégralement.

---

## 6. Les exemples — format explication uniquement

Chaque article explicatif contient **au moins un bloc de code et au moins un cas
d'usage décrit en français**. Les deux, systématiquement.

**Le bloc de code** — appel d'API, extrait de configuration, prompt, requête
`curl`. Encadré par des triples accents graves, avec la langue indiquée.

> **Règle la plus importante de ce fichier.** Un exemple de code ne s'invente
> pas. Tous les noms de paramètres, de champs, de valeurs et de points d'entrée
> doivent apparaître dans la documentation ouverte pendant cette exécution. Si tu
> ne l'as pas sous les yeux, tu n'écris pas l'exemple — tu changes de sujet.
>
> Un exemple de code faux est pire qu'une absence d'exemple : le lecteur le
> copie, il échoue, et il ne revient pas.

Si tu adaptes un exemple officiel, garde les noms techniques exacts et reformule
les commentaires en français.

**Le cas d'usage** — deux à quatre phrases décrivant une situation concrète : qui,
quel problème, ce que la fonctionnalité apporte. Pas de généralité du type « utile
pour améliorer la productivité ». Une situation nommée.

---

## 7. Format du fichier

Un article = un fichier JSON dans `content/posts/`, en UTF-8.

- actu : `AAAA-MM-JJ-slug.json`
- explication : `AAAA-MM-JJ-labo-slug.json`

Schéma complet et commenté dans `schema/post.schema.json`. Résumé :

```json
{
  "slug": "mistral-appel-outils-parallele",
  "titre": "Mistral : appeler plusieurs outils dans un même tour",
  "date": "2026-07-27T08:00:00+02:00",
  "type": "explication",
  "labo": "mistral",
  "fonctionnalite": "appel d'outils en parallèle",
  "chapeau": "Deux phrases : ce dont il s'agit, et pour qui ça compte.",
  "corps": "Le texte en Markdown, échappé pour JSON.",
  "tags": ["outils", "api"],
  "sources": [
    { "titre": "Documentation Mistral — Function calling", "url": "https://…", "consultee": "2026-07-27" }
  ],
  "statut": "brouillon"
}
```

Points de vigilance :

- `type` vaut `actu`, `explication` ou `methode` ;
- `labo` vaut `anthropic`, `openai`, `mistral`, `gemini`, ou `autre` pour une
  méthode ;
- `fonctionnalite` : obligatoire en explication, interdit ailleurs. Nom court et
  canonique, en minuscules. **Deux articles du même labo ne peuvent jamais porter
  la même `fonctionnalite`** ;
- `slug` en minuscules, chiffres et tirets, préfixé du labo en explication ;
- `titre` entre 15 et 95 caractères ;
- `chapeau` entre 80 et 260 caractères, compréhensible seul dans un flux RSS ;
- deux sources minimum, en `https://` ;
- dans le `corps`, les sauts de ligne s'écrivent `\n`, les guillemets `\"`.

**`statut` vaut `brouillon` par défaut.** Ne jamais publier directement, sauf si
`PUBLICATION_DIRECTE=oui` figure en section 10.

---

## 8. Validation et livraison

Avant tout commit :

```
php outils/valider.php
```

Le script sort en code 1 à la moindre non-conformité. **Un échec de validation
interdit le commit.** Corriger et relancer jusqu'à obtenir un code 0. Ne jamais
contourner le validateur, ne jamais l'assouplir pour faire passer un article.

Ensuite, **un commit unique** :

- actu : `actu : <titre court>` — ou `mise à jour : <slug>` pour une révision
- explication : `explications : anthropic, openai, mistral` — la liste des labos

Le commit ne contient que des fichiers d'articles. Ne jamais modifier `src/`,
`public/`, `outils/` ou ce fichier de ta propre initiative : si le moteur te
semble défectueux, le signaler en fin de session au lieu de le corriger.

**Compte rendu final**, obligatoire. En actu : sources consultées, candidats,
motif de chaque rejet, décision. En explication : acteur par acteur, sources
ouvertes, fonctionnalité retenue ou motif de l'abandon.

---

## 9. Erreurs à ne pas commettre

- **Inventer un paramètre d'API.** L'erreur la plus grave de ce journal.
  Dans le doute, cite la documentation ou abandonne le sujet.
- Mélanger les deux formats dans une même exécution.
- Publier une actu pour « ne pas revenir les mains vides ». Le silence est permis.
- Écrire quatre explications à tout prix. Trois bonnes valent mieux que quatre
  tièdes.
- Réexpliquer une fonctionnalité déjà traitée pour le même labo sous un autre nom.
- Rédiger la section « En pratique » sans exemple de code, ou avec un exemple
  décoratif qui ne fait rien.
- Reformuler une annonce officielle sans rien y ajouter. En actu, il faut un
  angle : une conséquence, une limite, une comparaison avec l'état antérieur.
- Confondre deux versions d'un modèle, ou attribuer une fonctionnalité au mauvais
  produit.
- Traiter une date de publication d'article comme la date de l'événement.
- Écrire au futur. On raconte ce qui est, pas ce qui arrivera.

---

## 10. Réglages du propriétaire

```
PUBLICATION_DIRECTE=oui
```

Ce réglage est à `oui` : **tout article que tu crées porte
`"statut": "publie"`.** Il paraît sur le site dès que le dépôt est déployé, sans
relecture préalable. N'écris jamais `brouillon` de ta propre initiative.

En contrepartie, la barre de qualité monte. Avant de committer, vérifie une
dernière fois :

- chaque nom de paramètre des blocs de code figure dans une documentation que tu
  as ouverte pendant cette exécution ;
- chaque chiffre est attribuable à une source citée ;
- aucune affirmation ne repose sur un souvenir plutôt que sur une page consultée.

Au moindre doute sur un fait, retire la phrase concernée. Un article plus court
est préférable à un article faux.

Pour revenir à la relecture manuelle, repasser la valeur à `non` : les articles
naîtront alors en `brouillon` et resteront invisibles jusqu'à modification à la
main du champ `statut`.
