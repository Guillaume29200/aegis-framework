<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Services\CountryFirewall;
use Framework\Services\SecurityFirewallService;

/**
 * Refuse l'accès aux visiteurs venant d'un pays non autorisé.
 *
 * Placé juste après le pare-feu IP, dont il réutilise deux acquis : la
 * résolution de l'adresse réelle du client — celle qui tient compte de
 * Cloudflare et des relais — et la liste blanche des adresses de confiance.
 * Écrire une seconde résolution d'adresse aurait garanti qu'elles finissent
 * par diverger, et une divergence ici veut dire « on bloque le mauvais
 * visiteur ».
 *
 * Le coût par requête se résume à une recherche dans un fichier local
 * (~0,13 ms) : aucune requête SQL, aucun appel réseau.
 */
final class CountryBlock
{
    private CountryFirewall $filtre;
    private SecurityFirewallService $pareFeu;

    public function __construct(CountryFirewall $filtre, SecurityFirewallService $pareFeu)
    {
        $this->filtre  = $filtre;
        $this->pareFeu = $pareFeu;
    }

    public function handle(): void
    {
        if (!$this->filtre->actif()) {
            return;
        }

        // Un administrateur connecté n'est jamais refoulé : c'est ce qui rend
        // une erreur de réglage réparable depuis l'interface plutôt que depuis
        // la base de données.
        if (!empty($_SESSION['logged_in'])
            && in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
            return;
        }

        $ip = $this->pareFeu->getClientIp();

        if ($this->pareFeu->isWhitelisted($ip)) {
            return;
        }

        $pays = $this->filtre->refus($ip);
        if ($pays === null) {
            return;
        }

        $this->refuser($pays);
    }

    /**
     * Rend la page de refus et interrompt la requête.
     *
     * En anglais : le visiteur ne parle pas nécessairement français, et cette
     * page est la seule chose qu'il verra du site.
     *
     * 403 et non 404 : la ressource existe, l'accès est refusé. Un `Retry-After`
     * n'aurait aucun sens — revenir plus tard ne changera pas le pays.
     */
    private function refuser(string $pays): void
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            // Une décision géographique ne doit jamais être mise en cache par
            // un intermédiaire : elle vaut pour ce visiteur, pas pour l'URL.
            header('Cache-Control: no-store, private');
            header('X-Robots-Tag: noindex');
        }

        $vue = dirname(__DIR__) . '/Views/errors/country-blocked.php';

        if (is_file($vue)) {
            $countryCode = $pays;
            // En anglais, comme le reste de la page : afficher « Russie » sur
            // une page rédigée en anglais serait incohérent pour le lecteur.
            $countryName = CountryFirewall::nomPays($pays, 'en');
            require $vue;
        } else {
            echo '<!doctype html><meta charset="utf-8"><title>Access denied</title>'
               . '<p>Access from your country is not permitted.</p>';
        }

        exit;
    }
}
