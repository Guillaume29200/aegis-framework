<?php
declare(strict_types=1);

namespace Framework\Templating;

/**
 * Moteur de gabarits HTML partagé par tous les modules.
 *
 * Un thème n'est fait que de fichiers .html : aucun PHP à écrire, donc aucune
 * exécution de code arbitraire depuis un thème téléversé. La syntaxe est
 * volontairement étroite — afficher, tester, boucler, inclure — et rien de
 * plus : c'est ce qui rend un thème sûr et lisible par un intégrateur.
 *
 *   {{ variable }}            valeur échappée
 *   {{{ variable }}}          valeur brute (HTML déjà sûr, produit par le module)
 *   {{ a.b.c }}               chemin dans les données, index numérique compris
 *   {{ x | filtre:argument }} upper, lower, date, datetime, truncate, nl2br,
 *                             number, default, count, initials
 *   {% if chemin %} … {% else %} … {% endif %}
 *   {% if not chemin %} … {% endif %}
 *   {% for x in liste %} … {% empty %} … {% endfor %}   (limit:N littéral)
 *   {% include "partiel" %}
 *
 * Ce qu'il ne sait volontairement PAS faire : comparer deux valeurs, calculer,
 * appeler une fonction. Tout ce qui relève d'une décision se prépare côté PHP
 * et arrive dans les données — un gabarit affiche, il ne raisonne pas.
 *
 * Il vit dans le framework et non dans un module : c'est le même moteur pour
 * la boutique, l'hébergement ou n'importe quel module généré. Les modules qui
 * l'exposaient sous leur propre nom en héritent désormais.
 */
class TemplateEngine
{
    private const MAX_INCLUDE_DEPTH = 5;

    /** @var callable(string):?string Résout un nom de template en contenu brut. */
    private $loader;

    /** @var array<string, array> AST mémoïsés pour la requête en cours. */
    private array $astCache = [];

    /**
     * @param callable(string):?string $loader Reçoit un nom logique ("home", "header"),
     *                                         retourne le contenu du template ou null.
     */
    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    /** Rend un template nommé avec les données fournies. */
    public function render(string $name, array $data = []): string
    {
        $source = ($this->loader)($name);
        if ($source === null) {
            return '<!-- template introuvable : ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' -->';
        }
        return $this->renderNodes($this->parseCached($name, $source), $data, 0);
    }

    /** Rend une chaîne de template directement (utile pour le contenu de widgets custom). */
    public function renderString(string $source, array $data = []): string
    {
        return $this->renderNodes($this->parse($source), $data, 0);
    }

    // ── Parsing ──────────────────────────────────────────────────────────

    private function parseCached(string $name, string $source): array
    {
        if (!isset($this->astCache[$name])) {
            $this->astCache[$name] = $this->parse($source);
        }
        return $this->astCache[$name];
    }

    /** Découpe le source en jetons puis construit l'arbre. */
    private function parse(string $source): array
    {
        // Capture {{{ raw }}}, {{ var }} et {% tag %} en conservant le texte intercalé.
        $pattern = '/(\{\{\{.*?\}\}\}|\{\{.*?\}\}|\{%.*?%\})/s';
        $tokens  = preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $position = 0;
        return $this->buildNodes($tokens ?: [], $position, null);
    }

    /**
     * Construit récursivement les nœuds jusqu'au tag de fermeture attendu.
     *
     * @param string[] $tokens
     * @param int      $position  Curseur partagé (par référence).
     * @param string|null $stopAt Tag de fermeture attendu ("endfor", "endif", "else", "empty").
     */
    private function buildNodes(array $tokens, int &$position, ?string $stopAt): array
    {
        $nodes = [];

        while ($position < count($tokens)) {
            $token = $tokens[$position];

            // ── Variable brute {{{ x }}} ──
            if (str_starts_with($token, '{{{') && str_ends_with($token, '}}}')) {
                $expr = trim(substr($token, 3, -3));
                $nodes[] = $this->makeVarNode($expr, true);
                $position++;
                continue;
            }

            // ── Variable échappée {{ x }} ──
            if (str_starts_with($token, '{{') && str_ends_with($token, '}}')) {
                $expr = trim(substr($token, 2, -2));
                $nodes[] = $this->makeVarNode($expr, false);
                $position++;
                continue;
            }

            // ── Tag de contrôle {% ... %} ──
            if (str_starts_with($token, '{%') && str_ends_with($token, '%}')) {
                $tag = trim(substr($token, 2, -2));

                // Tags de fermeture : on rend la main à l'appelant.
                if (in_array($tag, ['endfor', 'endif', 'else', 'empty'], true)) {
                    if ($tag === $stopAt || in_array($tag, ['endfor', 'endif'], true)) {
                        return $nodes;
                    }
                    // Tag de fermeture orphelin : ignoré silencieusement (thème mal formé).
                    $position++;
                    continue;
                }

                // {% for item in list %}
                // Le suffixe « limit:N » est optionnel et borne la boucle sans
                // toucher aux données : deux thèmes peuvent afficher un nombre
                // différent d'éléments à partir de la même liste.
                if (preg_match('/^for\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+in\s+([a-zA-Z_][a-zA-Z0-9_.]*)(?:\s+limit:(\d+))?$/', $tag, $m)) {
                    $position++;
                    $body      = $this->buildNodes($tokens, $position, 'empty');
                    $emptyBody = [];
                    if ($this->currentTag($tokens, $position) === 'empty') {
                        $position++;
                        $emptyBody = $this->buildNodes($tokens, $position, 'endfor');
                    }
                    if ($this->currentTag($tokens, $position) === 'endfor') { $position++; }
                    $nodes[] = [
                        'type'  => 'for',
                        'item'  => $m[1],
                        'list'  => $m[2],
                        'limit' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null,
                        'body'  => $body,
                        'empty' => $emptyBody,
                    ];
                    continue;
                }

                // {% if [not] path %}
                if (preg_match('/^if\s+(not\s+)?([a-zA-Z_][a-zA-Z0-9_.]*)$/', $tag, $m)) {
                    $position++;
                    $body     = $this->buildNodes($tokens, $position, 'else');
                    $elseBody = [];
                    if ($this->currentTag($tokens, $position) === 'else') {
                        $position++;
                        $elseBody = $this->buildNodes($tokens, $position, 'endif');
                    }
                    if ($this->currentTag($tokens, $position) === 'endif') { $position++; }
                    $nodes[] = ['type' => 'if', 'path' => $m[2], 'negate' => trim($m[1] ?? '') === 'not', 'body' => $body, 'else' => $elseBody];
                    continue;
                }

                // {% include "name" %}
                if (preg_match('/^include\s+["\']([a-zA-Z0-9_-]+)["\']$/', $tag, $m)) {
                    $nodes[] = ['type' => 'include', 'name' => $m[1]];
                    $position++;
                    continue;
                }

                // Tag inconnu : ignoré (le thème reste affichable).
                $position++;
                continue;
            }

            // ── Texte littéral ──
            $nodes[] = ['type' => 'text', 'value' => $token];
            $position++;
        }

        return $nodes;
    }

    /** Nom du tag de contrôle à la position courante, ou null. */
    private function currentTag(array $tokens, int $position): ?string
    {
        if (!isset($tokens[$position])) return null;
        $token = $tokens[$position];
        if (!str_starts_with($token, '{%') || !str_ends_with($token, '%}')) return null;
        return trim(substr($token, 2, -2));
    }

    /** Construit un nœud variable en extrayant les éventuels filtres. */
    private function makeVarNode(string $expr, bool $raw): array
    {
        $parts   = array_map('trim', explode('|', $expr));
        $path    = array_shift($parts) ?? '';
        return ['type' => 'var', 'path' => $path, 'filters' => $parts, 'raw' => $raw];
    }

    // ── Rendu ────────────────────────────────────────────────────────────

    private function renderNodes(array $nodes, array $data, int $depth): string
    {
        $out = '';

        foreach ($nodes as $node) {
            switch ($node['type']) {
                case 'text':
                    $out .= $node['value'];
                    break;

                case 'var':
                    $value = $this->resolvePath($node['path'], $data);
                    [$value, $isSafeHtml] = $this->applyFilters($value, $node['filters']);
                    $out  .= ($node['raw'] || $isSafeHtml)
                        ? (string) $value
                        : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                    break;

                case 'if':
                    $truthy = $this->isTruthy($this->resolvePath($node['path'], $data));
                    if ($node['negate']) { $truthy = !$truthy; }
                    $out .= $this->renderNodes($truthy ? $node['body'] : $node['else'], $data, $depth);
                    break;

                case 'for':
                    $list = $this->resolvePath($node['list'], $data);

                    // La coupe précède la boucle : loop.count, loop.last et le
                    // bloc {% empty %} portent bien sur ce qui est affiché.
                    if (is_array($list) && ($node['limit'] ?? null) !== null) {
                        $list = array_slice($list, 0, $node['limit']);
                    }

                    if (!is_array($list) || empty($list)) {
                        $out .= $this->renderNodes($node['empty'], $data, $depth);
                        break;
                    }
                    $list  = array_values($list);
                    $count = count($list);
                    foreach ($list as $i => $item) {
                        $scope = $data;
                        $scope[$node['item']] = $item;
                        $scope['loop'] = [
                            'index' => $i + 1,
                            'index0' => $i,
                            'first' => $i === 0,
                            'last'  => $i === $count - 1,
                            'count' => $count,
                        ];
                        $out .= $this->renderNodes($node['body'], $scope, $depth);
                    }
                    break;

                case 'include':
                    if ($depth >= self::MAX_INCLUDE_DEPTH) {
                        $out .= '<!-- include trop imbriqué : ' . htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') . ' -->';
                        break;
                    }
                    $source = ($this->loader)($node['name']);
                    if ($source === null) {
                        $out .= '<!-- partiel introuvable : ' . htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') . ' -->';
                        break;
                    }
                    $out .= $this->renderNodes($this->parseCached($node['name'], $source), $data, $depth + 1);
                    break;
            }
        }

        return $out;
    }

    /** Résout "a.b.c" dans un tableau imbriqué. */
    private function resolvePath(string $path, array $data): mixed
    {
        if ($path === '') return '';
        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return '';
            }
        }
        return $current;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_array($value)) return !empty($value);
        if (is_string($value)) return trim($value) !== '' && $value !== '0';
        return (bool) $value;
    }

    /**
     * Filtres disponibles dans les thèmes — volontairement peu nombreux et sans
     * effet de bord.
     *
     * Retourne aussi un drapeau indiquant que le résultat est déjà du HTML sûr :
     * un filtre comme nl2br produit des balises, que le rendu ne doit donc pas
     * ré-échapper (sinon le visiteur voit « <br /> » en toutes lettres). Ces
     * filtres échappent eux-mêmes le texte AVANT d'ajouter leurs balises, donc
     * le contenu utilisateur reste neutralisé.
     *
     * @return array{0:mixed, 1:bool} [valeur, déjà échappée]
     */
    private function applyFilters(mixed $value, array $filters): array
    {
        $isSafeHtml = false;

        foreach ($filters as $filter) {
            // Filtre avec argument : truncate:80, default:"—", date:"d/m/Y"
            $arg = null;
            if (str_contains($filter, ':')) {
                [$filter, $arg] = array_map('trim', explode(':', $filter, 2));
                $arg = trim($arg, '"\'');
            }

            $value = match ($filter) {
                'upper'    => mb_strtoupper((string) $value),
                'lower'    => mb_strtolower((string) $value),
                'date'     => $this->formatDate($value, $arg ?: 'd/m/Y'),
                'datetime' => $this->formatDate($value, $arg ?: 'd/m/Y H:i'),
                'truncate' => mb_strimwidth((string) $value, 0, max(4, (int) ($arg ?: 100)), '…'),
                // Échappe d'abord, ajoute les <br> ensuite : le texte de
                // l'utilisateur ne peut donc pas injecter de balise.
                'nl2br'    => nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')),
                'number'   => is_numeric($value) ? number_format((float) $value, (int) ($arg ?? 0), ',', ' ') : $value,
                'default'  => $this->isTruthy($value) ? $value : ($arg ?? ''),
                'count'    => is_array($value) ? count($value) : 0,
                'initials' => $this->initials((string) $value, (int) ($arg ?: 2)),
                default    => $value,
            };

            // nl2br a produit du HTML déjà échappé ; tout autre filtre appliqué
            // ensuite (truncate, upper…) casserait les balises, on redevient
            // donc du texte à échapper normalement.
            $isSafeHtml = $filter === 'nl2br';
        }

        return [$value, $isSafeHtml];
    }

    /**
     * Initiales pour les pastilles d'avatar : « Aegis Black » → « AB »,
     * « n0va » → « N0 ». Contrairement à truncate, aucune ellipse n'est ajoutée.
     */
    private function initials(string $value, int $length): string
    {
        $length = max(1, min(3, $length));
        $words  = preg_split('/[\s\-_]+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Plusieurs mots : une lettre par mot (prénom + nom, nom d'équipe…).
        if (count($words) >= 2) {
            $out = '';
            foreach (array_slice($words, 0, $length) as $word) {
                $out .= mb_strtoupper(mb_substr($word, 0, 1));
            }
            return $out;
        }

        return mb_strtoupper(mb_substr($words[0] ?? $value, 0, $length));
    }

    private function formatDate(mixed $value, string $format): string
    {
        if (empty($value)) return '';
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if (!$timestamp) { return ''; }

        return date($this->frenchFormat($format, $timestamp), $timestamp);
    }

    /**
     * date() n'écrit les jours et les mois qu'en anglais, ce qui donnait
     * « 23 Jul » sur un site français. Plutôt que de traduire le résultat —
     * où l'on ne distingue plus un nom de mois d'un mot du texte — on remplace
     * les lettres de format concernées par le libellé français, échappé, avant
     * de laisser date() traiter le reste.
     *
     * Les échappements déjà présents dans le format sont respectés : un M
     * précédé d'une contre-oblique reste un M littéral.
     */
    private function frenchFormat(string $format, int $timestamp): string
    {
        $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                  'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $court = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
                  'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        $n = (int) date('n', $timestamp);
        $w = (int) date('w', $timestamp);

        $noms = [
            'l' => $jours[$w],
            'D' => mb_substr($jours[$w], 0, 3, 'UTF-8'),
            'F' => $mois[$n],
            'M' => $court[$n],
        ];

        $out = '';
        for ($i = 0, $len = strlen($format); $i < $len; $i++) {
            $c = $format[$i];

            if ($c === '\\') {
                $out .= $c . ($format[$i + 1] ?? '');
                $i++;
                continue;
            }

            if (isset($noms[$c])) {
                // Chaque caractère du libellé est échappé : sans quoi le « a »
                // de « mars » deviendrait « am/pm ».
                // preg_replace ne convient pas ici : dans un motif de
                // remplacement, une contre-oblique devant un $ échappe le
                // groupe au lieu de s'écrire. On assemble donc à la main.
                $lettres = preg_split('//u', $noms[$c], -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $out .= '\\' . implode('\\', $lettres);
                continue;
            }

            $out .= $c;
        }

        return $out;
    }
}
