<?php
/**
 * documentation/modules/gnh/doc-gnh-catalogue.php — Offres, tarifs et durées.
 */
$docPage = 'modules/gnh/doc-gnh-catalogue.php';
$seo = [
    'title'     => 'GameNodeHosting — Offres, tarifs & durées · Documentation Aegis Framework',
    'desc'      => "La tarification de GameNodeHosting : prix au slot, durées à coefficient, offres mensuelles, suppléments facturables, TVA et contrôle de capacité avant vente.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-catalogue.php',
];
require __DIR__ . '/../../inc/head.php';

$code_formula = <<<'TXT'
       récurrent = prix_du_slot × slots × jours × coefficient
           total = récurrent + frais_de_mise_en_service
       économie  = (prix × slots × jours) − récurrent

Exemple — Rust, 0,04 €/slot/jour, 50 slots, 90 jours, coefficient 0,80 :

   sans remise   0,04 × 50 × 90            = 180,00 €
   récurrent     180,00 × 0,80             = 144,00 €
   mise en service                         =   5,00 €
   ───────────────────────────────────────────────────
   total                                     149,00 €
   économie affichée au client                36,00 €
TXT;
?>

    <h1>Offres, tarifs &amp; durées</h1>
    <p class="doc-lead">Toute la tarification de GameNodeHosting repose sur <strong>une seule formule</strong>, appliquée par un service unique. Un prix affiché sur la boutique, calculé au récapitulatif et facturé sur le PDF sort toujours du même endroit — c'est ce qui garantit qu'ils ne divergent jamais.</p>
    <div class="doc-meta">
      <span class="doc-pill">Prix au slot</span>
      <span class="doc-pill">Durées à coefficient</span>
      <span class="doc-pill">Capacité vérifiée</span>
    </div>

    <h2 id="gnhc-modele">Le modèle en trois pièces</h2>
    <table class="doc-table">
      <tr><th>Pièce</th><th>Ce qu'elle porte</th><th>Écran</th></tr>
      <tr><td><strong>Durée</strong></td><td>Un nombre de jours et un <strong>coefficient</strong> de remise.</td><td>Durées de location</td></tr>
      <tr><td><strong>Tarif par jeu</strong></td><td>Le prix d'un slot, pour un jeu donné du catalogue GameNodePanel.</td><td>Gérer les offres → Tarifs</td></tr>
      <tr><td><strong>Offre</strong></td><td>Un palier nommé portant un prix <strong>mensuel</strong> de référence, applicable à plusieurs jeux.</td><td>Gérer les offres</td></tr>
    </table>
    <div class="callout ok"><span class="i">🎯</span><div>La conséquence est ce qui rend le module tenable : créer « 6 mois −20 % » ne demande de toucher à <strong>aucun jeu</strong>. Le coefficient s'applique partout, une seule fois.</div></div>

    <h2 id="gnhc-durations">Les durées</h2>
    <p>Une durée est un couple <em>jours + coefficient</em>. Le coefficient est un multiplicateur : <code>1.0</code> = plein tarif, <code>0.80</code> = 20 % de remise. Chaque durée s'active ou se désactive sans être supprimée, ce qui permet de retirer une offre saisonnière et de la remettre plus tard.</p>
    <div class="callout"><span class="i">🛡️</span><div>Un coefficient nul ou négatif rendrait la location <strong>gratuite</strong>. Le service retombe alors sur le plein tarif plutôt que d'offrir le serveur — une faute de saisie ne se paie pas en chiffre d'affaires.</div></div>

    <h2 id="gnhc-pricing">Les tarifs par jeu</h2>
    <p>Le catalogue des jeux vient de GameNodePanel (<code>gnp_games</code>) : GameNodeHosting n'a pas de table de jeux et n'en crée pas. Pour chaque jeu, vous fixez le prix d'un slot, les bornes de slots autorisées et d'éventuels frais de mise en service. Un jeu sans tarif n'apparaît simplement pas à la vente.</p>
    <p>Un seul service lit <code>gnp_games</code> — <code>GameCatalogService</code>. La dépendance à GameNodePanel est ainsi concentrée en un point, et son absence se détecte là plutôt qu'au milieu d'une page.</p>

    <h2 id="gnhc-offers">Les offres</h2>
    <p>Une offre est un palier commercial — « Starter 4 Go », « Pro 8 Go » — portant un prix mensuel de référence. Elle peut servir <strong>plusieurs jeux</strong>, avec un prix ajustable au cas par cas : un « Starter 4 Go » n'a pas forcément le même tarif sur Minecraft et sur Rust.</p>
    <p>Depuis l'écran <em>Gérer mes offres</em>, chaque offre s'enregistre, se duplique, s'active ou se supprime, et un aperçu montre le rendu côté visiteur avant publication.</p>

    <h2 id="gnhc-formula">La formule</h2>
    <pre><code><?= $h($code_formula) ?></code></pre>
    <p>Le service qui l'applique — <code>PricingService</code> — est volontairement <strong>sans accès à la base</strong>. On lui passe les données déjà lues ; il ne fait que calculer. La règle métier centrale du module reste ainsi lisible et vérifiable sans monter une base de test.</p>
    <p>Il renvoie aussi <code>undiscounted</code> et <code>savings</code> : c'est ce qui permet à la boutique d'afficher « 180 € <s>barrés</s> → 144 €, vous économisez 36 € » sans qu'aucun gabarit n'ait à faire une soustraction — il n'en aurait pas les moyens.</p>

    <h3 id="gnhc-vat">TVA</h3>
    <p>Le taux et le mode d'affichage se règlent dans <em>Réglages</em>. Deux cas, traités par le même service :</p>
    <table class="doc-table">
      <tr><th>Mode</th><th>Calcul</th></tr>
      <tr><td>Prix saisis <strong>TTC</strong></td><td>La base HT est retrouvée en divisant par <code>1 + taux</code>.</td></tr>
      <tr><td>Prix saisis <strong>HT</strong></td><td>Le TTC est obtenu en multipliant par <code>1 + taux</code>.</td></tr>
    </table>
    <p>Dans les deux cas, le récapitulatif affiche HT, TVA et TTC séparément, et c'est le TTC qui est encaissé.</p>

    <h2 id="gnhc-extras">Les suppléments</h2>
    <p>La formule personnalisable accepte des <strong>suppléments facturables</strong> — options que le client coche au moment de configurer. Un service unique les décrit pour l'écran de commande, le récapitulatif, le calcul et la ligne de commande : un prix affiché quelque part et calculé ailleurs finit toujours par diverger.</p>
    <div class="callout warn"><span class="i">⚠️</span><div>
      <strong>Ces suppléments n'ont, en l'état, aucun effet technique.</strong> Cocher « sauvegarde quotidienne » facture l'option mais ne déclenche aucune sauvegarde. C'est un choix assumé, pas un oubli : l'onglet Marketing le dit sans détour et rappelle que <strong>facturer une caractéristique inexistante vous expose</strong>. Ne les activez que si vous assurez le service par ailleurs.
    </div></div>

    <h2 id="gnhc-capacity">Le contrôle de capacité</h2>
    <p>Avant d'accepter une commande, le module vérifie qu'une machine peut réellement la porter. <code>CapacityService</code> lit <code>gnp_dedicated_servers</code> et <code>gnp_game_servers</code>, additionne ce qui est déjà alloué et le compare à ce que la machine annonce.</p>
    <div class="callout"><span class="i">🧮</span><div>Convention GameNodePanel respectée : <code>allocated_cores</code> et <code>allocated_ram</code> valent soit un nombre, soit <code>mutual</code> (partagé, non réservé). <strong>Seules les valeurs numériques sont décomptées</strong> — compter le partagé comme réservé saturerait des machines qui ne le sont pas.</div></div>
    <p>Conséquence côté visiteur : la liste des emplacements disponibles ne peut pas être rendue au chargement de la page, puisqu'elle dépend du nombre de slots demandés. Elle est donc demandée en POST une fois la configuration choisie.</p>
    <div class="callout ok"><span class="i">✅</span><div>Le but de ce contrôle est simple : <strong>ne jamais encaisser un serveur qu'on ne saurait pas livrer</strong>.</div></div>

    <div class="doc-foot">
      <span>GameNodeHosting · tarification</span>
      <span><a href="modules/gnh/doc-gnh-commande.php">Commande, paiement &amp; livraison →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
