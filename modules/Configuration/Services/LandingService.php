<?php
declare(strict_types=1);

namespace Configuration\Services;

use Framework\Services\Database;
use Framework\Services\PublicPrefix;

/**
 * LandingService — liste les modules ACTIFS qui exposent une interface publique,
 * pour le réglage « Page d'accueil par défaut » de la Configuration.
 *
 * Deux déclarations sont reconnues dans module.json :
 *   — "public": {"prefix": "…", "label": "…"}  (actuelle, sert aussi au
 *     renommage du préfixe public depuis /admin/modules) ;
 *   — "public_home": {"url": "…", "label": "…"} (historique).
 *
 * La valeur enregistrée est toujours le chemin **canonique** du module. Elle
 * survit ainsi à un renommage du préfixe : c'est url(), au moment de la
 * redirection, qui la traduit vers l'adresse réellement en vigueur.
 */
class LandingService
{
    public function __construct(private Database $db) {}

    /**
     * @return array<int,array{value:string,label:string,url:string}>
     *         value = chemin canonique enregistré, url = adresse effective affichée
     */
    public function options(): array
    {
        $modulesDir = dirname(__DIR__, 2); // …/modules
        $out = [];

        try {
            $rows = $this->db->query('SELECT name FROM modules WHERE active = 1 ORDER BY name ASC') ?: [];
        } catch (\Throwable) {
            $rows = [];
        }

        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? '');
            $file = $modulesDir . '/' . $name . '/module.json';
            if ($name === '' || !is_file($file)) { continue; }

            $cfg = json_decode((string) @file_get_contents($file), true);
            if (!is_array($cfg)) { continue; }

            $option = $this->fromPublicBlock($name, $cfg) ?? $this->fromLegacyBlock($name, $cfg);
            if ($option !== null) { $out[] = $option; }
        }

        return $out;
    }

    /** Déclaration actuelle : le bloc "public" du manifeste. */
    private function fromPublicBlock(string $name, array $cfg): ?array
    {
        $canonical = PublicPrefix::normalize((string) ($cfg['public']['prefix'] ?? ''));
        if ($canonical === '') { return null; }

        $effective = PublicPrefix::effective($name) ?: $canonical;
        $label     = trim((string) ($cfg['public']['label'] ?? ''));

        return [
            'value' => '/' . $canonical,
            'label' => $label === '' ? $name : $name . ' — ' . $label,
            'url'   => '/' . $effective,
        ];
    }

    /** Déclaration historique, conservée pour les modules qui n'ont pas migré. */
    private function fromLegacyBlock(string $name, array $cfg): ?array
    {
        $home = $cfg['public_home'] ?? null;
        if (!is_array($home) || empty($home['url'])) { return null; }

        $url = '/' . ltrim((string) $home['url'], '/');

        return [
            'value' => $url,
            'label' => (string) ($home['label'] ?? $name),
            'url'   => $url,
        ];
    }

    /**
     * Valeurs autorisées pour le réglage.
     *
     * Les adresses effectives sont admises en plus des canoniques : un réglage
     * enregistré avant un renommage reste valide au lieu d'être silencieusement
     * remis à zéro.
     */
    public function allowedValues(): array
    {
        $vals = ['', 'auth'];
        foreach ($this->options() as $o) {
            $vals[] = $o['value'];
            if ($o['url'] !== $o['value']) { $vals[] = $o['url']; }
        }
        return array_values(array_unique($vals));
    }
}
