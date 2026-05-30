<?php
declare(strict_types=1);

namespace Framework\Security;

/**
 * Service XSS Protection
 * 
 * PHP 8.4+ Compatible
 * Protection contre attaques Cross-Site Scripting
 * 
 * Fonctionnalités:
 * - Filtrage automatique des superglobales
 * - Nettoyage HTML avec whitelist
 * - Échappement contextuels (HTML, JS, URL)
 * - Détection tentatives XSS
 */
class XSSProtection
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config['xss'];
    }
    
    /**
     * Filtrer toutes les superglobales au démarrage
     */
    public function filterGlobals(): void
    {
        if (!$this->config['enabled']) {
            return;
        }
        
        if ($this->config['filter_get']) {
            $_GET = $this->cleanArray($_GET);
        }
        
        if ($this->config['filter_post']) {
            $_POST = $this->cleanArray($_POST);
        }
        
        if ($this->config['filter_cookie']) {
            $_COOKIE = $this->cleanArray($_COOKIE);
        }
    }
    
    /**
     * Nettoyer une chaîne
     * 
     * @param string $data Données à nettoyer
     * @param bool $allowHtml Autoriser HTML (avec whitelist)
     * @return string Données nettoyées
     */
    public function clean(string $data, bool $allowHtml = false): string
    {
        if (!$allowHtml) {
            // Supprimer TOUS les tags HTML
            return strip_tags($data);
        }
        
        // Autoriser certains tags seulement
        return strip_tags($data, $this->config['allowed_tags']);
    }
    
    /**
     * Nettoyer un tableau récursivement (PHP 8.4 optimisé)
     * 
     * @param array $data Données à nettoyer
     * @param bool $allowHtml Autoriser HTML
     * @return array Données nettoyées
     */
    public function cleanArray(array $data, bool $allowHtml = false): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            // Nettoyer la clé
            $cleanKey = $this->clean((string)$key);
            
            // Nettoyer la valeur selon type
            $cleaned[$cleanKey] = match(true) {
                is_array($value) => $this->cleanArray($value, $allowHtml),
                is_string($value) => $this->clean($value, $allowHtml),
                default => $value
            };
        }
        
        return $cleaned;
    }
    
    /**
     * Échapper pour affichage HTML
     * 
     * @param string $data Données à échapper
     * @return string Données échappées
     */
    public function escape(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Échapper pour attribut HTML
     * 
     * @param string $data Données à échapper
     * @return string Données échappées
     */
    public function escapeAttr(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Échapper pour JavaScript
     * 
     * @param mixed $data Données à échapper
     * @return string JSON échappé
     */
    public function escapeJs(mixed $data): string
    {
        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }
    
    /**
     * Échapper pour URL
     * 
     * @param string $url URL à échapper
     * @return string URL échappée
     */
    public function escapeUrl(string $url): string
    {
        // Valider protocole d'abord
        if (!$this->isSafeUrl($url)) {
            return '';
        }
        
        return htmlspecialchars(urlencode($url), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valider si URL est sûre (PHP 8.4 array_any)
     * 
     * @param string $url URL à valider
     * @return bool True si URL sûre
     */
    public function isSafeUrl(string $url): bool
    {
        // URL relative = OK
        if (!str_contains($url, ':')) {
            return true;
        }
        
        $parsed = parse_url($url);
        
        if (!isset($parsed['scheme'])) {
            return true; // URL relative
        }
        
        // Vérifier si protocole autorisé (PHP 8.4 style)
        return in_array(
            strtolower($parsed['scheme']),
            $this->config['allowed_protocols'],
            true
        );
    }
    
    /**
     * Nettoyer HTML avec whitelist de tags et attributs
     * 
     * @param string $html HTML à nettoyer
     * @return string HTML nettoyé
     */
    public function cleanHtml(string $html): string
    {
        // Strip tags non autorisés
        $html = strip_tags($html, $this->config['allowed_tags']);
        
        // Nettoyer attributs
        $html = $this->cleanAttributes($html);
        
        return $html;
    }
    
    /**
     * Nettoyer attributs HTML
     * 
     * @param string $html HTML à traiter
     * @return string HTML avec attributs nettoyés
     */
    private function cleanAttributes(string $html): string
    {
        // Parser HTML
        $dom = new \DOMDocument();
        @$dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        $xpath = new \DOMXPath($dom);
        
        // Pour chaque élément
        foreach ($xpath->query('//*') as $element) {
            $tagName = strtolower($element->tagName);
            $allowedAttrs = $this->config['allowed_attributes'][$tagName] ?? [];
            
            // Supprimer attributs non autorisés
            $attributes = [];
            foreach ($element->attributes as $attr) {
                $attributes[] = $attr->name;
            }
            
            foreach ($attributes as $attrName) {
                if (!in_array($attrName, $allowedAttrs, true)) {
                    $element->removeAttribute($attrName);
                }
            }
        }
        
        return $dom->saveHTML();
    }
    
    /**
     * Détecter tentative XSS (PHP 8.4 array_any)
     * 
     * @param string $data Données à analyser
     * @return bool True si XSS détecté
     */
    public function detectXSS(string $data): bool
    {
        $dataLower = strtolower($data);
        
        // Patterns dangereux
        $dangerousPatterns = [
            '<script',
            'javascript:',
            'onerror=',
            'onload=',
            'onclick=',
            'onmouseover=',
            '<iframe',
            'eval(',
            'expression(',
            'vbscript:',
            'data:text/html',
        ];
        
        // PHP 8.4: array_any()
        return array_any(
            $dangerousPatterns,
            fn($pattern) => str_contains($dataLower, $pattern)
        );
    }
    
    /**
     * Sanitize filename (pour uploads)
     * 
     * @param string $filename Nom de fichier
     * @return string Nom de fichier sécurisé
     */
    public function sanitizeFilename(string $filename): string
    {
        // Garder seulement caractères alphanumériques + . - _
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Éviter double extensions (.php.txt)
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limiter longueur
        $filename = mb_substr($filename, 0, 200);
        
        return $filename;
    }
    
    /**
     * Nettoyer input utilisateur pour recherche SQL LIKE
     * 
     * @param string $input Input utilisateur
     * @return string Input échappé pour LIKE
     */
    public function escapeLikeInput(string $input): string
    {
        // Échapper caractères spéciaux LIKE
        return addcslashes($input, '%_\\');
    }
    
    /**
     * Valider et nettoyer email
     * 
     * @param string $email Email à valider
     * @return string|null Email nettoyé ou null si invalide
     */
    public function sanitizeEmail(string $email): ?string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
    
    /**
     * Nettoyer input pour inclusion dans commande shell
     * ATTENTION: Préférer toujours escapeshellarg() natif
     * 
     * @param string $input Input utilisateur
     * @return string Input échappé
     */
    public function escapeShellInput(string $input): string
    {
        return escapeshellarg($input);
    }
}
