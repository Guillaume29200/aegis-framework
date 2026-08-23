<?php
$docPage = 'modules/gnp/doc-marketplace.php';
$seo = ['title' => 'Marketplace & plugins — Documentation · GameNodePanel', 'desc' => "Le Marketplace de GameNodePanel : ajouter des plugins manuellement ou via l'IA qui reconnaît l'architecture du jeu et sait exactement où installer. Manifeste, installation SSH, gestion des admins.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-marketplace.php'];
require __DIR__ . '/../../inc/head.php';

$manifest = <<<'JSON'
{
  "manifest_version": 1,
  "plugin": {
    "id": "essentialsx",
    "name": "EssentialsX",
    "version": "2.21.0",
    "description": "Commandes essentielles pour serveurs Bukkit/Spigot/Paper.",
    "author": { "name": "EssentialsX Team", "url": "https://essentialsx.net" }
  },
  "compatibility": {
    "games": ["minecraft"],
    "loaders": ["paper", "spigot", "bukkit"],
    "min_game_version": "1.16"
  },
  "files": [
    { "path": "EssentialsX-2.21.0.jar", "checksum": "sha256:…", "size": 2148736 }
  ],
  "install": {
    "target_dir": "{plugin_dir}",
    "post_install": ["restart"]
  }
}
JSON;
?>
    <h1>Marketplace &amp; plugins</h1>
    <p class="doc-lead">Le Marketplace gère le <strong>catalogue de plugins/mods</strong> et leur installation sur vos serveurs. Sa force : une <strong>IA</strong> qui reconnaît l'architecture du fichier et sait <strong>exactement où l'installer</strong>.</p>
    <div class="doc-meta"><span class="doc-pill">module Marketplace</span><span class="doc-pill">manifest v1</span><span class="doc-pill">install via SSH</span></div>

    <h2 id="mk-intro">Le Marketplace</h2>
    <p>Chaque plugin du catalogue est décrit par un <strong>manifeste</strong> (compatibilité jeu, fichiers, emplacement d'installation). Côté utilisateur, on parcourt le catalogue et on installe en un clic ; côté serveur, GameNodePanel dépose les fichiers <strong>au bon endroit via SSH</strong> et exécute les actions post-installation.</p>

    <h2 id="mk-catalog">Alimenter le catalogue</h2>
    <p>Deux façons d'ajouter un plugin au catalogue :</p>
    <table class="doc-table">
      <tr><th>Méthode</th><th>Principe</th></tr>
      <tr><td><strong>Manuelle</strong></td><td>Créer / importer un manifeste à la main (formulaire <code>create</code> / <code>import</code>).</td></tr>
      <tr><td><strong>Par IA</strong> ⭐</td><td>Donner une URL de téléchargement + sélectionner le(s) jeu(x) : l'IA analyse le fichier et génère le manifeste complet.</td></tr>
    </table>

    <h2 id="mk-ai">Ajout par IA</h2>
    <p>C'est la voie recommandée. Depuis <code>Marketplace → Import IA</code>, vous fournissez l'<strong>URL du plugin</strong> et choisissez le(s) jeu(x) cible(s). Le service IA (<code>MarketplaceAIService</code>) :</p>
    <ol class="steps">
      <li><strong>Télécharge</strong> le fichier depuis l'URL.</li>
      <li><strong>Analyse son contenu</strong> : pour un <code>.jar</code> (qui est un ZIP), il lit <code>plugin.yml</code> (Bukkit/Spigot/Paper), <code>bungee.yml</code> (BungeeCord), <code>paper-plugin.yml</code>… afin d'identifier le <strong>système</strong> et la <strong>compatibilité</strong>.</li>
      <li>Construit un <strong>prompt contextualisé</strong> avec les infos du jeu et de l'archive, puis interroge l'IA (multi-provider : OpenAI / Claude / Mistral, selon la clé configurée).</li>
      <li><strong>Génère le manifeste</strong> complet, puis <strong>force les valeurs calculées</strong> (taille, checksum) — l'IA n'est pas crue sur ces points — et corrige les erreurs courantes.</li>
    </ol>
    <div class="callout"><span class="i">🧠</span><div>L'IA <strong>reconnaît l'architecture</strong> du plugin (loader, dépendances) et en déduit <strong>où l'installer</strong> sur le serveur. Vous validez le manifeste proposé avant publication.</div></div>
    <div class="callout warn"><span class="i">🔑</span><div>Les clés IA (<code>openai_api_key</code>, <code>claude_api_key</code>, <code>mistral_api_key</code>) et le provider par défaut se configurent dans les réglages — stockées de façon sécurisée.</div></div>

    <h2 id="mk-manifest">Le manifeste</h2>
    <p>Le manifeste suit le schéma <code>plugin-manifest-v1.json</code> (validé par <code>ManifestValidatorService</code>). Champs requis : <code>manifest_version</code>, <code>plugin</code>, <code>compatibility</code>, <code>files</code>, <code>install</code>.</p>
    <pre><code><?= $h($manifest) ?></code></pre>
    <table class="doc-table">
      <tr><th>Bloc</th><th>Rôle</th></tr>
      <tr><td><code>plugin</code></td><td>Identité : <code>id</code> (slug), <code>name</code>, <code>version</code> (semver), description, auteur.</td></tr>
      <tr><td><code>compatibility</code></td><td>Jeux, loaders (paper/spigot/bukkit…), version min — pour filtrer les serveurs compatibles.</td></tr>
      <tr><td><code>files</code></td><td>Fichiers livrés, avec <code>checksum</code> et <code>size</code> (vérifiés à l'installation).</td></tr>
      <tr><td><code>install</code></td><td>Emplacement cible (ex. <code>{plugin_dir}</code>) et actions post-install (ex. <code>restart</code>).</td></tr>
    </table>

    <h2 id="mk-systems">Systèmes reconnus</h2>
    <p>Au-delà de Minecraft, le Marketplace gère des systèmes de plugins via des <strong>parsers dédiés</strong> :</p>
    <ul>
      <li><strong>Bukkit / Spigot / Paper</strong> et <strong>BungeeCord</strong> (Minecraft) — détectés via <code>plugin.yml</code> / <code>bungee.yml</code> / <code>paper-plugin.yml</code>.</li>
      <li><strong>SourceMod</strong> (jeux Source : CS2, etc.) — via <code>SourceModAdminParser</code>.</li>
      <li><strong>AMX Mod X</strong> (Counter-Strike 1.6, jeux GoldSrc) — via <code>AMXModAdminParser</code>.</li>
    </ul>
    <p>Ces parsers permettent aussi la <strong>gestion des administrateurs</strong> du plugin (voir plus bas).</p>

    <h2 id="mk-install">Installation SSH</h2>
    <p><code>PluginInstallerService</code> orchestre l'installation, étape par étape :</p>
    <ol>
      <li>Vérifie que le plugin n'est pas déjà installé.</li>
      <li>Contrôle la <strong>compatibilité jeu</strong>, les <strong>dépendances</strong> et les <strong>conflits</strong>.</li>
      <li>Télécharge l'archive et <strong>vérifie le checksum</strong>.</li>
      <li>Valide le contenu de l'archive (extensions interdites côté web bloquées).</li>
      <li><strong>Installe sur le serveur via SSH</strong>, au bon dossier (<code>{plugin_dir}</code> / chemin de la version).</li>
      <li>Exécute les <strong>actions post-install</strong> (ex. redémarrage).</li>
      <li>Enregistre l'installation, incrémente le compteur de téléchargements, journalise l'historique.</li>
    </ol>
    <div class="callout ok"><span class="i">♻️</span><div><strong>Mise à jour</strong> : la nouvelle version remplace l'ancienne en <strong>préservant les configs</strong>. <strong>Désinstallation</strong> : suppression du serveur, avec option de conserver la configuration.</div></div>

    <h2 id="mk-admins">Gestion des admins</h2>
    <p>Pour les systèmes le permettant (SourceMod, AMX Mod X), le Marketplace gère les <strong>administrateurs du plugin</strong> (ajout/retrait, droits) via <code>PluginAdminService</code> et les parsers correspondants — directement depuis le panel, sans éditer les fichiers à la main.</p>

    <h2 id="mk-browse">Côté utilisateur</h2>
    <p>Dans le panel d'un serveur, l'utilisateur dispose des écrans : <strong>Parcourir</strong> le catalogue (filtré par compatibilité), <strong>Détails</strong> d'un plugin, <strong>Installés</strong> (mettre à jour / désinstaller) et la <strong>gestion des admins</strong>. Tout en un clic, l'installation réelle se faisant en SSH en arrière-plan.</p>
    <div class="callout"><span class="i">🎮</span><div>Cas spécial Minecraft : l'intégration <a href="../pages/modrinth.php">Modrinth</a> apporte 100 000+ plugins prêts à installer. Pour les autres jeux, l'ajout par IA couvre le reste.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
