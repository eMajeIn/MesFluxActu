# Delta — journal de veille alimenté par une routine Claude Code

Un blog sans base de données et sans étape de compilation. Un article est un
fichier JSON ; le PHP le lit à la volée. Une routine Claude Code s'exécute dans le
cloud d'Anthropic, rédige, valide et pousse. L'hébergement OVH récupère par `git pull`.

```
routine Claude Code  →  dépôt GitHub  →  git pull horaire  →  mutualisé OVH
     (rédaction)        (fichiers JSON)      (cron)            (rendu PHP)
```

---

## Prérequis

- Un hébergement mutualisé OVH **Pro ou Performance** — SSH et Git ne sont pas
  disponibles sur l'offre Perso.
- PHP 8.1 ou plus récent (8.3 est configuré dans `public/.ovhconfig`).
- Un dépôt GitHub, de préférence privé.
- Un abonnement Claude payant. Les routines cloud sont disponibles sur Pro, Max,
  Team et Enterprise, et restent en aperçu de recherche.

---

## Installation

### 1. Le dépôt

Poussez ce dossier sur GitHub, puis ajustez `src/config.php` : `url`, `titre`,
`accroche`, `auteur`. Commitez ces changements — le serveur ne fait que suivre.

### 2. La clé de déploiement

Sur l'hébergement OVH, en SSH :

```sh
ssh-keygen -t ed25519 -C "ovh-blog" -f ~/.ssh/id_blog -N ""
cat ~/.ssh/id_blog.pub
```

Collez la clé publique dans GitHub → dépôt → *Settings* → *Deploy keys*, en
lecture seule. Puis déclarez-la :

```sh
printf 'Host github.com\n  IdentityFile ~/.ssh/id_blog\n  IdentitiesOnly yes\n' >> ~/.ssh/config
```

### 3. Le clone

```sh
git clone git@github.com:VOUS/blog-ia.git ~/blog-ia
chmod +x ~/blog-ia/deploiement/ovh-pull.sh
```

### 4. La racine du site

Dans l'espace client OVH → *Hébergements* → *Multisite*, faites pointer le domaine
sur le dossier **`blog-ia/public`**. C'est ce qui garde `content/`, `src/` et
`outils/` hors de portée du web.

### 5. Le cron

Espace client OVH → *Hébergements* → *Tâches planifiées* → *Ajouter*.

| Champ | Valeur |
|---|---|
| Commande | `/home/VOTRE_LOGIN/blog-ia/deploiement/ovh-pull.sh` |
| Langage | Autre |
| Fréquence | Toutes les heures |

Le script écrit dans `~/blog-ia-deploiement.log` et se protège des exécutions
concurrentes par un verrou.

---

## La routine Claude Code

Rendez-vous sur **claude.ai/code/routines** → *New routine* → **Cloud**
(ou tapez `/schedule` dans la CLI Claude Code, ou passez par *Routines* dans
l'application de bureau).

| Réglage | Valeur |
|---|---|
| Dépôt | votre dépôt GitHub |
| Déclencheur | Scheduled — quotidien, le matin |
| Environnement | accès réseau autorisé vers les domaines de la section 4 de `CLAUDE.md` |

L'intervalle minimum est d'une heure et le déclenchement peut décaler de quelques
minutes. Pour une cadence sur mesure, créez la routine puis affinez avec
`/schedule update` dans la CLI.

### Le prompt

Court par construction : la charte est dans le dépôt, l'agent la lit à chaque
exécution.

```
Tiens le journal décrit dans CLAUDE.md, à la lettre.

Applique la procédure de la section 2 dans l'ordre, sans en sauter d'étape.
Fenêtre à couvrir : depuis la date de l'article le plus récent de content/posts/.

Trois rappels :
— un article au maximum ; zéro est une issue normale et attendue ;
— aucun fait sans source primaire réellement ouverte pendant cette exécution ;
— `php outils/valider.php` doit sortir en code 0 avant le moindre commit.

Termine ta session par un compte rendu : sources consultées, candidats
envisagés, motif de chaque rejet, décision finale.
```

### Branche et relecture

Par défaut, une routine travaille sur une branche préfixée `claude/` et vous
soumet ses changements. Deux régimes possibles :

- **Relecture** — vous fusionnez la proposition d'un clic depuis le téléphone.
  Le cron OVH prend le relais dans l'heure.
- **Autonomie complète** — demandez dans le prompt de pousser directement sur
  `main`, et passez `PUBLICATION_DIRECTE=oui` dans `CLAUDE.md`.

Un second cran de sécurité existe indépendamment : le champ `statut`. Un article
en `brouillon` est committé, versionné, visible dans le dépôt, mais absent du
site tant que vous n'avez pas basculé la valeur sur `publie`.

---

## Écrire ou corriger un article à la main

Créez `content/posts/AAAA-MM-JJ-slug.json` sur le modèle de l'article existant,
puis vérifiez :

```sh
php outils/valider.php
```

Le validateur contrôle le format, les longueurs, l'unicité des slugs, la présence
de sources en `https://`, l'absence de HTML dans le corps. Il refuse tout ce qui
sort du schéma.

Pour prévisualiser en local :

```sh
php -S localhost:8000 -t public
```

---

## Structure

```
CLAUDE.md                  charte éditoriale — lue par l'agent à chaque exécution
content/posts/*.json       les articles, un fichier chacun
schema/post.schema.json    le format d'un article, commenté
src/
  config.php               titre, URL, laboratoires suivis
  Contenu.php              lecture et tri du corpus
  Markdown.php             conversion Markdown → HTML, sans dépendance
  gabarit.php              en-tête, pied, utilitaires d'affichage
  amorce.php               chargement commun
public/                    ← racine web
  index.php                le journal, filtrable par laboratoire
  article.php              /a/{slug}
  tag.php                  /t/{étiquette}
  rss.php                  /rss.xml
  assets/style.css
  .htaccess                URLs propres et protection des dossiers
  .ovhconfig               version de PHP côté OVH
outils/valider.php         contrôle bloquant avant commit
deploiement/ovh-pull.sh    déploiement par cron
```

Aucune dépendance Composer, aucun JavaScript, aucune base de données.
