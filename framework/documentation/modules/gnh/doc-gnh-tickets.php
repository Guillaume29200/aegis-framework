<?php
/**
 * documentation/modules/gnh/doc-gnh-tickets.php — Le support intégré à GameNodeHosting.
 *
 * Cette page remplace l'ancienne documentation du module Tickets, qui était
 * une extension payante de GameNodePanel. Ce support est désormais absorbé
 * par GameNodeHosting et fourni sans surcoût.
 */
$docPage = 'modules/gnh/doc-gnh-tickets.php';
$seo = [
    'title'     => 'GameNodeHosting — Support intégré · Documentation Aegis Framework',
    'desc'      => "Le support client de GameNodeHosting : chaque ticket est rattaché à un serveur de jeu. Anciennement extension payante de GameNodePanel, désormais intégré sans surcoût. Côté client, côté staff, statuts, anti-spam et sécurité.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-tickets.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Support intégré</h1>
    <p class="doc-lead">Chaque demande de support est <strong>rattachée à un serveur de jeu</strong> du client. Le staff a donc le contexte technique sous les yeux en ouvrant le ticket — fini les allers-retours « quel serveur ? quelle configuration ? ».</p>
    <div class="doc-meta">
      <span class="doc-pill">Intégré</span>
      <span class="doc-pill">Sans surcoût</span>
      <span class="doc-pill">Lié au serveur</span>
    </div>

    <div class="callout ok"><span class="i">🎁</span><div>
      <strong>Anciennement payant, désormais inclus.</strong> Ce support était vendu comme une extension complémentaire de GameNodePanel, sous le nom de module <strong>Tickets</strong>. Il est aujourd'hui <strong>pré-intégré à GameNodeHosting, sans surcoût</strong> : il n'y a plus rien à acheter, ni à installer séparément. Si vous avez GameNodeHosting, vous avez le support.
    </div></div>

    <h2 id="tk-intro">Ce que c'est</h2>
    <p>Un système de tickets pensé pour l'hébergement de jeux, et non un formulaire de contact générique. Le client ouvre un ticket <strong>depuis l'un de ses serveurs</strong> ; le staff le traite avec, affichées à côté de la conversation, les informations du serveur concerné.</p>
    <table class="doc-table">
      <tr><th>Qui</th><th>Où</th></tr>
      <tr><td>Le client</td><td><code>/hosting/tickets</code>, dans son espace</td></tr>
      <tr><td>Le staff</td><td><em>GameNode Hosting → Support</em>, soit <code>/admin/gamenodehosting/tickets</code></td></tr>
    </table>

    <h2 id="tk-included">Plus rien à installer</h2>
    <p>Le module autonome <strong>Tickets n'existe plus</strong> : ses tables, ses écrans et sa logique ont été repris dans GameNodeHosting. Les données vivent désormais dans les tables du module hôte, <code>gnh_tickets</code> et <code>gnh_ticket_messages</code> — conformément à la règle Aegis selon laquelle chaque module possède son propre préfixe.</p>
    <div class="callout"><span class="i">🛑</span><div>Comme le reste de GameNodeHosting, le support suppose <strong>GameNodePanel installé</strong> : sans lui il n'y a pas de serveurs, donc rien à quoi rattacher un ticket. Voir <a href="modules/gnh/doc-gnh.php">la vue d'ensemble</a>.</div></div>

    <h2 id="tk-client">Côté client</h2>
    <ol class="steps">
      <li>Ouvrir un ticket depuis son espace, en choisissant <strong>le serveur concerné</strong> et le type de problème.</li>
      <li>Suivre la conversation, répondre, joindre des précisions.</li>
      <li>Fermer son ticket quand c'est résolu — ou le <strong>rouvrir</strong> si le problème revient.</li>
    </ol>
    <p>Un ticket fermé n'accepte plus de réponse tant qu'il n'est pas rouvert : le fil reste lisible plutôt que de s'étirer indéfiniment.</p>

    <h3 id="tk-types">Les types de problème</h3>
    <table class="doc-table">
      <tr><th>Type</th><th>Usage</th></tr>
      <tr><td>Plantage serveur</td><td>Le serveur tombe, redémarre, ne se lance plus</td></tr>
      <tr><td>Problème de panel</td><td>L'interface de gestion ne répond pas comme attendu</td></tr>
      <tr><td>Erreur générale</td><td>Comportement anormal non classé</td></tr>
      <tr><td>Autre</td><td>Le reste — facturation, question commerciale</td></tr>
    </table>
    <p>Le type oriente le tri du staff : une file de plantages ne se traite pas comme une file de questions de facturation.</p>

    <h2 id="tk-admin">Côté staff</h2>
    <p>La liste de tous les tickets, filtrable par statut. À l'ouverture d'un ticket : la conversation complète, l'identité du client, et le serveur concerné avec ses informations techniques.</p>
    <p>Trois actions : <strong>répondre</strong>, <strong>changer le statut</strong>, <strong>supprimer</strong>.</p>

    <h2 id="tk-statuses">Statuts &amp; priorités</h2>
    <table class="doc-table">
      <tr><th>Statut</th><th>Sens</th></tr>
      <tr><td>🟢 <code>open</code></td><td>Ouvert, en attente de traitement</td></tr>
      <tr><td>⏳ <code>pending</code></td><td>En attente — d'un retour client ou d'une action tierce</td></tr>
      <tr><td>⚫ <code>closed</code></td><td>Fermé</td></tr>
    </table>
    <p>Trois priorités sont disponibles côté staff : <code>low</code>, <code>normal</code>, <code>high</code>.</p>
    <div class="callout"><span class="i">↩️</span><div>Le client ne manipule que <strong>deux</strong> états — ouvert et fermé. <code>pending</code> est un outil de gestion interne : c'est au staff de dire qu'il attend quelque chose, pas au client de se le déclarer.</div></div>

    <h2 id="tk-antispam">Anti-spam &amp; limites</h2>
    <p>Des garde-fous s'appliquent aux <strong>clients</strong>, jamais au staff :</p>
    <table class="doc-table">
      <tr><th>Garde-fou</th><th>Valeur</th></tr>
      <tr><td>Tickets ouverts simultanés par client</td><td>10 maximum</td></tr>
      <tr><td>Délai minimum entre deux créations</td><td>60 secondes</td></tr>
      <tr><td>Délai minimum entre deux réponses</td><td>15 secondes</td></tr>
      <tr><td>Longueur du sujet</td><td>255 caractères</td></tr>
      <tr><td>Longueur d'un message</td><td>10 000 caractères</td></tr>
    </table>
    <p>Un sujet trop long est <strong>tronqué</strong> plutôt que rejeté — perdre un signalement parce que son titre dépassait de trois caractères serait absurde. Un message trop long, lui, est refusé : le tronquer perdrait de l'information utile sans que personne ne le sache.</p>

    <h2 id="tk-secu">Sécurité</h2>
    <table class="doc-table">
      <tr><th>Protection</th><th>Détail</th></tr>
      <tr><td>Cloisonnement des comptes</td><td>Un client ne peut lire, ni répondre à, un ticket qui n'est pas le sien : la lecture passe par une requête liant l'identifiant du ticket <strong>et</strong> celui du client.</td></tr>
      <tr><td>Rattachement au serveur</td><td>Le serveur choisi doit appartenir au client. On ne rattache pas un ticket au serveur d'un tiers.</td></tr>
      <tr><td>CSRF</td><td>Toutes les actions mutantes sont en POST et protégées par jeton.</td></tr>
      <tr><td>Échappement</td><td>Les messages sont du texte : ils sont échappés à l'affichage, jamais interprétés en HTML.</td></tr>
      <tr><td>Requêtes préparées</td><td>Sans exception, comme partout dans le module.</td></tr>
    </table>

    <div class="doc-foot">
      <span>GameNodeHosting · support intégré</span>
      <span><a href="modules/gnh/doc-gnh.php">← Retour à la vue d'ensemble</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
