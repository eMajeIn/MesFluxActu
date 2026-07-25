#!/bin/sh
#
# Récupère les nouveaux articles depuis GitHub.
# Destiné aux tâches planifiées de l'hébergement mutualisé OVH.
#
#   Fréquence conseillée : toutes les heures
#   Commande à déclarer   : /home/VOTRE_LOGIN/blog-ia/deploiement/ovh-pull.sh
#
# Rendre exécutable une fois pour toutes :
#   chmod +x deploiement/ovh-pull.sh

set -eu

DEPOT="${HOME}/blog-ia"
BRANCHE="main"
VERROU="${HOME}/.blog-ia.verrou"
JOURNAL="${HOME}/blog-ia-deploiement.log"

trace() {
    printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" >> "$JOURNAL"
}

# Verrou atomique : deux exécutions ne doivent jamais se chevaucher.
if ! mkdir "$VERROU" 2>/dev/null; then
    trace "exécution déjà en cours, abandon"
    exit 0
fi
trap 'rmdir "$VERROU" 2>/dev/null || true' EXIT INT TERM

cd "$DEPOT" || { trace "dépôt introuvable dans ${DEPOT}"; exit 1; }

AVANT=$(git rev-parse HEAD)

if ! git fetch --quiet origin "$BRANCHE" 2>>"$JOURNAL"; then
    trace "échec du fetch"
    exit 1
fi

# --ff-only : si l'historique a divergé, on préfère échouer que produire un merge.
if ! git merge --ff-only "origin/${BRANCHE}" >/dev/null 2>>"$JOURNAL"; then
    trace "avance rapide impossible, intervention manuelle requise"
    exit 1
fi

APRES=$(git rev-parse HEAD)

if [ "$AVANT" = "$APRES" ]; then
    trace "aucun changement"
    exit 0
fi

NOUVEAUX=$(git diff --name-only --diff-filter=A "$AVANT" "$APRES" -- content/posts | wc -l | tr -d ' ')
trace "mis à jour ${AVANT} -> ${APRES} (${NOUVEAUX} nouvel/nouveaux article(s))"

# Le journal ne doit pas grossir indéfiniment sur un mutualisé.
if [ -f "$JOURNAL" ] && [ "$(wc -l < "$JOURNAL")" -gt 2000 ]; then
    tail -n 500 "$JOURNAL" > "${JOURNAL}.tmp" && mv "${JOURNAL}.tmp" "$JOURNAL"
fi

exit 0
