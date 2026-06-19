<?php
/**
 * CacheService - Gestion du cache fichier
 */

namespace Framework\Services;

class CacheService
{
    private bool $enabled;
    private int $ttl;
    private string $cacheDir;
    
    public function __construct(array $config)
    {
        $this->enabled = $config['cache_enabled'] ?? true;
        $this->ttl = $config['cache_ttl'] ?? 3600;
        $this->cacheDir = $config['cache_dir'] ?? ROOT_PATH . '/framework/cache';
        
        // Créer le répertoire si nécessaire
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Récupérer une valeur du cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->enabled) {
            return $default;
        }
        
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }
        
        $data = @unserialize($content, ["allowed_classes" => false]);
        if ($data === false) {
            return $default;
        }
        
        // Vérifier expiration
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'] ?? $default;
    }
    
    /**
     * Enregistrer une valeur dans le cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->ttl;
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        $serialized = serialize($data);
        
        // Créer le sous-dossier si nécessaire
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return @file_put_contents($file, $serialized, LOCK_EX) !== false;
    }
    
    /**
     * Vérifier si une clé existe
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        // Vérifier si pas expiré
        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }
        
        $data = @unserialize($content, ["allowed_classes" => false]);
        if ($data === false) {
            return false;
        }
        
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Supprimer une clé du cache
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }
    
    /**
     * Vider tout le cache
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        return $this->deleteDirectory($this->cacheDir);
    }
    
    /**
     * Remember : récupère du cache OU exécute callback et met en cache
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Obtenir le chemin du fichier cache
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        $subdir = substr($hash, 0, 2); // Premier 2 caractères pour sous-dossier
        
        return $this->cacheDir . '/' . $subdir . '/' . $hash . '.cache';
    }
    
    /**
     * Supprimer récursivement un répertoire
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        // Ne pas supprimer le dossier racine, juste son contenu
        if ($dir !== $this->cacheDir) {
            return @rmdir($dir);
        }
        
        return true;
    }
    
    /**
     * Nettoyer les entrées expirées
     */
    public function cleanExpired(): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'cache') {
                $content = @file_get_contents($file->getPathname());
                if ($content !== false) {
                    $data = @unserialize($content, ["allowed_classes" => false]);
                    if ($data !== false && isset($data['expires_at']) && $data['expires_at'] < time()) {
                        if (@unlink($file->getPathname())) {
                            $count++;
                        }
                    }
                }
            }
        }
        
        return $count;
    }
}