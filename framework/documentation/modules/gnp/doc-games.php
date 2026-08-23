<?php
$docPage = 'modules/gnp/doc-games.php';
$seo = ['title' => 'Ajouter un jeu — Documentation · GameNodePanel', 'desc' => "Ajouter un jeu à GameNodePanel de deux façons : via le formulaire d'administration, ou via l'import d'un fichier JSON (format d'échange GNP). Structure du JSON expliquée.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-games.php'];
require __DIR__ . '/../../inc/head.php';

$json_full = <<<'JSON'
{
    "export_format_version": "1.0",
    "exported_at": "2026-06-05T00:24:08+02:00",
    "game_count": 1,
    "games": [
        {
            "id": 1,
            "name": "Counter-Strike 1.6",
            "viewer_id": "cs16",
            "viewer_type": "gnpq",
            "category": "fps",
            "default_port": 27015,
            "query_port": 27015,
            "max_slots": 32,
            "default_map": "de_dust2",
            "os_type": "linux",
            "status": "active",
            "description": "Counter-Strike … jeu de tir à la première personne multijoueur.",
            "versions": [
                {
                    "version_name": "Latest",
                    "install_type": "steam",
                    "game_runtime_type": "classic",
                    "docker_image": null,
                    "docker_network_mode": "bridge",
                    "docker_env_vars": null,
                    "docker_mount_path": null,
                    "docker_workdir": null,
                    "docker_seed_path": null,
                    "steam_app_id": "90",
                    "steam_extra_args": null,
                    "steam_login_required": 0,
                    "steam_guard_required": 0,
                    "download_url": "",
                    "git_repository": "",
                    "install_path": "/home/mods/cs16",
                    "startup_dir": "",
                    "config_dir": "cstrike",
                    "logs_dir": "",
                    "plugin_dir": null,
                    "workshop_compatible": 0,
                    "requires_gslt": 0,
                    "startup_command": "./hlds_run -game cstrike +ip {ip} +port {port} +maxplayers {slots} +map {map} -sys_tickrate {cfg1} -pingboost {cfg2}",
                    "install_notes": "",
                    "is_default": 1,
                    "status": "active",
                    "configs": [
                        { "config_key": "cfg1", "config_name": "Tickrate",  "config_description": "1000" },
                        { "config_key": "cfg2", "config_name": "Pingboost", "config_description": "3" }
                    ]
                }
            ]
        }
    ]
}
JSON;
?>
    <h1>Ajouter un jeu</h1>
    <p class="doc-lead">GameNodePanel permet d'ajouter un jeu de <strong>deux manières</strong> : via le <strong>formulaire</strong> d'administration, ou en <strong>important un fichier JSON</strong> au format d'échange GNP. Les deux produisent exactement la même chose.</p>
    <div class="doc-meta"><span class="doc-pill">/admin/gamenodepanel/games/create</span><span class="doc-pill">/games/import-export</span></div>

    <h2 id="games-intro">Deux méthodes</h2>
    <table class="doc-table">
      <tr><th>Méthode</th><th>Idéale pour</th></tr>
      <tr><td><strong>Formulaire</strong></td><td>Créer un jeu à la main, pas à pas, avec upload d'icône/bannière.</td></tr>
      <tr><td><strong>Import JSON</strong></td><td>Réutiliser/partager une config complète entre administrateurs GNP (ports, versions, commandes…).</td></tr>
    </table>

    <h2 id="games-form">Via le formulaire</h2>
    <p>Depuis <code>Jeux &amp; mods → Ajouter un jeu</code>, renseignez la fiche du jeu :</p>
    <table class="doc-table">
      <tr><th>Champ</th><th>Description</th></tr>
      <tr><td><code>name</code></td><td>Nom affiché (ex. <em>Counter-Strike 1.6</em>).</td></tr>
      <tr><td><code>viewer_id</code></td><td>Identifiant technique court (ex. <code>cs16</code>) — sert au routage du panel.</td></tr>
      <tr><td><code>viewer_type</code></td><td>Type de viewer/panel (ex. <code>gnpq</code>, docker, standard…).</td></tr>
      <tr><td><code>category</code></td><td>Catégorie (ex. <code>fps</code>, survie, sandbox…).</td></tr>
      <tr><td><code>status</code></td><td><code>active</code> / inactif.</td></tr>
      <tr><td><code>default_port</code> · <code>query_port</code></td><td>Port de jeu et port de requête (ex. 27015).</td></tr>
      <tr><td><code>max_slots</code></td><td>Nombre de joueurs max.</td></tr>
      <tr><td><code>default_map</code></td><td>Map de départ (ex. <code>de_dust2</code>).</td></tr>
      <tr><td><code>os_type</code></td><td>Système cible (ex. <code>linux</code>).</td></tr>
      <tr><td><code>description</code></td><td>Présentation du jeu.</td></tr>
      <tr><td><code>icon_file</code> · <code>banner_file</code></td><td>Icône et bannière (upload d'images).</td></tr>
    </table>

    <h2 id="games-versions">Versions &amp; configurations</h2>
    <p>Un jeu possède une ou plusieurs <strong>versions</strong> (ex. « Latest »), chacune décrivant <em>comment installer et démarrer</em> : type d'installation (Steam, Docker, manuel), chemins, et <strong>commande de démarrage</strong> avec des <strong>placeholders</strong> remplacés à l'exécution :</p>
    <ul>
      <li><code>{ip}</code>, <code>{port}</code>, <code>{slots}</code>, <code>{map}</code> — valeurs du serveur.</li>
      <li><code>{cfg1}</code>, <code>{cfg2}</code>… — options personnalisées définies dans <code>configs</code> (ex. Tickrate, Pingboost).</li>
    </ul>
    <div class="callout"><span class="i">🧩</span><div>Les <code>configs</code> transforment la commande en formulaire : chaque <code>config_key</code> (<code>cfg1</code>…) devient un champ paramétrable côté admin, injecté dans <code>startup_command</code>.</div></div>

    <h2 id="games-json">Via JSON (import)</h2>
    <p>Le format d'échange GNP permet de décrire un (ou plusieurs) jeu(x) dans un fichier <code>.json</code>, puis de l'importer. C'est exactement ce que produit l'export. Exemple complet (Counter-Strike 1.6) :</p>
    <pre><code><?= $h($json_full) ?></code></pre>

    <h2 id="games-json-struct">Structure du JSON</h2>
    <p><strong>Niveau racine</strong></p>
    <table class="doc-table">
      <tr><th>Clé</th><th>Rôle</th></tr>
      <tr><td><code>export_format_version</code></td><td>Version du format (actuellement <code>1.0</code>).</td></tr>
      <tr><td><code>exported_at</code></td><td>Date ISO de l'export (informatif).</td></tr>
      <tr><td><code>game_count</code></td><td>Nombre de jeux dans le fichier.</td></tr>
      <tr><td><code>games[]</code></td><td>Tableau des jeux.</td></tr>
    </table>
    <p><strong>Un jeu</strong> reprend les champs du formulaire (<code>name</code>, <code>viewer_id</code>, <code>category</code>, <code>default_port</code>, <code>max_slots</code>, <code>os_type</code>, <code>status</code>, <code>description</code>…) + un tableau <code>versions[]</code>.</p>
    <p><strong>Une version</strong> (<code>versions[]</code>)</p>
    <table class="doc-table">
      <tr><th>Clé</th><th>Rôle</th></tr>
      <tr><td><code>version_name</code></td><td>Nom de la version (ex. « Latest »).</td></tr>
      <tr><td><code>install_type</code></td><td><code>steam</code> · <code>docker</code> · <code>manual</code> (download/git).</td></tr>
      <tr><td><code>game_runtime_type</code></td><td>Runtime (ex. <code>classic</code>, docker…).</td></tr>
      <tr><td><code>steam_app_id</code></td><td>App ID Steam (ex. <code>90</code>) si <code>install_type=steam</code>.</td></tr>
      <tr><td><code>steam_login_required</code> · <code>steam_guard_required</code> · <code>requires_gslt</code></td><td>0/1 — exigences Steam.</td></tr>
      <tr><td><code>docker_image</code> · <code>docker_network_mode</code> · <code>docker_env_vars</code> · <code>docker_mount_path</code>…</td><td>Paramètres Docker (si <code>install_type=docker</code>).</td></tr>
      <tr><td><code>download_url</code> · <code>git_repository</code></td><td>Source en installation manuelle.</td></tr>
      <tr><td><code>install_path</code> · <code>startup_dir</code> · <code>config_dir</code> · <code>logs_dir</code> · <code>plugin_dir</code></td><td>Chemins sur le serveur.</td></tr>
      <tr><td><code>startup_command</code></td><td>Commande de lancement, avec placeholders <code>{ip}</code>, <code>{port}</code>, <code>{slots}</code>, <code>{map}</code>, <code>{cfgN}</code>.</td></tr>
      <tr><td><code>is_default</code></td><td>0/1 — version par défaut.</td></tr>
      <tr><td><code>configs[]</code></td><td>Options personnalisées (<code>config_key</code>, <code>config_name</code>, <code>config_description</code>).</td></tr>
    </table>

    <h2 id="games-import">Importer</h2>
    <p>Depuis <code>Jeux &amp; mods → Import / Export</code> : déposez le fichier <code>.json</code> dans la zone d'import et choisissez la <strong>stratégie</strong> :</p>
    <table class="doc-table">
      <tr><th>Stratégie</th><th>Effet</th></tr>
      <tr><td><strong>✓ Ignorer</strong> (skip)</td><td>Si un jeu existe déjà, il est laissé tel quel (rien n'est écrasé).</td></tr>
      <tr><td><strong>⚠️ Remplacer</strong></td><td>Les versions et configs existantes sont <strong>supprimées puis recréées</strong> à partir du JSON.</td></tr>
    </table>
    <div class="callout warn"><span class="i">⚠️</span><div>En mode <strong>Remplacer</strong>, la config existante du jeu est écrasée. Les <strong>images ne sont jamais touchées</strong>.</div></div>

    <h2 id="games-export">Exporter</h2>
    <p>Sur la même page, sélectionnez un ou plusieurs jeux (filtre + « Tout / Aucun ») et cliquez sur <strong>Télécharger le JSON</strong>. Le fichier est nommé automatiquement, par ex. <code>gnp_Counter-Strike16_2026-06-05.json</code> (1 jeu), <code>gnp_Minecraft_CS2_Rust_….json</code> (2-3), ou <code>gnp_export_4jeux_….json</code> (4+).</p>

    <h2 id="games-images">Images (icônes & bannières)</h2>
    <div class="callout"><span class="i">🖼️</span><div>L'export/import <strong>n'inclut pas</strong> les icônes ni les bannières. Après un import, ré-uploadez-les dans la fiche de chaque jeu. (Sur Lavela Studio, vous pourrez aussi récupérer les visuels des jeux.)</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
