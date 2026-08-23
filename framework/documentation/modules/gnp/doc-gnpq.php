<?php
$docPage = 'modules/gnp/doc-gnpq.php';
$seo = ['title' => 'GNPQ — Le viewer de serveurs · Documentation GameNodePanel', 'desc' => "GNPQ (ex-GSHQ) est le viewer de serveurs de jeu sur mesure de GameNodePanel : ~40 protocoles (Source, GoldSrc, Quake3, Minecraft…), matrice de champs par jeu, cache, géoloc et widget live. Façon LGSL, en plus complet et extensible.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-gnpq.php'];
require __DIR__ . '/../../inc/head.php';

$code_engine = <<<'PHP'
// protocol/GNPQ-Mygame.php — un protocole = une fonction GNPQ_<id>_engine()
function GNPQ_mygame_engine(array $server, array $viewer_data): array {
    $ip   = $server['adresse_ip'];
    $port = (int) $server['gserver_port'];

    // 1) Envoi du paquet de requête (UDP via fsockopen)
    $packet   = "\xFF\xFF\xFF\xFFTSource Engine Query\x00";
    $response = gnpq_udp_send_stream($ip, $port, $packet, 3);

    // 2) Pas de réponse → erreur normalisée
    if (!$response || strlen($response) < 5) {
        return gnpq_error($viewer_data, "No response", "mygame", ['ip' => $ip, 'port' => $port]);
    }

    // 3) Parsing (SourceParser pour le binaire Source/GoldSrc)
    //    puis on remplit viewer_data avec les champs pertinents
    $viewer_data['hostname']    = $name;
    $viewer_data['num_players'] = $players;
    $viewer_data['max_players'] = $max;
    $viewer_data['map']         = $map;
    return $viewer_data;
}
PHP;

$code_matrice = <<<'PHP'
// Controllers/GNPQ-Matrice.php — déclare les champs affichés pour ce jeu
'mygame' => [
    'hostname',
    'num_players',
    'max_players',
    'map',
    'players_list',
    'protocol',
],
PHP;
?>
    <h1>GNPQ — le viewer</h1>
    <p class="doc-lead"><strong>GNPQ</strong> (GameNodePanel Query, ex-<em>GSHQ</em>) est le moteur maison qui interroge vos serveurs de jeu et affiche leur état en direct : joueurs, map, ping, type de serveur… Dans l'esprit de <strong>LGSL</strong>, mais plus complet et entièrement extensible.</p>
    <div class="doc-meta"><span class="doc-pill">~40 protocoles</span><span class="doc-pill">v2.0</span><span class="doc-pill">UDP / TCP / HTTP</span><span class="doc-pill">extensible</span></div>

    <h2 id="gq-intro">Qu'est-ce que GNPQ ?</h2>
    <p>GNPQ est une bibliothèque de <strong>query multi-protocoles</strong> : pour chaque type de jeu, elle sait forger le bon paquet réseau, l'envoyer au serveur, parser la réponse (souvent binaire) et en extraire des informations normalisées. Le résultat alimente un <strong>widget live</strong> affiché dans le panel et sur les pages publiques.</p>
    <p>Elle est <strong>autonome</strong> (aucune dépendance externe) et organisée en trois couches : des <strong>protocoles</strong> (un par jeu), des <strong>briques utilitaires</strong> (réseau, parsing, cache, géoloc) et une <strong>matrice</strong> qui décide quoi afficher selon le jeu.</p>

    <h2 id="gq-vs">GNPQ vs LGSL</h2>
    <table class="doc-table">
      <tr><th></th><th>LGSL</th><th>GNPQ</th></tr>
      <tr><td>Multi-protocoles</td><td>✅</td><td>✅ (~40)</td></tr>
      <tr><td>Champs adaptés par jeu</td><td>partiel</td><td>✅ matrice dédiée</td></tr>
      <tr><td>Parsing v2.0 enrichi (anti-cheat, steam_app_id, game_type…)</td><td>—</td><td>✅</td></tr>
      <tr><td>Intégré au panel (cache, batch, géoloc, images de map)</td><td>—</td><td>✅</td></tr>
      <tr><td>Widget Discord</td><td>—</td><td>✅</td></tr>
      <tr><td>Extensible (1 fichier par jeu)</td><td>✅</td><td>✅</td></tr>
    </table>

    <h2 id="gq-protocols">Protocoles supportés</h2>
    <p>Chaque protocole vit dans <code>protocol/GNPQ-&lt;Jeu&gt;.php</code>. Familles couvertes :</p>
    <ul>
      <li><strong>Source (SRCDS)</strong> : CS:S, CS2, TF2, L4D / L4D2, DODS, HL2DM, Insurgency, Garry's Mod, Black Mesa, PVKII, FoF, NMRiH…</li>
      <li><strong>GoldSrc (HLDS)</strong> : CS 1.6, CS:CZ, Day of Defeat, Half-Life 1…</li>
      <li><strong>Quake 3</strong> : Call of Duty 2, Call of Duty 4, Quake 3, Jedi Knight 2, MOHAA…</li>
      <li><strong>ASE</strong> : AvP2 / AvP2010…</li>
      <li><strong>Autres</strong> : Minecraft Java (via <code>MinecraftQuery</code>), Palworld, Satisfactory, Squad, Project Zomboid…</li>
      <li><strong>Widget Discord</strong> : présence/boost d'un serveur Discord.</li>
    </ul>
    <div class="callout"><span class="i">🧩</span><div>La liste s'agrandit simplement : un nouveau jeu = un nouveau fichier de protocole (voir <a href="modules/gnp/doc-gnpq.php#gq-extend">Ajouter un jeu</a>).</div></div>

    <h2 id="gq-matrice">La matrice de champs</h2>
    <p>Tous les jeux ne renvoient pas les mêmes informations. La <strong>matrice</strong> (<code>GNPQ-Matrice.php</code>) déclare, par protocole, les champs <em>pertinents</em> à afficher — pour ne montrer que ce qui a du sens.</p>
    <pre><code><?= $h($code_matrice) ?></code></pre>
    <p>Champs disponibles (selon le jeu) : <code>hostname</code>, <code>num_players</code>, <code>max_players</code>, <code>map</code>, <code>bots</code>, <code>players_list</code>, <code>anti_cheat</code>, <code>prive_public</code>, <code>serveur_type</code>, <code>serveur_os</code>, <code>protocol</code>, <code>steam_app_id</code>, <code>protocol_version</code>, <code>game_type</code>, <code>mod_game</code>… La v2.0 a enrichi Source, Quake 3 et le widget Discord (boost_level, expires_at…).</p>

    <h2 id="gq-flow">Cycle d'une requête</h2>
    <ol>
      <li><code>GNPQController::getServerStatus()</code> (ou <code>getBatchStatus()</code> pour plusieurs serveurs) est appelé en AJAX.</li>
      <li>Le <strong>protocole est résolu</strong> : priorité au champ <code>protocol</code> du serveur, sinon le <code>viewer_id</code> du jeu / <code>gnpq_protocol</code>, normalisé par <code>gnpq_game_to_protocol()</code>.</li>
      <li>GNPQ appelle <code>GNPQ_&lt;protocole&gt;_engine($server, $viewer_data)</code>.</li>
      <li>Le moteur interroge le serveur, parse la réponse et renvoie <code>viewer_data</code>.</li>
      <li>Le résultat est <strong>mis en cache</strong> (pour ne pas spammer le serveur) puis renvoyé en JSON au <strong>widget</strong>.</li>
    </ol>
    <div class="callout"><span class="i">⚡</span><div><code>forceRefresh()</code> permet de forcer une requête fraîche (ignorer le cache). Le mode <strong>batch</strong> interroge plusieurs serveurs en une fois.</div></div>

    <h2 id="gq-libs">Les briques (Libs)</h2>
    <table class="doc-table">
      <tr><th>Brique</th><th>Rôle</th></tr>
      <tr><td><code>Network.php</code></td><td>Envoi réseau : <code>gnpq_udp_send</code>, <code>gnpq_udp_send_stream</code> (fsockopen), <code>gnpq_tcp_send</code>, <code>gnpq_http_get/post</code>, <code>gnpq_is_reachable</code>, validation IP/port.</td></tr>
      <tr><td><code>Parsers.php</code></td><td>Parsing binaire (SourceParser…) des réponses serveur.</td></tr>
      <tr><td><code>MinecraftQuery.php</code></td><td>Requête spécifique Minecraft Java.</td></tr>
      <tr><td><code>GeoIP.php</code></td><td>Géolocalisation du serveur (drapeau / pays).</td></tr>
      <tr><td><code>Cache.php</code></td><td>Cache des résultats de query.</td></tr>
      <tr><td><code>Debug.php</code></td><td>Journalisation de debug (<code>gnpq_debug_log()</code>) et erreurs normalisées (<code>gnpq_error()</code>).</td></tr>
      <tr><td><code>GNPQ-MapViewers.php</code></td><td>Images de map par jeu (dossier <code>uploads/map/&lt;Jeu&gt;/</code>).</td></tr>
    </table>

    <h2 id="gq-extend">Ajouter un jeu (protocole)</h2>
    <p>GNPQ est conçu pour s'étendre. Pour supporter un nouveau jeu :</p>
    <ol class="steps">
      <li>Créez <code>protocol/GNPQ-Mygame.php</code> avec une fonction <code>GNPQ_mygame_engine($server, $viewer_data)</code>.</li>
      <li>Utilisez les helpers réseau (<code>gnpq_udp_send_stream</code>, <code>gnpq_tcp_send</code>…) et un parser pour remplir <code>viewer_data</code>.</li>
      <li>Déclarez les champs pertinents du jeu dans <code>GNPQ-Matrice.php</code>.</li>
      <li>(Optionnel) Ajoutez les images de map dans <code>uploads/map/Mygame/</code>.</li>
    </ol>
    <pre><code><?= $h($code_engine) ?></code></pre>
    <div class="callout ok"><span class="i">✅</span><div>Aucune autre modification : dès que le fichier de protocole existe et que la matrice le connaît, le viewer sait afficher ce jeu.</div></div>

    <h2 id="gq-widget">Le widget</h2>
    <p>Le rendu se fait via <code>viewer-widget.php</code> (et une variante « standard »). Le widget se charge en <strong>AJAX</strong> : il affiche un loader, interroge GNPQ, puis montre l'état du serveur — nom, joueurs (n/max), map (avec image si disponible), liste des joueurs, type/OS, anti-cheat, géolocalisation… selon ce que la matrice autorise pour ce jeu.</p>
    <p>C'est ce widget que l'on retrouve dans les panels (onglet du serveur) et qui peut être intégré ailleurs dans GameNodePanel.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
