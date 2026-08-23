<?php
/**
 * documentation/modules/gnh/doc-gnh-commande.php — Tunnel de commande, paiement, livraison.
 */
$docPage = 'modules/gnh/doc-gnh-commande.php';
$seo = [
    'title'     => 'GameNodeHosting — Commande, paiement & livraison · Documentation Aegis Framework',
    'desc'      => "Le tunnel de commande de GameNodeHosting : configuration, codes promo, paiement PayPal, création de la commande, livraison automatique du serveur dans GameNodePanel, facture PDF et e-mails transactionnels.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-commande.php',
];
require __DIR__ . '/../../inc/head.php';

$code_gateway = <<<'PHP'
<?php
namespace GameNodeHosting\Services\Gateways;

// Déposer ce fichier dans Services/Gateways/ suffit : il est découvert.
class StripeGateway implements PaymentGatewayInterface
{
    public function key(): string   { return 'stripe'; }
    public function label(): string { return 'Carte bancaire (Stripe)'; }

    public function start(array $order, array $settings): string { /* → URL de paiement */ }
    public function complete(array $request, array $settings): array { /* → statut */ }
}
PHP;

$code_mail = <<<'TXT'
Bonjour {username},

Votre serveur {game} « {server_name} » est en ligne.

  Adresse      {server_ip}
  Slots        {slots}
  Durée        {duration}
  Échéance     {expires_at}
  Panel        {panel_url}

Merci de votre confiance,
{site_name}
TXT;
?>

    <h1>Commande, paiement &amp; livraison</h1>
    <p class="doc-lead">Du clic sur une offre jusqu'au serveur en ligne, tout est automatique. Ce parcours est le cœur du module : c'est lui qui transforme GameNodePanel en hébergeur, plutôt qu'en simple outil d'exploitation.</p>
    <div class="doc-meta">
      <span class="doc-pill">Tunnel en session</span>
      <span class="doc-pill">Tarif figé</span>
      <span class="doc-pill">Livraison automatique</span>
    </div>

    <h2 id="gnho-tunnel">Le tunnel de commande</h2>
    <ol class="steps">
      <li><strong>Configuration</strong> — le visiteur choisit son jeu, ses slots, sa durée, ses suppléments. <code>/hosting/commander/{offre}</code></li>
      <li><strong>Emplacement</strong> — la liste des machines capables d'accueillir <em>cette</em> configuration est demandée en POST, car elle dépend du nombre de slots.</li>
      <li><strong>Récapitulatif</strong> — totaux HT / TVA / TTC, code promo éventuel. <code>/hosting/recapitulatif</code></li>
      <li><strong>Paiement</strong> — départ vers la passerelle, puis retour. <code>/hosting/paiement</code></li>
      <li><strong>Livraison</strong> — la commande devient une location, et le serveur est créé.</li>
    </ol>
    <div class="callout ok"><span class="i">🛒</span><div>La configuration vit <strong>en session</strong> tant qu'elle n'est pas payée. Un visiteur non connecté peut donc configurer entièrement son serveur, puis créer son compte, <strong>sans rien reperdre</strong>. C'est la différence entre un panier abandonné et une vente.</div></div>
    <p><code>CheckoutService</code> ne fait que retenir et calculer : il ne crée <strong>aucune</strong> commande. Rien n'est écrit en base tant que le paiement n'a pas abouti.</p>

    <h2 id="gnho-coupon">Codes promo &amp; liens partenaires</h2>
    <p>Un code se saisit au récapitulatif et se retire aussi facilement. Trois types sont gérés : <code>percent</code> (pourcentage), <code>fixed</code> (montant) et <code>free</code> (gratuité). Chaque usage est tracé dans <code>gnh_coupon_usages</code>, ce qui permet de plafonner un code globalement ou par client.</p>
    <p>Un <strong>lien partenaire</strong> — <code>/hosting/promo/{slug}</code> — dépose le code puis renvoie sur le site. Le clic est enregistré dans <code>gnh_promo_clicks</code> : l'écran Promotions montre ensuite les clics et les conversions réelles de chaque partenaire.</p>
    <div class="callout"><span class="i">💶</span><div>La remise s'applique sur le <strong>total TTC</strong> du récapitulatif : c'est le montant que le client voit, et exactement celui que la passerelle encaisse.</div></div>

    <h2 id="gnho-payment">Le paiement</h2>
    <p>Trois routes seulement : le départ (<code>POST /hosting/paiement</code>), le retour (<code>/hosting/paiement/retour</code>) et l'abandon (<code>/hosting/paiement/annule</code>). <strong>PayPal</strong> est fourni, en REST API v2 (Orders), avec les modes <em>sandbox</em> et <em>live</em>.</p>

    <h3 id="gnho-gateways">Ajouter une passerelle</h3>
    <p>Les passerelles sont <strong>découvertes</strong>, pas déclarées. Déposer dans <code>Services/Gateways/</code> un fichier qui implémente le contrat suffit : il apparaît en administration, puis dans le tunnel une fois activé et configuré.</p>
    <pre><code><?= $h($code_gateway) ?></code></pre>
    <div class="callout"><span class="i">🔍</span><div>Le registre <strong>balaye le dossier</strong> plutôt que de tenir une liste à la main — parce qu'une liste finit toujours par oublier une entrée.</div></div>

    <h2 id="gnho-order">De la configuration à la commande</h2>
    <p>Une fois le paiement confirmé, <code>OrderCreationService</code> transforme la configuration validée en commande, puis en location.</p>
    <div class="callout warn"><span class="i">🔒</span><div><strong>Le tarif est figé à la création.</strong> Modifier une offre, un prix au slot ou un coefficient plus tard ne réécrit <strong>jamais</strong> une commande passée. Un client paie ce qu'il a vu, et sa facture le prouve.</div></div>
    <table class="doc-table">
      <tr><th>Objet</th><th>Statuts</th></tr>
      <tr><td>Commande (<code>gnh_orders</code>)</td><td><code>pending</code> · <code>paid</code> · <code>failed</code> · <code>cancelled</code> · <code>refunded</code></td></tr>
      <tr><td>Location (<code>gnh_rentals</code>)</td><td><code>pending</code> · <code>provisioning</code> · <code>active</code> · <code>expired</code> · <code>suspended</code> · <code>cancelled</code></td></tr>
    </table>

    <h2 id="gnho-provisioning">La livraison automatique</h2>
    <p>C'est le <strong>seul endroit</strong> du module qui écrit dans le schéma de GameNodePanel, et il s'y limite à l'insertion d'une ligne dans <code>gnp_game_servers</code>. Aucune colonne existante n'est modifiée, aucune table n'est altérée.</p>
    <div class="callout"><span class="i">🚚</span><div>Le serveur est créé en statut <strong><code>uninstalled</code></strong> : l'installation reste déclenchée depuis GameNodePanel, qui sait la mener correctement. GameNodeHosting crée l'objet, il ne pilote pas la machine.</div></div>

    <h2 id="gnho-invoice">La facture PDF</h2>
    <p>Générateur <strong>100 % PHP, sans aucune dépendance externe</strong> : police Helvetica standard, texte encodé en Windows-1252 pour les accents. Une location se facturant par lignes (jeu, durée, slots), la mise en page diffère d'une facture produit, mais les primitives de dessin sont celles, éprouvées, du Marketplace.</p>
    <p>Le client récupère sa facture depuis son espace : <code>/hosting/mon-espace/factures/{id}.pdf</code>.</p>

    <h2 id="gnho-mails">Les e-mails transactionnels</h2>
    <p>Trois moments de la vie d'une location, chacun avec son gabarit stocké en réglage, activable séparément :</p>
    <table class="doc-table">
      <tr><th>Moment</th><th>Rôle</th></tr>
      <tr><td>Livraison</td><td>Le serveur est en ligne : adresse, slots, échéance, lien vers le panel.</td></tr>
      <tr><td>Échéance proche</td><td>Prévenir avant l'expiration, avec le lien de renouvellement.</td></tr>
      <tr><td>Remerciement</td><td>Après le paiement.</td></tr>
    </table>
    <p>Les gabarits acceptent des variables entre accolades :</p>
    <pre><code><?= $h($code_mail) ?></code></pre>
    <p>Variables disponibles : <code>{username}</code>, <code>{site_name}</code>, <code>{game}</code>, <code>{server_name}</code>, <code>{server_ip}</code>, <code>{slots}</code>, <code>{duration}</code>, <code>{expires_at}</code>, <code>{days_left}</code>, <code>{amount}</code>, <code>{order_ref}</code>, <code>{panel_url}</code>, <code>{renew_url}</code>.</p>
    <div class="callout ok"><span class="i">✉️</span><div>Le service <strong>compose</strong>, il n'envoie pas. C'est ce qui permet d'afficher un aperçu en administration <strong>sans risquer de partir un e-mail</strong> à un vrai client.</div></div>

    <div class="doc-foot">
      <span>GameNodeHosting · vente</span>
      <span><a href="modules/gnh/doc-gnh-client.php">Espace client &amp; panel de jeu →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
