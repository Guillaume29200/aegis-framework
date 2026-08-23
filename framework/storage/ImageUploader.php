<?php
declare(strict_types=1);

namespace Framework\Storage;

/**
 * Réception d'images téléversées, pour tous les modules.
 *
 * Ce code existait en quatre exemplaires — FPSmeter, GameNodeEsport,
 * Tournaments, et un quatrième propre à Marketplace. Trois disaient la même
 * chose avec des mots différents ; le dernier fait autre chose et reste chez
 * lui. Ce qui suit est la réunion des trois, et non le plus petit
 * dénominateur : chacun gagne ce que les autres avaient de mieux.
 *
 * Ce qu'un fichier reçu subit avant d'être gardé :
 *
 *   — sa taille est bornée ;
 *   — son type est lu dans le fichier, jamais dans ce que le navigateur annonce ;
 *   — l'image est RÉ-ENCODÉE pixel par pixel par GD, ce qui détruit tout octet
 *     caché derrière une image valide — charge PHP, fichier polyglotte,
 *     métadonnée piégée ;
 *   — le SVG est refusé sauf demande explicite, parce qu'un SVG peut porter du
 *     script ;
 *   — le dossier de destination reçoit un .htaccess qui interdit l'exécution
 *     de scripts, au cas où le serveur serait mal réglé.
 *
 * Le ré-encodage est la protection principale : un fichier qui n'est pas une
 * vraie image ne survit pas au passage, et un fichier qui en est une en
 * ressort nettoyé.
 */
final class ImageUploader
{
    /** Types acceptés, et l'extension qu'on leur donne. */
    private const TYPES = [
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/gif'     => 'gif',
        'image/webp'    => 'webp',
        'image/svg+xml' => 'svg',
    ];

    private string $baseDir;
    private string $baseUrl;

    /**
     * @param string $module   Nom du module propriétaire des fichiers.
     * @param int    $maxBytes Taille maximale acceptée.
     */
    public function __construct(private string $module, private int $maxBytes = 5 * 1024 * 1024)
    {
        $root = defined('ROOT_PATH') ? rtrim(ROOT_PATH, '/\\') : dirname(__DIR__, 2);
        $this->baseDir = $root . '/modules/' . $module . '/Assets/uploads/';
        $this->baseUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/' . $module . '/Assets/uploads/';
    }

    /**
     * Même uploader, mais qui écrit ailleurs que dans Assets/uploads.
     *
     * Sert notamment aux thèmes, qui gardent leurs images chez eux pour rester
     * des dossiers autonomes. Tous les contrôles restent identiques : seule la
     * destination change.
     */
    public static function into(string $module, string $absoluteDir, string $publicUrl, int $maxBytes = 5 * 1024 * 1024): self
    {
        $up = new self($module, $maxBytes);
        $up->baseDir = rtrim(str_replace(chr(92), '/', $absoluteDir), '/') . '/';
        $up->baseUrl = rtrim($publicUrl, '/') . '/';
        return $up;
    }

    /**
     * Range une image et renvoie son URL publique, ou null si elle est refusée.
     *
     * @param array       $file $_FILES['champ']
     * @param string      $sub  Sous-dossier (teams, avatars, banners…)
     * @param string|null $old  URL d'un fichier à remplacer, effacé en cas de succès.
     * @param array{svg?:bool} $opts ['svg' => true] pour accepter le SVG.
     */
    public function store(array $file, string $sub, ?string $old = null, array $opts = []): ?string
    {
        try {
            return $this->storeOrFail($file, $sub, $old, $opts);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Même chose, mais explique pourquoi elle refuse.
     *
     * À préférer derrière un formulaire d'administration : « Format non
     * autorisé » aide, un champ qui reste vide sans rien dire n'aide pas.
     *
     * @throws \RuntimeException
     */
    public function storeOrFail(array $file, string $sub, ?string $old = null, array $opts = []): string
    {
        $svgAutorise = (bool) ($opts['svg'] ?? false);

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            throw new \RuntimeException('Aucun fichier reçu.');
        }
        // En dehors d'une requête HTTP (tâche planifiée, test), is_uploaded_file
        // est faux : on ne l'exige que lorsqu'il a un sens.
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Fichier invalide.');
        }
        if (($file['size'] ?? 0) > $this->maxBytes) {
            throw new \RuntimeException('Fichier trop volumineux (' . $this->humanSize($this->maxBytes) . ' au maximum).');
        }

        $mime = $this->detectMime($file);
        if (!isset(self::TYPES[$mime])) {
            throw new \RuntimeException('Format non autorisé (JPG, PNG, GIF, WEBP' . ($svgAutorise ? ', SVG' : '') . ').');
        }
        if ($mime === 'image/svg+xml' && !$svgAutorise) {
            throw new \RuntimeException("Le format SVG n'est pas autorisé ici : il peut porter du script. Utilisez un PNG, JPG, GIF ou WEBP.");
        }
        if ($mime !== 'image/svg+xml' && !@getimagesize($file['tmp_name'])) {
            throw new \RuntimeException("Le fichier ne semble pas être une image valide.");
        }

        $sub = $this->cleanSub($sub);
        $dir = $this->baseDir . $sub . '/';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Impossible de créer le dossier de destination.");
        }
        $this->hardenDir();

        $nom  = bin2hex(random_bytes(8)) . '.' . self::TYPES[$mime];
        $dest = $dir . $nom;

        if ($mime === 'image/svg+xml') {
            // Un SVG ne se ré-encode pas : il n'arrive ici que sur demande
            // explicite, et le .htaccess du dossier en interdit le service
            // comme script.
            if (!$this->place($file['tmp_name'], $dest)) {
                throw new \RuntimeException("Écriture impossible.");
            }
        } elseif (!$this->reencode($file['tmp_name'], $dest, $mime)) {
            // GD absent : on garde le fichier tel quel plutôt que de refuser un
            // téléversement légitime. GD présent mais image illisible : on
            // refuse, c'est le signe d'un fichier truqué.
            if (function_exists('imagecreatefromstring') || !$this->place($file['tmp_name'], $dest)) {
                throw new \RuntimeException("Image illisible : elle a été refusée.");
            }
        }

        if ($old !== null && $old !== '') {
            $this->delete($old);
        }

        return $this->baseUrl . $sub . '/' . $nom;
    }

    /** Efface un fichier téléversé, à condition qu'il nous appartienne. */
    public function delete(string $url): void
    {
        if ($url === '') { return; }

        $relatif = ltrim(str_replace($this->baseUrl, '', $url), '/');
        if ($relatif === '' || str_contains($relatif, '..')) { return; }

        $chemin = $this->baseDir . $relatif;
        $reel   = realpath($chemin);
        $base   = realpath($this->baseDir);

        // Garde-fou : on ne supprime que sous le dossier d'uploads du module.
        if ($reel !== false && $base !== false && str_starts_with($reel, $base) && is_file($reel)) {
            @unlink($reel);
        }
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    // ── Interne ───────────────────────────────────────────────────────────

    /**
     * Nettoie un sous-dossier, en conservant son imbrication.
     *
     * Certains appelants rangent par thème ou par entité — « themes/goldgaming ».
     * Aplatir le chemin déplacerait leurs fichiers ; on nettoie donc segment par
     * segment, et tout ce qui pourrait remonter d'un cran disparaît.
     */
    private function cleanSub(string $sub): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $sub)) as $segment) {
            $segment = preg_replace('/[^a-z0-9_-]/', '', strtolower($segment)) ?? '';
            if ($segment !== '') { $parts[] = $segment; }
        }

        // Trois niveaux suffisent largement et bornent la profondeur.
        return $parts === [] ? 'misc' : implode('/', array_slice($parts, 0, 3));
    }

    /** Type réel du fichier, avec repli sur l'extension pour le seul SVG. */
    private function detectMime(array $file): string
    {
        $mime = function_exists('mime_content_type')
            ? (string) @mime_content_type($file['tmp_name'])
            : '';

        // Les serveurs annoncent le SVG de plusieurs façons ; c'est le seul cas
        // où l'extension d'origine sert de recours.
        if ($mime === '' || !isset(self::TYPES[$mime])) {
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($ext === 'svg') { $mime = 'image/svg+xml'; }
        }

        return $mime;
    }

    /**
     * Ré-encode l'image vers sa destination.
     *
     * C'est ici que se joue la sécurité : GD relit les pixels et réécrit un
     * fichier neuf. Ce qui n'était pas de l'image ne passe pas.
     */
    private function reencode(string $source, string $dest, string $mime): bool
    {
        if (!function_exists('imagecreatefromstring')) { return false; }

        $data = @file_get_contents($source);
        if ($data === false || $data === '') { return false; }

        $img = @imagecreatefromstring($data);
        if (!$img) { return false; }

        // Transparence préservée pour les formats qui en ont.
        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            @imagealphablending($img, false);
            @imagesavealpha($img, true);
        }

        $ok = match ($mime) {
            'image/jpeg' => @imagejpeg($img, $dest, 88),
            'image/png'  => @imagepng($img, $dest, 6),
            'image/gif'  => @imagegif($img, $dest),
            'image/webp' => function_exists('imagewebp') ? @imagewebp($img, $dest, 88) : @imagepng($img, $dest, 6),
            default      => @imagejpeg($img, $dest, 88),
        };

        @imagedestroy($img);
        return (bool) $ok;
    }

    /** Dépose le fichier tel quel, en tenant compte du contexte hors HTTP. */
    private function place(string $source, string $dest): bool
    {
        return PHP_SAPI === 'cli'
            ? @copy($source, $dest)
            : @move_uploaded_file($source, $dest);
    }

    /**
     * Interdit l'exécution de scripts dans le dossier d'uploads.
     *
     * Ceinture et bretelles : le ré-encodage empêche déjà qu'un script y
     * arrive, mais un serveur mal réglé ne doit pas pouvoir exécuter ce qui
     * s'y trouverait.
     */
    private function hardenDir(): void
    {
        $ht = $this->baseDir . '.htaccess';
        if (is_file($ht)) { return; }

        @file_put_contents($ht, implode("\n", [
            'php_flag engine off',
            'RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar',
            'RemoveType .php .phtml .phar',
            '<FilesMatch "\.(php|phtml|phar|cgi|pl|svg)$">',
            '  Require all denied',
            '</FilesMatch>',
            '',
        ]));
    }

    private function humanSize(int $octets): string
    {
        return $octets >= 1048576
            ? round($octets / 1048576) . ' Mo'
            : round($octets / 1024) . ' Ko';
    }
}
