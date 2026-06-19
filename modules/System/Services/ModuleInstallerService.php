<?php
declare(strict_types=1);

namespace System\Services;

/**
 * ModuleInstallerService — installation d'un module depuis une archive ZIP.
 *
 * Sécurité :
 *  - taille limitée, extension .zip, extension zip requise ;
 *  - protection « zip slip » (aucune entrée ne peut sortir du dossier cible) ;
 *  - validation du manifeste (module.json : name + class cohérents) ;
 *  - extraction dans un dossier temporaire, puis déplacement atomique vers
 *    modules/<Name> (sauvegarde de l'ancien dossier en cas de mise à jour).
 *
 * L'ACTIVATION n'est pas faite ici : elle reste pilotée par ModuleManager
 * (avec sa couche de vérification des tables).
 */
class ModuleInstallerService
{
    private const MAX_SIZE = 50 * 1024 * 1024; // 50 Mo

    /**
     * @return array{success:bool, message:string, module?:string, existed?:bool}
     */
    public function installFromUpload(array $file): array
    {
        if (!extension_loaded('zip')) {
            return ['success' => false, 'message' => "L'extension PHP « zip » est requise et n'est pas activée."];
        }
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => "Échec de l'upload (code {$err})."];
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['success' => false, 'message' => 'Fichier temporaire invalide.'];
        }
        if ((int)($file['size'] ?? 0) > self::MAX_SIZE) {
            return ['success' => false, 'message' => 'Archive trop volumineuse (max 50 Mo).'];
        }
        if (strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip') {
            return ['success' => false, 'message' => 'Le fichier doit être une archive .zip.'];
        }

        $workDir = ROOT_PATH . '/framework/cache/module_install_' . bin2hex(random_bytes(6));
        try {
            if (!@mkdir($workDir, 0755, true) && !is_dir($workDir)) {
                return ['success' => false, 'message' => 'Impossible de créer le dossier temporaire.'];
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) {
                return ['success' => false, 'message' => 'Archive ZIP illisible ou corrompue.'];
            }

            // Extraction sécurisée (anti zip-slip).
            $real = realpath($workDir);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false || $name === '') continue;
                if (str_contains($name, '..') || str_starts_with($name, '/') || str_starts_with($name, '\\')) {
                    $zip->close();
                    return ['success' => false, 'message' => "Entrée d'archive non sûre détectée : {$name}."];
                }
            }
            $zip->extractTo($workDir);
            $zip->close();

            // Localiser le module.json (racine ou sous-dossier unique).
            $manifest = $this->findManifest($workDir);
            if ($manifest === null) {
                return ['success' => false, 'message' => "Aucun module.json trouvé dans l'archive."];
            }
            $moduleRoot = dirname($manifest);

            $config = json_decode((string) file_get_contents($manifest), true);
            if (!is_array($config) || empty($config['name']) || empty($config['class'])) {
                return ['success' => false, 'message' => 'module.json invalide (name/class manquants).'];
            }
            $name = (string) $config['name'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                return ['success' => false, 'message' => "Nom de module invalide : « {$name} »."];
            }

            $target = ROOT_PATH . '/modules/' . $name;
            $existed = is_dir($target);

            // Sauvegarde de l'ancienne version (mise à jour).
            $backup = null;
            if ($existed) {
                $backup = $target . '.bak_' . date('YmdHis');
                if (!@rename($target, $backup)) {
                    return ['success' => false, 'message' => "Impossible de sauvegarder l'ancienne version du module."];
                }
            }

            if (!@rename($moduleRoot, $target)) {
                // Repli : copie récursive si rename inter-volumes échoue.
                if (!$this->copyDir($moduleRoot, $target)) {
                    if ($backup) { @rename($backup, $target); } // restaure
                    return ['success' => false, 'message' => "Impossible d'installer le module dans modules/{$name}."];
                }
            }

            // Mise à jour réussie : on purge la sauvegarde.
            if ($backup && is_dir($backup)) {
                $this->rrmdir($backup);
            }

            $verb = $existed ? 'mis à jour' : 'installé';
            return ['success' => true, 'message' => "Module « {$name} » {$verb}. Vous pouvez maintenant l'activer.", 'module' => $name, 'existed' => $existed];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        } finally {
            if (is_dir($workDir)) {
                $this->rrmdir($workDir);
            }
        }
    }

    /** Cherche le module.json le moins profond dans l'arborescence extraite. */
    private function findManifest(string $dir): ?string
    {
        if (is_file($dir . '/module.json')) {
            return $dir . '/module.json';
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        $best = null; $bestDepth = PHP_INT_MAX;
        foreach ($it as $f) {
            if ($f->getFilename() === 'module.json') {
                $depth = substr_count(str_replace('\\', '/', $f->getPathname()), '/');
                if ($depth < $bestDepth) { $bestDepth = $depth; $best = $f->getPathname(); }
            }
        }
        return $best;
    }

    private function copyDir(string $src, string $dst): bool
    {
        if (!is_dir($dst) && !@mkdir($dst, 0755, true)) return false;
        foreach (scandir($src) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $s = $src . '/' . $item; $d = $dst . '/' . $item;
            if (is_dir($s)) { if (!$this->copyDir($s, $d)) return false; }
            else { if (!@copy($s, $d)) return false; }
        }
        return true;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . '/' . $item;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
