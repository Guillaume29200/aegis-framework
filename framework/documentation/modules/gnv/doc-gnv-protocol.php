<?php
$docPage = 'modules/gnv/doc-gnv-protocol.php';
$seo = [
    'title' => 'GameNodeViewer — Créer un protocole · Documentation Aegis Framework',
    'desc'  => "Ajouter un protocole d'interrogation à GameNodeViewer : l'interface ProtocolInterface, l'auto-découverte, l'objet ServerInfo, la couche réseau Net, et un exemple complet pas à pas.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnv/doc-gnv-protocol.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Créer un protocole</h1>
    <p class="doc-lead">L'argument anti-LGSL de GameNodeViewer : ajouter le support d'un nouveau jeu/protocole revient à <strong>déposer une seule classe</strong>. Elle est <strong>auto-découverte</strong> — aucune liste à maintenir, aucun cœur à modifier.</p>

    <h2 id="p-intro">Le principe</h2>
    <p>Un « protocole » décrit <strong>comment interroger</strong> un type de serveur (Source A2S, Quake3 getstatus, HTTP FiveM…). Chaque protocole est une classe dans <code>modules/GameNodeViewer/Protocol/</code> nommée <code>XxxProtocol.php</code> qui implémente <code>ProtocolInterface</code>.</p>
    <div class="callout"><span class="i">💡</span><div>Un protocole peut couvrir <strong>plusieurs jeux</strong>. Ex. <code>SourceProtocol</code> sert ~35 jeux : ils ne diffèrent que par l'icône, pas par la requête.</div></div>

    <h2 id="p-interface">L'interface</h2>
    <pre><code>interface ProtocolInterface
{
    public static function key(): string;    // identifiant = colonne gnv_games.protocol
    public static function label(): string;  // libellé humain (admin)
    public function query(array $server, int $timeout): ServerInfo;
}</code></pre>
    <p><code>$server</code> contient <code>host</code>, <code>port</code>, <code>query_port</code>, <code>game_key</code>. La méthode renvoie un <code>ServerInfo</code>.</p>

    <h2 id="p-registry">L'auto-découverte</h2>
    <p><code>ProtocolRegistry</code> scanne <code>Protocol/*Protocol.php</code> : toute classe implémentant l'interface est enregistrée sous sa <code>key()</code>. <strong>Rien d'autre à faire.</strong></p>
    <pre><code>ProtocolRegistry::all();     // ['source' =&gt; SourceProtocol::class, …]
ProtocolRegistry::get('source');  // la classe pour un protocole
ProtocolRegistry::labels();  // ['source' =&gt; 'Source / GoldSource (A2S)', …]</code></pre>

    <h2 id="p-serverinfo">L'objet ServerInfo</h2>
    <p>Résultat typé (fini l'array fourre-tout). Renseignez seulement ce que vous savez ; la vue affiche ce qui est présent &amp; activé dans la matrice.</p>
    <table class="doc-table">
      <tr><th>Propriété</th><th>Type</th><th>Sens</th></tr>
      <tr><td><code>online</code></td><td>bool</td><td>serveur joignable</td></tr>
      <tr><td><code>hostname</code> / <code>map</code> / <code>gameType</code> / <code>version</code></td><td>?string</td><td>infos serveur</td></tr>
      <tr><td><code>players</code> / <code>maxPlayers</code> / <code>bots</code></td><td>int</td><td>fréquentation</td></tr>
      <tr><td><code>passworded</code> / <code>vac</code></td><td>?bool</td><td>privé / anti-triche</td></tr>
      <tr><td><code>os</code> / <code>serverType</code></td><td>?string</td><td>ex. « 🐧 Linux »</td></tr>
      <tr><td><code>playerList</code></td><td>array</td><td><code>[['name','score','ping','duration'], …]</code></td></tr>
      <tr><td><code>extra</code></td><td>array</td><td>champs spécifiques (favicon, tags…)</td></tr>
    </table>
    <p>Helper : <code>ServerInfo::offline($protocol, $host, $port, $error)</code> pour un retour « hors ligne ».</p>

    <h2 id="p-net">La couche réseau</h2>
    <table class="doc-table">
      <tr><th>Méthode</th><th>Usage</th></tr>
      <tr><td><code>Net::udp($ip,$port,$packet,$timeout)</code></td><td>Source, Quake3… (ext-sockets + repli fsockopen)</td></tr>
      <tr><td><code>Net::tcp(...)</code></td><td>Minecraft &amp; co</td></tr>
      <tr><td><code>Net::httpGet($url,$timeout)</code> / <code>httpPost(...)</code></td><td>FiveM, Discord, webhooks</td></tr>
      <tr><td><code>Net::resolve($host)</code> / <code>validIp()</code> / <code>validPort()</code></td><td>résolution &amp; validation</td></tr>
    </table>
    <p>Pour le binaire Source : <code>BinaryReader</code> (byte/short/long/float/string little-endian). Pour Quake3 : <code>Text::q3Vars()</code> / <code>Text::q3Players()</code> / <code>Text::stripQ3Colors()</code>.</p>

    <h2 id="p-example">Exemple complet</h2>
    <p>Un protocole UDP fictif « <code>myproto</code> » qui renvoie <code>hostname;players;max;map</code> :</p>
    <pre><code>&lt;?php
declare(strict_types=1);
namespace GameNodeViewer\Protocol;

use GameNodeViewer\Net\Net;

final class MyprotoProtocol implements ProtocolInterface
{
    public static function key(): string   { return 'myproto'; }
    public static function label(): string { return 'Mon protocole (UDP)'; }

    public function query(array $server, int $timeout): ServerInfo
    {
        $host = $server['host'];
        $port = (int) ($server['query_port'] ?: $server['port']);
        $ip   = Net::resolve($host) ?? $host;

        $resp = Net::udp($ip, $port, "\xFF\xFFstatus", $timeout);
        if ($resp === null) {
            return ServerInfo::offline('myproto', $host, $port, 'Aucune réponse.');
        }

        [$name, $pl, $max, $map] = array_pad(explode(';', trim($resp)), 4, '');

        $info = new ServerInfo();
        $info->protocol   = 'myproto';
        $info->host       = $host;
        $info->port       = $port;
        $info->online     = true;
        $info->hostname   = Text::clean($name);
        $info->players    = (int) $pl;
        $info->maxPlayers = (int) $max;
        $info->map        = $map ?: null;
        return $info;
    }
}</code></pre>
    <div class="callout ok"><span class="i">✅</span><div>Dès que le fichier est déposé, le protocole apparaît dans l'admin (sélecteur de protocole du registre de jeux). Zéro configuration.</div></div>

    <h2 id="p-register">Brancher un jeu dessus</h2>
    <ol class="steps">
      <li><strong>Admin → Jeux → Ajouter</strong> : clé jeu, libellé, <strong>protocole = Mon protocole</strong>, port par défaut, icône.</li>
      <li>Cochez les <strong>champs d'affichage</strong> pertinents (les défauts du protocole sont pré-cochés).</li>
      <li><strong>Admin → Serveurs → Ajouter</strong> : choisissez ce jeu, saisissez l'hôte/port.</li>
    </ol>

    <h2 id="p-test">Tester</h2>
    <ul>
      <li>Sur la fiche d'édition d'un serveur : bouton <strong>« 📡 Tester maintenant »</strong> (interrogation live, bypass cache).</li>
      <li>En CLI : <code>php -l</code> sur la classe, puis vérifiez la découverte via un petit script appelant <code>ProtocolRegistry::labels()</code>.</li>
    </ul>
    <div class="callout warn"><span class="i">⚠️</span><div>Nommez le fichier <code>&lt;Clé&gt;Protocol.php</code> avec la classe du même nom dans le namespace <code>GameNodeViewer\Protocol</code> — c'est ainsi que le registre la trouve.</div></div>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
