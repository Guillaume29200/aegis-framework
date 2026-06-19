<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;

/**
 * SeoService — SEO, médias (logo / favicon / Open Graph) et analytics.
 * Service dédié (séparé de Configuration), gère lecture, sauvegarde et uploads.
 */
class SeoService
{
    private SettingsService $settings;
    private ImageSettingsService $imageSettings;

    /** Extensions autorisées par type d'asset. */
    private const ALLOWED = [
        'logo'    => ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'],
        'favicon' => ['svg', 'png', 'ico'],
        'og'      => ['png', 'jpg', 'jpeg', 'webp'],
    ];

    public function __construct(Database $db)
    {
        $this->settings = new SettingsService($db);
        $this->imageSettings = new ImageSettingsService($db);
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        return [
            'logo_url'         => (string) $this->settings->get('logo_url', ''),
            'favicon_url'      => (string) $this->settings->get('favicon_url', ''),
            'og_image'         => (string) $this->settings->get('seo_og_image', ''),
            'meta_title'       => (string) $this->settings->get('meta_title_template', '{page_title} - {site_name}'),
            'meta_description' => (string) $this->settings->get('meta_description_default', ''),
            'meta_keywords'    => (string) $this->settings->get('meta_keywords_default', ''),
            'robots'           => (string) $this->settings->get('seo_robots', 'index,follow'),
            'ga_id'            => (string) $this->settings->get('seo_ga_id', ''),
        ];
    }

    /**
     * Enregistrer depuis $_POST / $_FILES.
     *
     * @return array{success:bool,message:string}
     */
    public function save(array $post, array $files): array
    {
        try {
            $updates = [
                'meta_title_template'      => ['value' => trim((string)($post['meta_title_template'] ?? '')) ?: '{page_title} - {site_name}', 'type' => 'string'],
                'meta_description_default' => ['value' => trim((string)($post['meta_description_default'] ?? '')), 'type' => 'string'],
                'meta_keywords_default'    => ['value' => trim((string)($post['meta_keywords_default'] ?? '')), 'type' => 'string'],
                'seo_robots'               => ['value' => in_array($post['seo_robots'] ?? '', ['index,follow','noindex,follow','index,nofollow','noindex,nofollow'], true) ? $post['seo_robots'] : 'index,follow', 'type' => 'string'],
                'seo_ga_id'                => ['value' => trim((string)($post['seo_ga_id'] ?? '')), 'type' => 'string'],
            ];

            // Uploads + suppressions
            foreach (['logo' => 'logo_url', 'favicon' => 'favicon_url', 'og' => 'seo_og_image'] as $field => $key) {
                if (!empty($post['remove_' . $field])) {
                    $updates[$key] = ['value' => '', 'type' => 'string'];
                    continue;
                }
                $uploaded = $this->upload($field . '_file', $field, $files);
                if ($uploaded !== null) {
                    $updates[$key] = ['value' => $uploaded, 'type' => 'string'];
                }
            }

            if ($this->settings->setMultiple($updates)) {
                return ['success' => true, 'message' => 'Paramètres SEO & médias enregistrés.'];
            }
            return ['success' => false, 'message' => 'Erreur lors de la sauvegarde.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Upload sécurisé d'un asset image. Retourne l'URL ou null si aucun fichier. */
    private function upload(string $fieldName, string $type, array $files): ?string
    {
        if (!isset($files[$fieldName]) || !is_array($files[$fieldName])) {
            return null;
        }
        $file = $files[$fieldName];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Échec de l'upload ({$type}).");
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Fichier temporaire invalide.');
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException("L'image ({$type}) ne doit pas dépasser 5 Mo.");
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowed = self::ALLOWED[$type] ?? [];
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException("Format {$type} non autorisé. Acceptés : " . implode(', ', $allowed) . '.');
        }

        // Validation du contenu (souple pour svg/ico, stricte pour bitmaps)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $bitmap = ['png' => ['image/png','image/x-png'], 'jpg' => ['image/jpeg','image/pjpeg'], 'jpeg' => ['image/jpeg','image/pjpeg'], 'webp' => ['image/webp'], 'gif' => ['image/gif']];
        if (isset($bitmap[$ext]) && !in_array($mime, $bitmap[$ext], true)) {
            throw new \RuntimeException("Le contenu du fichier ne correspond pas à une image {$ext} valide.");
        }
        // svg : doit contenir <svg ; ico : laissé passer (type binaire variable)
        if ($ext === 'svg') {
            $head = (string)@file_get_contents($tmp, false, null, 0, 512);
            if (stripos($head, '<svg') === false) {
                throw new \RuntimeException('Le fichier SVG est invalide.');
            }
        }

        $dir = ROOT_PATH . '/framework/uploads';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException('Dossier uploads inaccessible.');
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException("Dossier uploads non accessible en écriture.");
        }

        $filename = sprintf('%s-%s-%s.%s', $type, date('Ymd-His'), bin2hex(random_bytes(6)), $ext);
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException("Impossible d'enregistrer le fichier {$type}.");
        }

        // Optimisation (service dédié : raster uniquement, jamais SVG/ICO).
        $this->imageSettings->optimize($dest, $ext);

        return '/framework/uploads/' . $filename;
    }
}
