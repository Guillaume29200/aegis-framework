<?php
$docPage = 'framework/doc-theme.php';
$seo = ['title' => 'Thème admin & TurboNav — Documentation · Aegis Framework', 'desc' => "Le thème d'administration d'Aegis : helpers admin_header/footer, préférences (thème, disposition, accent), menus JSON, méga-menu et la navigation SPA TurboNav — avec le pattern pour écrire du JS compatible.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-theme.php'];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Thème admin &amp; TurboNav</h1>
    <p class="doc-lead">L'administration d'Aegis repose sur un <strong>thème unique</strong> (design system <code>.ui-*</code>, zéro Bootstrap) et une <strong>navigation SPA</strong> (TurboNav) sans framework. Voici comment l'utiliser dans vos modules — et le piège à connaître côté JavaScript.</p>
    <div class="doc-meta"><span class="doc-pill">Zéro dépendance front</span><span class="doc-pill">Clair / sombre / auto</span><span class="doc-pill">SPA</span></div>

    <h2 id="th-intro">Le thème admin</h2>
    <p>Toutes les pages d'administration partagent un thème situé à un <strong>emplacement unique</strong> : <code>framework/Views/theme/admin/{header,footer}.php</code>. Vos vues ne l'incluent jamais par chemin : elles passent par des helpers.</p>

    <h2 id="th-helpers">admin_header / footer</h2>
    <p>Encadrez le contenu de votre page entre les deux helpers :</p>
    <pre><code>&lt;?php admin_header('Titre de la page', ['currentUser' =&gt; $user]); ?&gt;

  &lt;!-- votre HTML en markup .ui-* --&gt;
  &lt;div class="ui-card"&gt;…&lt;/div&gt;

&lt;?php admin_footer(); ?&gt;</code></pre>
    <div class="callout"><span class="i">📐</span><div>Le catalogue des composants (<code>.ui-card</code>, <code>.ui-kpi</code>, <code>.ui-table</code>, <code>.ui-btn</code>…) est détaillé dans <a href="framework/doc-views.php">Anatomie d'une vue admin</a>.</div></div>

    <h2 id="th-prefs">Préférences &amp; dispositions</h2>
    <p>Le thème mémorise les choix de l'utilisateur en <code>localStorage</code> et les pré-applique par un script inline (anti-FOUC, pas de clignotement au chargement). Les attributs sont portés par la balise <code>&lt;html&gt;</code>.</p>
    <table class="doc-table">
      <tr><th>Clé localStorage</th><th>Valeurs</th><th>Attribut &lt;html&gt;</th></tr>
      <tr><td><code>adm.theme</code></td><td>light / dark / auto</td><td><code>data-theme</code></td></tr>
      <tr><td><code>adm.layout</code></td><td>sidebar / topbar</td><td><code>data-layout</code></td></tr>
      <tr><td><code>adm.accent</code></td><td>couleur d'accent</td><td><code>data-accent</code> (via style)</td></tr>
      <tr><td><code>adm.sidebar</code></td><td>ouvert / replié</td><td><code>data-sidebar</code></td></tr>
    </table>
    <p>Autres attributs gérés : <code>data-panel</code>, <code>data-fullscreen</code>, <code>data-mobnav</code>. En CSS, ciblez le mode sombre via <code>[data-theme="dark"]</code>.</p>

    <h2 id="th-menus">Menus JSON</h2>
    <p>Les entrées de menu d'un module sont déclarées <strong>en JSON</strong> dans son <code>module.json</code> (jamais en dur). <code>AdminMenuService::build()</code> agrège les menus de tous les modules actifs, triés par <code>position</code>.</p>
    <pre><code>{
  "menu": [
    {
      "label": "Mon module",
      "icon": "🚀",
      "position": 300,
      "url": "/admin/mon-module",
      "match": "/admin/mon-module",
      "children": [
        { "label": "Réglages", "icon": "⚙️", "url": "/admin/mon-module/settings" }
      ]
    }
  ]
}</code></pre>
    <table class="doc-table">
      <tr><th>Champ</th><th>Rôle</th></tr>
      <tr><td><code>label</code> / <code>icon</code></td><td>Texte affiché + emoji</td></tr>
      <tr><td><code>url</code></td><td>Lien (absent pour un simple groupe)</td></tr>
      <tr><td><code>position</code></td><td>Ordre croissant dans le menu global</td></tr>
      <tr><td><code>match</code></td><td>Préfixe d'URL pour l'état « actif » (défaut : <code>url</code>)</td></tr>
      <tr><td><code>badge</code></td><td>Compteur optionnel</td></tr>
      <tr><td><code>children</code></td><td>Sous-menu</td></tr>
    </table>
    <div class="callout"><span class="i">🔌</span><div>Activer/désactiver un module <strong>ajoute/retire automatiquement</strong> ses entrées.</div></div>

    <h2 id="th-graft">Greffer dans le menu d'un autre module</h2>
    <p>Un module peut <strong>ajouter ses entrées dans le groupe d'un autre module</strong> sans modifier ce dernier. <code>AdminMenuService</code> applique une <strong>fusion par label</strong> : si deux modules déclarent un groupe portant le <strong>même libellé</strong> (insensible à la casse/aux espaces), leurs <code>children</code> sont réunis sous un seul item. Les autres propriétés (icône, position, <code>mega</code>) viennent de la première occurrence.</p>
    <p>Avantage clé : l'entrée greffée <strong>reste pilotée par son module</strong> — elle apparaît/disparaît selon que ce module est activé, et non celui qui « possède » le groupe.</p>
    <p>Exemple : le module <strong>Tickets</strong> est alloué à GameNodePanel. Il déclare donc le même groupe que GNP pour s'y loger :</p>
    <pre><code>{
  "category": "Game Node Panel",
  "menu": [
    {
      "label": "Game Node Panel",
      "icon": "🖥️",
      "position": 30,
      "match": "/admin/gamenodepanel",
      "mega": true,
      "children": [
        {
          "label": "Support", "icon": "🎫", "match": "/admin/tickets",
          "children": [
            { "label": "Tickets", "icon": "🎫", "url": "/admin/tickets" }
          ]
        }
      ]
    }
  ]
}</code></pre>
    <div class="callout"><span class="i">💡</span><div>Pour qu'un module figure dans la même <strong>catégorie</strong> sur la page d'administration des modules, ajoute aussi la clé <code>"category"</code> (ex. <code>"Game Node Panel"</code>). C'est le mécanisme idéal pour les <strong>add-ons payants</strong> qui se logent dans un module hôte.</div></div>

    <h2 id="th-mega">Méga-menu</h2>
    <p>Pour un module à nombreuses sections, déclarez <code>"mega": true</code> sur l'item : en mode topbar il s'affiche en <strong>grille 3 colonnes</strong> (min 580 px / max 92 vw), repli 2 colonnes sous 700 px. C'est <strong>opt-in</strong> — un menu sans <code>mega</code> reste un déroulant classique. Exemple : le module <strong>Forum</strong> (14 sections).</p>

    <h2 id="th-turbonav">TurboNav (SPA)</h2>
    <p><code>turbo-nav.js</code> transforme l'administration en application <strong>monopage</strong> : les clics de liens internes sont interceptés, le contenu est récupéré en AJAX et échangé sans rechargement complet (navigation instantanée, état du thème conservé). Activable via <strong>Configuration</strong>.</p>
    <p>Deux évènements sont émis sur <code>document</code> à chaque navigation :</p>
    <table class="doc-table">
      <tr><th>Évènement</th><th>Quand</th></tr>
      <tr><td><code>turbonav:before-swap</code></td><td>Juste avant de remplacer le contenu (detail: <code>{ url }</code>)</td></tr>
      <tr><td><code>turbonav:after-swap</code></td><td>Juste après l'injection du nouveau contenu</td></tr>
    </table>

    <h2 id="th-turbonav-js">Écrire du JS compatible</h2>
    <p><strong>Le piège à connaître.</strong> En SPA, votre script de page n'est pas rechargé comme sur un site classique : il peut s'exécuter plusieurs fois et vos <code>setInterval</code> survivent à la navigation. Deux règles :</p>
    <ol>
      <li><strong>Encapsulez</strong> tout dans une IIFE (pas de <code>const</code>/<code>function</code> globales qui se redéclarent → <em>SyntaxError</em> qui casse la page).</li>
      <li><strong>Nettoyez</strong> vos timers/écouteurs sur <code>turbonav:before-swap</code>.</li>
    </ol>
    <pre><code>(function () {
  'use strict';

  // ... votre logique de page ...
  var timer = setInterval(refresh, 5000);

  // Nettoyage avant que TurboNav ne change de page
  document.addEventListener('turbonav:before-swap', function cleanup() {
    clearInterval(timer);
    document.removeEventListener('turbonav:before-swap', cleanup);
  }, { once: true });
})();</code></pre>
    <div class="callout warn"><span class="i">⚠️</span><div>Une <code>const csrfToken = …</code> globale déclarée dans deux pages provoquera une <em>SyntaxError</em> à la seconde navigation et « cassera » les boutons. Toujours envelopper dans <code>(function(){ … })()</code> et exposer ce qu'il faut via <code>window.maFn = …</code>.</div></div>

    <h2 id="th-assets">CSS &amp; JS du thème</h2>
    <ul>
      <li><strong>CSS</strong> : <code>assets/css/admin/ui.css</code> (design system <code>.ui-*</code>) + <code>compat.css</code> (re-style des classes Bootstrap héritées).</li>
      <li><strong>JS</strong> : <code>assets/js/admin/ui.js</code> (thème, disposition, plein écran, accent, panneau, méga-menu) + <code>turbo-nav.js</code> (navigation SPA).</li>
    </ul>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
