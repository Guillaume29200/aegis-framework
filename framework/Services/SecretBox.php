<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Chiffrement générique de secrets (clés API, tokens...) au repos.
 * AES-256-CBC, clé 32 octets générée au premier appel et stockée hors webroot
 * dans framework/storage/secrets/.enckey (0600).
 *
 * decrypt() retourne null si le payload n'est pas un chiffré valide, ce qui
 * permet aux appelants de distinguer une valeur legacy en clair (migration
 * transparente : fallback sur la valeur brute).
 */
final class SecretBox
{
    private static function keyHex(): string
    {
        $dir = ROOT_PATH . '/framework/storage/secrets';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $kf = $dir . '/.enckey';
        if (is_file($kf)) {
            $k = trim((string) @file_get_contents($kf));
            if (strlen($k) === 64) {
                return $k;
            }
        }

        $k = bin2hex(random_bytes(32));
        @file_put_contents($kf, $k);
        @chmod($kf, 0600);

        return $k;
    }

    public static function encrypt(string $plain): string
    {
        $key = hex2bin(self::keyHex());
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $enc);
    }

    public static function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        }

        $key = hex2bin(self::keyHex());
        $iv  = substr($raw, 0, 16);
        $enc = substr($raw, 16);

        $plain = openssl_decrypt($enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? null : $plain;
    }
}
