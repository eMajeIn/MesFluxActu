<?php
/**
 * Mini-parseur Markdown, sans dépendance externe.
 *
 * Sous-ensemble volontairement restreint : titres, paragraphes, listes,
 * citations, blocs de code, filets, gras, italique, code inline, liens.
 *
 * Principe de sécurité : le texte est échappé AVANT toute transformation.
 * Aucun HTML brut présent dans le JSON ne peut donc s'exécuter.
 */
final class Markdown
{
    /** @var string[] Réservoir des fragments de code protégés */
    private static array $coffre = [];

    public static function versHtml(string $texte): string
    {
        self::$coffre = [];

        $texte  = str_replace(["\r\n", "\r"], "\n", $texte);
        $lignes = explode("\n", $texte);

        $html        = '';
        $tampon      = [];   // lignes du paragraphe en cours
        $liste       = null; // 'ul' | 'ol' | null
        $citation    = [];   // lignes de la citation en cours
        $dansCode    = false;
        $codeLangue  = '';
        $codeLignes  = [];

        $viderParagraphe = static function () use (&$tampon, &$html) {
            if ($tampon === []) {
                return;
            }
            $contenu = self::inline(implode(' ', $tampon));
            $html   .= '<p>' . $contenu . "</p>\n";
            $tampon  = [];
        };

        $fermerListe = static function () use (&$liste, &$html) {
            if ($liste !== null) {
                $html .= '</' . $liste . ">\n";
                $liste = null;
            }
        };

        $viderCitation = static function () use (&$citation, &$html) {
            if ($citation === []) {
                return;
            }
            $html    .= '<blockquote><p>' . self::inline(implode(' ', $citation)) . "</p></blockquote>\n";
            $citation = [];
        };

        foreach ($lignes as $ligne) {
            $nue = rtrim($ligne);

            // --- Blocs de code délimités par ``` ---------------------------
            if (preg_match('/^```\s*([A-Za-z0-9_+-]*)\s*$/', $nue, $m)) {
                if ($dansCode) {
                    $classe = $codeLangue !== ''
                        ? ' class="langue-' . htmlspecialchars($codeLangue, ENT_QUOTES, 'UTF-8') . '"'
                        : '';
                    $html .= '<pre><code' . $classe . '>'
                           . htmlspecialchars(implode("\n", $codeLignes), ENT_QUOTES, 'UTF-8')
                           . "</code></pre>\n";
                    $dansCode   = false;
                    $codeLignes = [];
                    $codeLangue = '';
                } else {
                    $viderParagraphe();
                    $fermerListe();
                    $viderCitation();
                    $dansCode   = true;
                    $codeLangue = $m[1];
                }
                continue;
            }

            if ($dansCode) {
                $codeLignes[] = $ligne;
                continue;
            }

            // --- Ligne vide : on ferme tout ce qui est ouvert ---------------
            if (trim($nue) === '') {
                $viderParagraphe();
                $fermerListe();
                $viderCitation();
                continue;
            }

            // --- Filet horizontal ------------------------------------------
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', trim($nue))) {
                $viderParagraphe();
                $fermerListe();
                $viderCitation();
                $html .= "<hr>\n";
                continue;
            }

            // --- Titres ----------------------------------------------------
            // Le titre de l'article occupe déjà le <h1> : on démarre à <h2>.
            if (preg_match('/^(#{1,4})\s+(.*)$/', $nue, $m)) {
                $viderParagraphe();
                $fermerListe();
                $viderCitation();
                $niveau = min(strlen($m[1]) + 1, 5);
                $html  .= '<h' . $niveau . '>' . self::inline(trim($m[2])) . '</h' . $niveau . ">\n";
                continue;
            }

            // --- Citations -------------------------------------------------
            if (preg_match('/^>\s?(.*)$/', $nue, $m)) {
                $viderParagraphe();
                $fermerListe();
                $citation[] = trim($m[1]);
                continue;
            }
            $viderCitation();

            // --- Listes à puces --------------------------------------------
            if (preg_match('/^\s*[-*+]\s+(.*)$/', $nue, $m)) {
                $viderParagraphe();
                if ($liste !== 'ul') {
                    $fermerListe();
                    $html .= "<ul>\n";
                    $liste = 'ul';
                }
                $html .= '<li>' . self::inline(trim($m[1])) . "</li>\n";
                continue;
            }

            // --- Listes numérotées -----------------------------------------
            if (preg_match('/^\s*\d+[.)]\s+(.*)$/', $nue, $m)) {
                $viderParagraphe();
                if ($liste !== 'ol') {
                    $fermerListe();
                    $html .= "<ol>\n";
                    $liste = 'ol';
                }
                $html .= '<li>' . self::inline(trim($m[1])) . "</li>\n";
                continue;
            }

            $fermerListe();

            // --- Texte courant ---------------------------------------------
            $tampon[] = trim($nue);
        }

        // Fermeture de ce qui resterait ouvert en fin de document
        if ($dansCode && $codeLignes !== []) {
            $html .= '<pre><code>'
                   . htmlspecialchars(implode("\n", $codeLignes), ENT_QUOTES, 'UTF-8')
                   . "</code></pre>\n";
        }
        $viderParagraphe();
        $fermerListe();
        $viderCitation();

        return $html;
    }

    /**
     * Mise en forme intra-ligne. L'échappement HTML a lieu ici, en premier.
     */
    private static function inline(string $texte): string
    {
        $texte = htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');

        // 1. Le code inline est mis à l'abri : aucune autre règle ne doit
        //    s'y appliquer. On le remplace par un jeton improbable.
        $texte = preg_replace_callback('/`([^`]+)`/', static function (array $m): string {
            self::$coffre[] = '<code>' . $m[1] . '</code>';
            return "\x02" . (count(self::$coffre) - 1) . "\x03";
        }, $texte) ?? $texte;

        // 2. Liens [libellé](url) — seuls http, https et mailto sont acceptés.
        $texte = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m): string {
                $url = $m[2];
                if (!preg_match('#^(https?://|mailto:|/)#i', $url)) {
                    return $m[1];
                }
                $externe = str_starts_with(strtolower($url), 'http')
                    ? ' rel="noopener noreferrer" target="_blank"'
                    : '';
                return '<a href="' . $url . '"' . $externe . '>' . $m[1] . '</a>';
            },
            $texte
        ) ?? $texte;

        // 3. Gras puis italique (l'ordre compte : ** avant *).
        $texte = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $texte) ?? $texte;
        $texte = preg_replace('/(?<!\*)\*(?=\S)([^*]+?)(?<=\S)\*(?!\*)/s', '<em>$1</em>', $texte) ?? $texte;

        // 4. Restitution du code inline.
        $texte = preg_replace_callback('/\x02(\d+)\x03/', static function (array $m): string {
            return self::$coffre[(int) $m[1]] ?? '';
        }, $texte) ?? $texte;

        return $texte;
    }
}
