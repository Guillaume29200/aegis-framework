<?php
declare(strict_types=1);

namespace Framework\Templating;

use Framework\Services\Database;

/**
 * Petit magasin clé/valeur adossé à la table de réglages d'un module.
 *
 * Le gestionnaire de thèmes a besoin de retenir trois fois rien : le thème
 * actif, et la valeur de chaque option de chaque thème. Plutôt que d'imposer
 * une table à lui — une de plus dans la base à chaque module — il se branche
 * sur celle que le module possède déjà, dont on lui donne le nom et les deux
 * colonnes.
 *
 * Les lectures sont mémorisées pour la durée de la requête : une page de
 * thème lit facilement cinquante options, ce n'est pas cinquante allers-retours.
 */
final class ThemeSettings implements SettingsStore
{
    /** @var array<string,string>|null Cache de requête ; null tant qu'on n'a rien lu. */
    private ?array $cache = null;

    public function __construct(
        private Database $db,
        private string $table,
        private string $keyColumn = 'setting_key',
        private string $valueColumn = 'setting_value'
    ) {
    }

    public function get(string $key, string $default = ''): string
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /** @return array<string,string> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = [];
        try {
            $rows = $this->db->query(
                "SELECT `{$this->keyColumn}` AS k, `{$this->valueColumn}` AS v FROM `{$this->table}`"
            ) ?: [];
            foreach ($rows as $r) {
                $this->cache[(string) $r['k']] = (string) ($r['v'] ?? '');
            }
        } catch (\Throwable $e) {
            // Table absente — le module n'est pas encore installé. Un thème doit
            // pouvoir se rendre avec ses valeurs par défaut plutôt que d'échouer.
        }

        return $this->cache;
    }

    public function set(string $key, string $value): void
    {
        try {
            $this->db->execute(
                "INSERT INTO `{$this->table}` (`{$this->keyColumn}`, `{$this->valueColumn}`)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `{$this->valueColumn}` = VALUES(`{$this->valueColumn}`)",
                [$key, $value]
            );
            if ($this->cache !== null) {
                $this->cache[$key] = $value;
            }
        } catch (\Throwable $e) {
            // Écriture impossible : on ne casse pas la page d'administration
            // pour autant, l'appelant verra que la valeur n'a pas bougé.
        }
    }

    /** Supprime toutes les clés commençant par un préfixe (les options d'un thème). */
    public function forget(string $prefix): void
    {
        try {
            $this->db->execute(
                "DELETE FROM `{$this->table}` WHERE `{$this->keyColumn}` LIKE ?",
                [$prefix . '%']
            );
            $this->cache = null;
        } catch (\Throwable $e) {
            // idem
        }
    }
}
