<?php
declare(strict_types=1);

namespace System\Services;

use Framework\Services\Database;
use Framework\ModuleManager\ModuleManager;

/**
 * DiagnosticService — contrôle de santé de l'installation (Aegis).
 *
 * Détecte les incohérences les plus courantes : module actif sans ses tables,
 * version module.json ≠ base (migrations en attente), tables cœur manquantes,
 * dossiers non inscriptibles, dossier /install encore présent.
 *
 * Chaque contrôle renvoie : group, label, status (ok|warn|error), detail,
 * et éventuellement une action de réparation (fix + cible).
 */
class DiagnosticService
{
    private Database $db;
    private ModuleManager $modules;

    public function __construct(Database $db, ModuleManager $modules)
    {
        $this->db = $db;
        $this->modules = $modules;
    }

    /** @return array<int,array<string,mixed>> */
    public function run(): array
    {
        return array_merge(
            $this->checkCoreTables(),
            $this->checkModules(),
            $this->checkWritableDirs(),
            $this->checkInstallDir()
        );
    }

    /** Synthèse : compte par statut. */
    public function summary(array $checks): array
    {
        $s = ['ok' => 0, 'warn' => 0, 'error' => 0];
        foreach ($checks as $c) {
            $s[$c['status']] = ($s[$c['status']] ?? 0) + 1;
        }
        return $s;
    }

    // ── Contrôles ───────────────────────────────────────────────────────────

    private function checkCoreTables(): array
    {
        $out = [];
        foreach (['users', 'settings', 'modules'] as $t) {
            $exists = $this->tableExists($t);
            $out[] = [
                'group'  => 'Base de données',
                'label'  => "Table cœur « {$t} »",
                'status' => $exists ? 'ok' : 'error',
                'detail' => $exists ? 'Présente.' : 'MANQUANTE — le CMS ne peut pas fonctionner correctement.',
            ];
        }
        return $out;
    }

    private function checkModules(): array
    {
        $out = [];
        try {
            $rows = $this->db->query("SELECT name, active, version FROM modules ORDER BY name");
        } catch (\Throwable $e) {
            return [['group' => 'Modules', 'label' => 'Lecture de la table modules', 'status' => 'error', 'detail' => $e->getMessage()]];
        }

        foreach ($rows as $row) {
            $name = $row['name'];
            $active = (int)$row['active'] === 1;
            $modulePath = ROOT_PATH . '/modules/' . $name;

            // Module actif mais dossier absent
            if ($active && !is_dir($modulePath)) {
                $out[] = ['group' => 'Modules', 'label' => "Module « {$name} »", 'status' => 'error',
                    'detail' => "Actif en base mais le dossier modules/{$name} est introuvable.",
                    'fix' => 'disable', 'target' => $name];
                continue;
            }
            if (!$active) {
                continue; // on ne contrôle que les modules actifs
            }

            // Tables manquantes (déclarées dans le SQL mais absentes)
            $missing = $this->modules->missingTables($name);
            if (!empty($missing)) {
                $out[] = ['group' => 'Modules', 'label' => "Module « {$name} » — tables", 'status' => 'error',
                    'detail' => 'Actif mais tables manquantes : ' . implode(', ', $missing) . '.',
                    'fix' => 'reinstall', 'target' => $name];
            } else {
                $out[] = ['group' => 'Modules', 'label' => "Module « {$name} » — tables", 'status' => 'ok',
                    'detail' => 'Toutes les tables déclarées sont présentes.'];
            }

            // Version module.json vs base + migrations en attente
            $cfgFile = $modulePath . '/module.json';
            $cfgVersion = is_file($cfgFile) ? (string)((json_decode((string)file_get_contents($cfgFile), true) ?: [])['version'] ?? '') : '';
            $pending = $this->modules->pendingMigrationCount($name);
            if ($pending > 0 || ($cfgVersion !== '' && $cfgVersion !== (string)$row['version'])) {
                $out[] = ['group' => 'Modules', 'label' => "Module « {$name} » — version", 'status' => 'warn',
                    'detail' => "Mise à jour disponible : base v" . ($row['version'] ?: '?') . " → fichiers v{$cfgVersion}"
                              . ($pending > 0 ? " ({$pending} migration(s) en attente)" : '') . '.',
                    'fix' => 'update', 'target' => $name];
            }
        }
        return $out;
    }

    private function checkWritableDirs(): array
    {
        $dirs = [
            'framework/uploads' => ROOT_PATH . '/framework/uploads',
            'framework/logs'    => ROOT_PATH . '/framework/logs',
            'racine (sitemap/robots)' => ROOT_PATH,
        ];
        $out = [];
        foreach ($dirs as $label => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $out[] = [
                'group'  => 'Système de fichiers',
                'label'  => "Écriture : {$label}",
                'status' => $writable ? 'ok' : ($exists ? 'warn' : 'warn'),
                'detail' => $writable ? 'Inscriptible.' : ($exists ? 'NON inscriptible — corrigez les permissions.' : 'Dossier absent (sera créé au besoin).'),
            ];
        }
        return $out;
    }

    private function checkInstallDir(): array
    {
        $present = is_dir(ROOT_PATH . '/install');
        return [[
            'group'  => 'Sécurité',
            'label'  => 'Dossier /install',
            'status' => $present ? 'warn' : 'ok',
            'detail' => $present ? "Toujours présent — à supprimer après l'installation." : 'Absent (bien).',
            'fix'    => $present ? 'remove_install' : null,
            'target' => 'install',
        ]];
    }

    // ── Réparations ───────────────────────────────────────────────────────────

    /** @return array{success:bool, message:string} */
    public function repair(string $fix, string $target): array
    {
        switch ($fix) {
            case 'reinstall':
                $ok = $this->modules->activateModule($target);
                return ['success' => $ok, 'message' => $ok
                    ? "Module « {$target} » réinstallé (tables recréées)."
                    : "Échec réinstallation : " . ($this->modules->getLastError() ?? 'inconnue')];

            case 'update':
                $r = $this->modules->updateModule($target);
                return ['success' => $r['success'], 'message' => $r['message']];

            case 'disable':
                $ok = $this->modules->deactivateModule($target);
                return ['success' => $ok, 'message' => $ok ? "Module « {$target} » désactivé." : 'Échec de la désactivation.'];

            case 'remove_install':
                $ok = $this->rrmdir(ROOT_PATH . '/install');
                return ['success' => $ok, 'message' => $ok ? 'Dossier /install supprimé.' : 'Suppression impossible (permissions).'];

            default:
                return ['success' => false, 'message' => 'Action de réparation inconnue.'];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return (bool) $this->db->queryOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
                [$table]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function rrmdir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        return @rmdir($dir);
    }
}
