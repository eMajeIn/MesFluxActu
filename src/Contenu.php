<?php
/**
 * Lecture du corpus d'articles.
 *
 * Un article = un fichier JSON dans content/posts/.
 * Rien n'est compilé : ajouter un fichier suffit à publier.
 */
final class Contenu
{
    private static ?array $cache = null;

    private static function dossier(): string
    {
        return dirname(__DIR__) . '/content/posts';
    }

    /**
     * Tous les articles publiés, du plus récent au plus ancien.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tous(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $articles = [];
        $fichiers = glob(self::dossier() . '/*.json') ?: [];

        foreach ($fichiers as $chemin) {
            $brut = file_get_contents($chemin);
            if ($brut === false) {
                continue;
            }

            $article = json_decode($brut, true);
            if (!is_array($article)) {
                continue; // fichier illisible : on l'ignore plutôt que de casser le site
            }

            // Un article sans ces trois champs n'est pas affichable.
            if (empty($article['slug']) || empty($article['titre']) || empty($article['date'])) {
                continue;
            }

            if (($article['statut'] ?? 'brouillon') !== 'publie') {
                continue;
            }

            $article['horodatage'] = strtotime($article['date']) ?: 0;
            $article['labo']       = $article['labo'] ?? 'autre';
            $article['tags']       = array_values(array_filter((array) ($article['tags'] ?? [])));
            $article['sources']    = array_values(array_filter(
                (array) ($article['sources'] ?? []),
                static fn ($s): bool => is_array($s) && !empty($s['url'])
            ));

            $articles[] = $article;
        }

        usort($articles, static fn (array $a, array $b): int => $b['horodatage'] <=> $a['horodatage']);

        return self::$cache = $articles;
    }

    public static function parSlug(string $slug): ?array
    {
        foreach (self::tous() as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }
        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function parTag(string $tag): array
    {
        $tag = mb_strtolower($tag);
        return array_values(array_filter(
            self::tous(),
            static fn (array $a): bool => in_array(
                $tag,
                array_map(static fn ($t): string => mb_strtolower((string) $t), $a['tags']),
                true
            )
        ));
    }

    /** @return array<int, array<string, mixed>> */
    public static function parLabo(string $labo): array
    {
        return array_values(array_filter(
            self::tous(),
            static fn (array $a): bool => $a['labo'] === $labo
        ));
    }

    /**
     * Tags du corpus, triés par fréquence décroissante.
     *
     * @return array<string, int>
     */
    public static function tags(): array
    {
        $comptes = [];
        foreach (self::tous() as $article) {
            foreach ($article['tags'] as $tag) {
                $cle = mb_strtolower((string) $tag);
                $comptes[$cle] = ($comptes[$cle] ?? 0) + 1;
            }
        }
        arsort($comptes);
        return $comptes;
    }

    /**
     * Les deux articles encadrant celui-ci dans la chronologie.
     *
     * @return array{precedent: ?array, suivant: ?array}
     */
    public static function voisins(string $slug): array
    {
        $tous  = self::tous();
        $index = null;
        foreach ($tous as $i => $article) {
            if ($article['slug'] === $slug) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return ['precedent' => null, 'suivant' => null];
        }
        return [
            'suivant'   => $tous[$index - 1] ?? null, // plus récent
            'precedent' => $tous[$index + 1] ?? null, // plus ancien
        ];
    }

    /** Durée de lecture estimée, en minutes. */
    public static function dureeLecture(array $article): int
    {
        $texte = (string) ($article['corps'] ?? '');
        $mots  = preg_match_all('/[\p{L}\p{N}]+/u', $texte);
        return max(1, (int) ceil(((int) $mots) / 220));
    }
}
