<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * ImageOptimizer — optimisation d'images bitmap (redimensionnement + recompression).
 *
 * Partagé par tous les uploads (SEO, configuration, …). Sûr par conception :
 *  - ne touche jamais aux SVG / ICO / GIF (animations préservées) ;
 *  - écrit dans un fichier temporaire et ne remplace l'original que si le
 *    résultat est plus léger (ou si un redimensionnement a eu lieu) ;
 *  - n'échoue jamais bruyamment (un upload ne doit pas casser à cause de ça).
 */
class ImageOptimizer
{
    public static function isAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Optimise une image en place.
     *
     * @return array{optimized:bool, before:int, after:int, width:int, resized:bool}
     */
    public static function optimize(string $path, int $maxWidth = 1920, int $quality = 82): array
    {
        $res = ['optimized' => false, 'before' => 0, 'after' => 0, 'width' => 0, 'resized' => false];

        if (!is_file($path) || !self::isAvailable()) {
            return $res;
        }

        $before = (int) @filesize($path);
        $res['before'] = $res['after'] = $before;

        try {
            $info = @getimagesize($path);
            if ($info === false) {
                return $res;
            }
            [$w, $h] = $info;
            $type = $info[2];
            $res['width'] = (int) $w;

            $quality  = max(40, min(100, $quality));
            $maxWidth = max(64, $maxWidth);

            switch ($type) {
                case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($path); break;
                case IMAGETYPE_PNG:  $img = @imagecreatefrompng($path); break;
                case IMAGETYPE_WEBP: $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null; break;
                default: return $res; // GIF (animations), BMP, ICO, etc. : on ne touche pas
            }
            if (!$img) {
                return $res;
            }

            // Redimensionnement si plus large que la limite.
            $resized = false;
            $dst = $img;
            if ($w > $maxWidth) {
                $nh  = max(1, (int) round($h * ($maxWidth / $w)));
                $tmp = imagecreatetruecolor($maxWidth, $nh);
                if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                    imagealphablending($tmp, false);
                    imagesavealpha($tmp, true);
                }
                imagecopyresampled($tmp, $img, 0, 0, 0, 0, $maxWidth, $nh, (int)$w, (int)$h);
                imagedestroy($img);
                $dst = $tmp;
                $resized = true;
            }

            // Encodage vers un fichier temporaire.
            $tmpFile = $path . '.opt';
            $ok = false;
            switch ($type) {
                case IMAGETYPE_JPEG: $ok = imagejpeg($dst, $tmpFile, $quality); break;
                case IMAGETYPE_PNG:  $ok = imagepng($dst, $tmpFile, 8); break; // 0-9 (compression)
                case IMAGETYPE_WEBP: $ok = function_exists('imagewebp') ? imagewebp($dst, $tmpFile, $quality) : false; break;
            }
            imagedestroy($dst);

            if (!$ok || !is_file($tmpFile)) {
                @unlink($tmpFile);
                return $res;
            }

            $newSize = (int) @filesize($tmpFile);
            // On ne remplace que si on a redimensionné OU si c'est plus léger.
            if ($resized || ($newSize > 0 && $newSize < $before)) {
                if (@rename($tmpFile, $path)) {
                    clearstatcache(true, $path);
                    $res['after']     = (int) @filesize($path);
                    $res['optimized'] = true;
                    $res['resized']   = $resized;
                    return $res;
                }
            }
            @unlink($tmpFile);
        } catch (\Throwable $e) {
            error_log('[ImageOptimizer] ' . $e->getMessage());
        }

        return $res;
    }
}
