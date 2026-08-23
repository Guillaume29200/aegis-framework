<?php
declare(strict_types=1);

namespace Framework\Capabilities\Markdown;

/**
 * Moteur Markdown PARTAGÉ — capacité « markdown » du framework.
 *
 * Consolidation de la meilleure des 5 copies historiques (Blogging) rendue
 * transverse : namespace framework, classes CSS neutralisées en `.md-*`,
 * mentions désactivées par défaut (pas de couplage à un module de profils).
 *
 * Syntaxe standard :
 *   # h1  ## h2  ### h3   **gras**  *italique*  ~~barré~~  `code`
 *   ```lang ... ```   > quote   - liste   1. liste   | tableau |
 *   [texte](url)   ![alt](url)   ---
 *
 * Extensions :
 *   [spoiler]texte secret[/spoiler]
 *   [quote=Pseudo]texte cité[/quote]
 *   [youtube]https://youtu.be/ID[/youtube]   (si $allowMedia)
 *   [color=#hex ou nom]texte[/color]
 *   [size=sm|md|lg|xl]texte[/size]
 *   :emoji:    →  emoji unicode (subset)
 *
 * 🛡️ Sécurité :
 *   - Tout le markdown est HTML-échappé à l'entrée (render()) AVANT tout traitement.
 *   - Les appels récursifs (spoilers, quotes, blockquotes) passent par process()
 *     pour éviter le double-encodage.
 *   - Les URLs sont filtrées par sanitizeUrl() (javascript:, data:, etc. bloqués).
 */
class MarkdownService
{
    private array $codeBlocks = [];
    private int   $codeIndex  = 0;

    private const EMOJIS = [
        ':smile:'       => '😄', ':laughing:'   => '😆', ':wink:'       => '😉',
        ':heart:'       => '❤️',  ':thumbsup:'   => '👍', ':thumbsdown:' => '👎',
        ':fire:'        => '🔥',  ':star:'       => '⭐', ':check:'      => '✅',
        ':x:'           => '❌',  ':warning:'    => '⚠️', ':info:'       => 'ℹ️',
        ':rocket:'      => '🚀',  ':eyes:'       => '👀', ':clap:'       => '👏',
        ':sad:'         => '😢',  ':angry:'      => '😡', ':cool:'       => '😎',
        ':thinking:'    => '🤔',  ':party:'      => '🎉', ':trophy:'     => '🏆',
        ':bug:'         => '🐛',  ':lock:'       => '🔒', ':key:'        => '🔑',
        ':bulb:'        => '💡',  ':question:'   => '❓', ':exclamation:'=> '❗',
    ];

    // =========================================================================
    // POINT D'ENTRÉE PUBLIC
    // =========================================================================

    /**
     * Convertit le markdown utilisateur en HTML sécurisé.
     *
     * 🛡️ Étape 1 : échapper & < " ' pour neutraliser tout HTML brut.
     *    '>' n'est PAS échappé afin de préserver la syntaxe blockquote (> ...).
     * Étape 2 : process() applique ensuite la pipeline markdown/BBCode.
     */
    public function render(string $markdown, bool $allowMedia = true): string
    {
        $this->codeBlocks = [];
        $this->codeIndex  = 0;

        $text = $this->normalizeLineEndings($markdown);

        // 🛡️ Échappement global HTML : & < " ' (pas > pour les blockquotes markdown)
        $text = str_replace(
            ['&',     '<',    '"',      "'"],
            ['&amp;', '&lt;', '&quot;', '&#039;'],
            $text
        );

        return '<div class="md-body">' . $this->process($text, $allowMedia) . '</div>';
    }

    // =========================================================================
    // PIPELINE INTERNE (contenu déjà HTML-échappé)
    // =========================================================================

    private function process(string $text, bool $allowMedia = true): string
    {
        $text = $this->extractCodeBlocks($text);

        // Extensions BBcode-like AVANT le markdown
        $text = $this->parseSpoilers($text);
        $text = $this->parseQuotes($text);
        if ($allowMedia) { $text = $this->parseYoutube($text); }
        $text = $this->parseColor($text);
        $text = $this->parseSize($text);

        // Blocs markdown
        $text = $this->parseHorizontalRules($text);
        $text = $this->parseHeadings($text);
        $text = $this->parseBlockquotes($text);
        $text = $this->parseTables($text);
        $text = $this->parseLists($text);
        $text = $this->parseParagraphs($text);

        // Inline
        $text = $this->parseInline($text);

        // Emojis :name:
        $text = $this->parseEmojis($text);

        // Réinjecter les blocs de code
        $text = $this->restoreCodeBlocks($text);

        return $text;
    }

    // =========================================================================
    // BLOCS DE CODE
    // =========================================================================

    private function extractCodeBlocks(string $text): string
    {
        return preg_replace_callback(
            '/```(\w*)\n?(.*?)```/s',
            function (array $m): string {
                $lang     = trim($m[1]);
                $code     = trim($m[2]); // déjà HTML-échappé globalement
                $langAttr = $lang ? ' class="language-' . $lang . '" data-lang="' . $lang . '"' : '';
                $html     = '<div class="md-code-wrap">'
                          . ($lang ? '<span class="md-code-lang">' . $lang . '</span>' : '')
                          . '<button type="button" class="md-code-copy" onclick="mdCopyCode(this)" title="Copier">📋</button>'
                          . '<pre class="md-code-block"><code' . $langAttr . '>' . $code . '</code></pre>'
                          . '</div>';
                $key = "\x02CODE{$this->codeIndex}\x03";
                $this->codeBlocks[$key] = $html;
                $this->codeIndex++;
                return $key;
            },
            $text
        );
    }

    private function restoreCodeBlocks(string $text): string
    {
        return str_replace(array_keys($this->codeBlocks), array_values($this->codeBlocks), $text);
    }

    // =========================================================================
    // EXTENSIONS
    // =========================================================================

    private function parseSpoilers(string $text): string
    {
        return preg_replace_callback(
            '/\[spoiler\](.*?)\[\/spoiler\]/si',
            function (array $m): string {
                $inner = $this->process(trim($m[1]), false);
                return '<details class="md-spoiler"><summary>Spoiler — cliquez pour révéler</summary><div class="md-spoiler-body">' . $inner . '</div></details>';
            },
            $text
        );
    }

    private function parseQuotes(string $text): string
    {
        return preg_replace_callback(
            '/\[quote(?:=([^\]]+))?\](.*?)\[\/quote\]/si',
            function (array $m): string {
                $author = $m[1] ? '<cite>' . $m[1] . ' a écrit :</cite>' : '';
                $inner  = $this->process(trim($m[2]), false);
                return '<blockquote class="md-quote">' . $author . $inner . '</blockquote>';
            },
            $text
        );
    }

    private function parseYoutube(string $text): string
    {
        return preg_replace_callback(
            '/\[youtube\](https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})[^\[]*)\[\/youtube\]/i',
            function (array $m): string {
                $id = $m[2]; // validé par regex : [a-zA-Z0-9_-]{11}
                return '<div class="md-yt-wrap"><iframe class="md-yt" src="https://www.youtube.com/embed/' . $id
                    . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
            },
            $text
        );
    }

    private function parseColor(string $text): string
    {
        return preg_replace_callback(
            '/\[color=(#[0-9a-fA-F]{3,6}|[a-zA-Z]+)\](.*?)\[\/color\]/si',
            fn($m) => '<span style="color:' . $m[1] . '">' . $m[2] . '</span>',
            $text
        );
    }

    private function parseSize(string $text): string
    {
        $map = ['sm' => '0.85em', 'md' => '1em', 'lg' => '1.25em', 'xl' => '1.6em'];
        return preg_replace_callback(
            '/\[size=(sm|md|lg|xl)\](.*?)\[\/size\]/si',
            function (array $m) use ($map): string {
                $sz = $map[$m[1]] ?? '1em';
                return '<span style="font-size:' . $sz . '">' . $m[2] . '</span>';
            },
            $text
        );
    }

    private function parseEmojis(string $text): string
    {
        return str_replace(array_keys(self::EMOJIS), array_values(self::EMOJIS), $text);
    }

    // =========================================================================
    // BLOCS MARKDOWN
    // =========================================================================

    private function parseHorizontalRules(string $text): string
    {
        return preg_replace('/^[ \t]*(?:---+|\*\*\*+|___+)[ \t]*$/m', '<hr class="md-hr">', $text);
    }

    private function parseHeadings(string $text): string
    {
        return preg_replace_callback(
            '/^(#{1,4})\s+(.+)$/m',
            function (array $m): string {
                $level = min(strlen($m[1]), 4);
                $id    = 'h-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($m[2])));
                return "<h{$level} id=\"{$id}\" class=\"md-heading\">" . $this->inlineLight(trim($m[2])) . "</h{$level}>";
            },
            $text
        );
    }

    private function parseBlockquotes(string $text): string
    {
        return preg_replace_callback(
            '/(?:^>[ \t]?.+\n?)+/m',
            function (array $m): string {
                $inner = preg_replace('/^>[ \t]?/m', '', $m[0]);
                return '<blockquote class="md-blockquote">' . trim($this->process($inner, false)) . '</blockquote>' . "\n";
            },
            $text
        );
    }

    private function parseTables(string $text): string
    {
        return preg_replace_callback(
            '/(?:^\|.+\|\n)+/m',
            function (array $m): string {
                $lines  = array_filter(explode("\n", trim($m[0])));
                $rows   = [];
                $isHead = true;
                foreach ($lines as $line) {
                    if (preg_match('/^\|[-| :]+\|$/', trim($line))) { $isHead = false; continue; }
                    $cells = array_map('trim', explode('|', trim($line, '| ')));
                    $tag   = $isHead ? 'th' : 'td';
                    $rows[] = ['row' => '<tr>' . implode('', array_map(fn($c) => "<{$tag}>" . $this->inlineLight($c) . "</{$tag}>", $cells)) . '</tr>', 'head' => $isHead];
                }
                $thead = $tbody = '';
                foreach ($rows as $r) { $r['head'] ? $thead .= $r['row'] : $tbody .= $r['row']; }
                return '<div class="md-table-wrap"><table class="md-table">'
                    . ($thead ? "<thead>{$thead}</thead>" : '')
                    . ($tbody ? "<tbody>{$tbody}</tbody>" : '')
                    . '</table></div>' . "\n";
            },
            $text
        );
    }

    private function parseLists(string $text): string
    {
        $text = preg_replace_callback('/((?:^[ \t]*[-*+][ \t]+.+\n?)+)/m', fn($m) => $this->buildList($m[1], 'ul'), $text);
        $text = preg_replace_callback('/((?:^[ \t]*\d+\.[ \t]+.+\n?)+)/m', fn($m) => $this->buildList($m[1], 'ol'), $text);
        return $text;
    }

    private function buildList(string $block, string $tag): string
    {
        $pat   = $tag === 'ul' ? '/^[ \t]*[-*+][ \t]+(.+)$/m' : '/^[ \t]*\d+\.[ \t]+(.+)$/m';
        $items = [];
        preg_match_all($pat, $block, $matches);
        foreach ($matches[1] as $item) { $items[] = '<li>' . $this->inlineLight(trim($item)) . '</li>'; }
        return "<{$tag} class=\"md-list\">" . implode('', $items) . "</{$tag}>\n";
    }

    private function parseParagraphs(string $text): string
    {
        $blocks = preg_split('/\n{2,}/', trim($text));
        $result = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;
            $isBlock = preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|table|div|details|hr|p)[\s>\/]/', $block)
                    || preg_match('/^\x02CODE\d+\x03$/', $block);
            if ($isBlock) {
                $result[] = $block;
            } else {
                $block    = preg_replace('/\n/', "<br>\n", $block);
                $result[] = '<p class="md-p">' . $block . '</p>';
            }
        }
        return implode("\n", $result);
    }

    // =========================================================================
    // INLINE
    // =========================================================================

    private function parseInline(string $text): string
    {
        // Images : src filtré par sanitizeUrl(), alt déjà HTML-échappé
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/',
            fn($m) => '<img src="' . $this->sanitizeUrl($m[2]) . '" alt="' . $m[1] . '" class="md-img" loading="lazy">',
            $text
        );
        // Liens : href filtré par sanitizeUrl(), texte déjà HTML-échappé
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            fn($m) => '<a href="' . $this->sanitizeUrl($m[2]) . '" target="_blank" rel="noopener noreferrer" class="md-link">' . $m[1] . '</a>',
            $text
        );
        $text = preg_replace('/`([^`]+)`/', '<code class="md-inline-code">$1</code>', $text);
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/', '<s>$1</s>', $text);
        return $text;
    }

    private function inlineLight(string $text): string
    {
        $text = preg_replace('/`([^`]+)`/', '<code class="md-inline-code">$1</code>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/', '<s>$1</s>', $text);
        return $text;
    }

    // =========================================================================
    // 🛡️ SÉCURITÉ URL
    // =========================================================================

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Double décodage pour détecter les obfuscations doublement encodées
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Supprimer espaces, tabulations et caractères de contrôle
        $clean = preg_replace('/[\x00-\x20\x7f\xc2\xa0]/u', '', strtolower($decoded));

        $blocked = ['javascript:', 'vbscript:', 'data:', 'mhtml:', 'file:'];
        foreach ($blocked as $proto) {
            if (str_starts_with($clean, $proto)) {
                return '#';
            }
        }

        return $url; // URL originale déjà HTML-échappée
    }

    // =========================================================================
    // ASSETS STATIQUES (fallback si les fichiers md.css/md-editor.js absents)
    // =========================================================================

    private function normalizeLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }
}
