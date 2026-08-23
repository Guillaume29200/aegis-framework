<?php
/**
 * documentation/framework/doc-templating.php — Moteur de templates & thèmes publics.
 */
$docPage = 'framework/doc-templating.php';
$seo = [
    'title'     => 'Moteur de templates & thèmes publics — Documentation · Aegis Framework',
    'desc'      => "Le moteur de templates d'Aegis : des thèmes en HTML pur, sans PHP, donc sûrs à téléverser. Structure /themes/<clé>/assets/, meta.json, syntaxe, filtres, ThemeManager et installation par ZIP.",
    'canonical' => 'https://gamenodepanel.com/documentation/framework/doc-templating.php',
];
require __DIR__ . '/../inc/head.php';

$code_tree = <<<'TXT'
MonModule/
└── themes/
    └── default/                 # la clé du thème = le nom du dossier
        ├── meta.json            # OBLIGATOIRE — carte d'identité + options
        ├── header.html          # OBLIGATOIRE
        ├── footer.html          # OBLIGATOIRE
        ├── home.html            # OBLIGATOIRE
        ├── list.html            # selon les gabarits demandés
        ├── item.html
        ├── preview.png          # vignette montrée dans l'admin (optionnel)
        └── assets/              # OBLIGATOIRE — les 4 dossiers ci-dessous
            ├── css/
            │   └── theme.css
            ├── js/
            │   └── theme.js
            ├── images/          # images livrées avec le thème
            └── uploads/         # images téléversées par l'administrateur
TXT;

$code_meta = <<<'JSON'
{
    "name": "Default",
    "icon": "🎨",
    "desc": "Thème livré avec le module. Sert aussi de repli.",
    "author": "Aegis",
    "version": "1.0.0",
    "options": [
        {
            "key": "brand_name",
            "type": "text",
            "label": "🏷️ Nom affiché",
            "group": "🧭 En-tête",
            "help": "Laissez vide pour reprendre le nom du site.",
            "default": ""
        },
        {
            "key": "accent",
            "type": "color",
            "label": "🎨 Couleur d'accent",
            "group": "🎨 Couleurs",
            "default": "#6d5efc"
        },
        {
            "key": "show_hero",
            "type": "toggle",
            "label": "🖼️ Afficher la bannière",
            "group": "🏠 Accueil",
            "default": true
        }
    ]
}
JSON;

$code_syntax = <<<'TPL'
{{ site.name }}                     Variable, échappée automatiquement
{{{ body_end }}}                    Variable brute — HTML non échappé
{{ item.title | upper }}            Filtre
{{ item.created_at | date:d/m/Y }}  Filtre avec argument
{{ countries.0.flag }}              Index numérique dans un tableau

{% if theme.show_hero %} … {% endif %}
{% if theme.show_hero %} … {% else %} … {% endif %}
{% if not items %}Rien à afficher.{% endif %}

{% for item in items %}
    {{ item.title }}
{% empty %}
    Aucun élément pour l'instant.
{% endfor %}

{% for item in items limit:3 %} … {% endfor %}

{% include "partials/card" %}
TPL;

$code_manager = <<<'PHP'
<?php
namespace MonModule\Services;

use Framework\Templating\ThemeManager;
use Framework\Templating\ThemeSettings;
use Framework\Services\Database;

final class MonModuleThemes
{
    /** Le thème livré avec le module : il sert de repli et ne s'efface pas. */
    public const FALLBACK = 'default';

    public static function manager(Database $db): ThemeManager
    {
        // ThemeSettings lit/écrit dans la table de réglages du module.
        // Par défaut : colonnes setting_key / setting_value.
        return new ThemeManager(
            'MonModule',
            new ThemeSettings($db, 'monmodule_settings'),
            self::FALLBACK
        );
    }
}
PHP;

$code_render = <<<'PHP'
// Dans le contrôleur public :
$themes = MonModuleThemes::manager($this->db);

echo $themes->render('home', [
    'site'  => ['name' => 'Mon site'],
    'theme' => $themes->optionValues($themes->activeKey()),
    'items' => $this->service->latest(6),
]);
PHP;

$code_traps = <<<'TPL'
{{ theme.brand_name | default:site.name }}
    → affiche littéralement « site.name »

{% if theme.brand_name %}{{ theme.brand_name }}{% else %}{{ site.name }}{% endif %}
    → correct

{{ total }} élément{% if total %}s{% endif %}
    → écrit « 1 éléments » : 1 est vrai

{{ total_label }}   avec, côté PHP :
    'total_label' => $total . ' élément' . ($total > 1 ? 's' : '')
    → correct

{{ score | default:– }}
    → un score de 0 affiche « – » : 0 est faux
TPL;
?>

    <h1>Moteur de templates &amp; thèmes publics</h1>
    <p class="doc-lead">Les parties <strong>publiques</strong> d'Aegis ne s'écrivent pas en PHP. Elles s'écrivent en <strong>HTML pur</strong>, dans des thèmes qu'un administrateur peut téléverser sans risque — parce qu'un gabarit ne peut <em>rien exécuter</em>.</p>
    <div class="doc-meta">
      <span class="doc-pill">Framework\Templating</span>
      <span class="doc-pill">HTML sans PHP</span>
      <span class="doc-pill">Thèmes téléversables</span>
    </div>

    <h2 id="tpl-why">Pourquoi du HTML et pas du PHP</h2>
    <p>Un thème est du contenu fourni par l'utilisateur. S'il contenait du PHP, l'installer reviendrait à <strong>exécuter du code arbitraire sur le serveur</strong> — un téléversement de thème deviendrait une porte d'entrée.</p>
    <p>Le moteur d'Aegis lève ce risque à la racine : la syntaxe ne sait <strong>ni comparer, ni calculer, ni appeler une fonction</strong>. Elle sait afficher une valeur, tester si elle est vraie, et boucler. C'est tout.</p>
    <div class="callout ok"><span class="i">🛡️</span><div>Cette limite n'est pas une faiblesse, c'est la fonctionnalité. Tout ce qui relève d'une décision se prépare <strong>côté PHP</strong> et arrive dans les données. Le gabarit ne fait que poser.</div></div>

    <h2 id="tpl-structure">Structure d'un thème</h2>
    <p>Un thème est un <strong>dossier autonome</strong>. La clé du thème est le nom du dossier. Cette structure est <strong>imposée</strong> : le générateur la crée toujours, et l'installation par ZIP la vérifie.</p>
    <div class="tree"><?= $h($code_tree) ?></div>
    <table class="doc-table">
      <tr><th>Élément</th><th>Rôle</th></tr>
      <tr><td><code>meta.json</code></td><td>Nom, icône, description, auteur, version, et la <strong>déclaration des options</strong>. Sans ce fichier, le dossier n'est pas un thème.</td></tr>
      <tr><td><code>header.html</code> / <code>footer.html</code></td><td>Encadrent chaque page. Ils portent <code>{{{ head_extra }}}</code> et <code>{{{ body_end }}}</code> — voir <a href="framework/doc-capabilities.php">Fonctionnalités</a>.</td></tr>
      <tr><td><code>assets/css</code>, <code>assets/js</code></td><td>Feuilles de style et scripts du thème.</td></tr>
      <tr><td><code>assets/images</code></td><td>Images <strong>livrées</strong> avec le thème (logo par défaut, décors…).</td></tr>
      <tr><td><code>assets/uploads</code></td><td>Images <strong>téléversées</strong> par l'administrateur via les options de type <code>image</code>.</td></tr>
    </table>
    <div class="callout"><span class="i">📁</span><div>Les quatre dossiers d'<code>assets/</code> sont créés d'office, même vides (<code>ThemeManager::ASSET_DIRS</code>). Un thème n'a jamais à se demander où déposer un fichier.</div></div>

    <h2 id="tpl-meta">Le manifeste — <code>meta.json</code></h2>
    <p>Il décrit le thème et, surtout, <strong>déclare ses options</strong>. Chaque option déclarée devient un champ dans l'écran d'administration du thème, rangé sous son <code>group</code>. Aucune ligne de PHP à écrire pour cela.</p>
    <pre><code><?= $h($code_meta) ?></code></pre>
    <table class="doc-table">
      <tr><th>Type</th><th>Champ produit</th></tr>
      <tr><td><code>text</code></td><td>Ligne de texte</td></tr>
      <tr><td><code>textarea</code></td><td>Bloc de texte</td></tr>
      <tr><td><code>number</code></td><td>Nombre</td></tr>
      <tr><td><code>toggle</code></td><td>Interrupteur — la valeur vaut <code>true</code> / <code>false</code></td></tr>
      <tr><td><code>select</code></td><td>Liste déroulante (fournir <code>options</code>)</td></tr>
      <tr><td><code>color</code></td><td>Sélecteur de couleur</td></tr>
      <tr><td><code>image</code></td><td>Téléversement, rangé dans <code>assets/uploads/</code></td></tr>
      <tr><td><code>links</code></td><td>Liste de liens (libellé + URL), répétable</td></tr>
    </table>
    <p>Les valeurs sont stockées dans la table de réglages du module, sous la clé <code>theme_option.&lt;thème&gt;.&lt;option&gt;</code>. Deux thèmes ne se marchent donc jamais dessus : changer de thème puis revenir <strong>retrouve ses réglages</strong>.</p>

    <h3 id="tpl-links">Le type <code>links</code></h3>
    <p>L'administrateur saisit une liste, une entrée par ligne, au format <code>Libellé | url</code>. Le gabarit la reçoit <strong>déjà découpée</strong> :</p>
    <pre><code><?= $h(<<<'TPL'
Accueil        | /
Nos offres     | /offres
Discord        | https://discord.gg/exemple

{% for link in theme.footer_links %}
  <a href="{{ link.url }}"{% if link.is_external %} target="_blank" rel="noopener"{% endif %}>
    {{ link.label }}
  </a>
{% endfor %}
TPL) ?></code></pre>
    <table class="doc-table">
      <tr><th>Clé</th><th>Contenu</th></tr>
      <tr><td><code>link.label</code></td><td>Le libellé saisi.</td></tr>
      <tr><td><code>link.url</code></td><td>L'adresse. Un chemin interne est <strong>préfixé automatiquement</strong> par la base du site — il survit donc au renommage du préfixe public du module.</td></tr>
      <tr><td><code>link.is_external</code></td><td>Vrai si l'adresse pointe hors du site. Le gabarit ne saurait pas le déterminer seul : il ne sait pas comparer.</td></tr>
    </table>
    <p>Deux variantes accompagnent chaque option de ce type : <code>&lt;clé&gt;_raw</code> donne le texte tel qu'il a été saisi, et <code>&lt;clé&gt;_items</code> est un alias historique de la liste découpée — les deux écritures fonctionnent, car un thème déjà distribué en ZIP ne peut pas être réécrit après coup.</p>
    <div class="callout"><span class="i">🔗</span><div>Une ligne incomplète (libellé sans URL, ou l'inverse) est <strong>ignorée</strong> plutôt que rendue en lien mort.</div></div>

    <h2 id="tpl-syntax">La syntaxe</h2>
    <pre><code><?= $h($code_syntax) ?></code></pre>
    <table class="doc-table">
      <tr><th>Balise</th><th>Effet</th></tr>
      <tr><td><code>{{ chemin }}</code></td><td>Affiche la valeur, <strong>échappée</strong> (<code>htmlspecialchars</code>).</td></tr>
      <tr><td><code>{{{ chemin }}}</code></td><td>Affiche la valeur <strong>brute</strong>. À réserver au HTML que vous avez produit vous-même.</td></tr>
      <tr><td><code>{% if %}</code> / <code>{% else %}</code> / <code>{% endif %}</code></td><td>Teste si la valeur est <strong>vraie</strong> — pas de comparaison possible.</td></tr>
      <tr><td><code>{% if not %}</code></td><td>La négation.</td></tr>
      <tr><td><code>{% for x in liste %}</code> … <code>{% empty %}</code> … <code>{% endfor %}</code></td><td>Boucle, avec le cas « liste vide » traité sur place.</td></tr>
      <tr><td><code>limit:N</code></td><td>Limite la boucle. <strong>N est un nombre écrit en dur</strong>, jamais une variable.</td></tr>
      <tr><td><code>{% include "nom" %}</code></td><td>Insère un autre gabarit du même thème.</td></tr>
    </table>
    <div class="callout warn"><span class="i">🚫</span><div>Ce qui <strong>n'existe pas</strong>, et n'existera pas : <code>{% if a == b %}</code>, <code>{{ a + b }}</code>, <code>{{ maFonction() }}</code>, une limite de boucle variable. Si vous en avez besoin, la réponse est de le préparer en PHP.</div></div>

    <h2 id="tpl-filters">Les filtres</h2>
    <table class="doc-table">
      <tr><th>Filtre</th><th>Effet</th><th>Exemple</th></tr>
      <tr><td><code>upper</code> / <code>lower</code></td><td>Casse</td><td><code>{{ nom | upper }}</code></td></tr>
      <tr><td><code>date</code></td><td>Date formatée, <strong>en français</strong> (défaut <code>d/m/Y</code>)</td><td><code>{{ d | date:d F Y }}</code> → 08 mars 2026</td></tr>
      <tr><td><code>datetime</code></td><td>Idem, avec l'heure (défaut <code>d/m/Y H:i</code>)</td><td><code>{{ d | datetime }}</code></td></tr>
      <tr><td><code>truncate</code></td><td>Coupe et ajoute une ellipse</td><td><code>{{ texte | truncate:120 }}</code></td></tr>
      <tr><td><code>nl2br</code></td><td>Retours à la ligne en <code>&lt;br&gt;</code> — <strong>échappe avant</strong></td><td><code>{{ texte | nl2br }}</code></td></tr>
      <tr><td><code>number</code></td><td>Séparateurs français (espace, virgule)</td><td><code>{{ prix | number:2 }}</code> → 1 234,50</td></tr>
      <tr><td><code>count</code></td><td>Nombre d'éléments d'un tableau</td><td><code>{{ items | count }}</code></td></tr>
      <tr><td><code>initials</code></td><td>Initiales (défaut 2 lettres)</td><td><code>{{ pseudo | initials }}</code></td></tr>
      <tr><td><code>default</code></td><td>Valeur de repli si la valeur est fausse</td><td><code>{{ ville | default:Inconnue }}</code></td></tr>
    </table>
    <p>Les mois et les jours sont rendus en français par le moteur lui-même : <code>d F Y</code> donne « 08 mars 2026 », <code>l d M</code> donne « samedi 01 août ». Les échappements <code>\</code> de PHP sont respectés (<code>\L\e d F</code> → « Le 08 mars »).</p>

    <h2 id="tpl-traps">Les pièges — à lire avant d'écrire un thème</h2>
    <p>Trois erreurs reviennent systématiquement. Elles viennent toutes du même endroit : le moteur teste la <strong>véracité</strong>, il ne compare pas.</p>
    <pre><code><?= $h($code_traps) ?></code></pre>
    <table class="doc-table">
      <tr><th>Piège</th><th>Ce qui se passe</th><th>La règle</th></tr>
      <tr><td><code>default:</code> avec un chemin</td><td>L'argument est <strong>toujours une chaîne littérale</strong>. <code>default:site.name</code> écrit les neuf caractères « site.name ».</td><td>Pour un repli sur une autre variable, utilisez <code>{% if %}{% else %}{% endif %}</code>.</td></tr>
      <tr><td>Le pluriel</td><td><code>{% if total %}s{% endif %}</code> écrit « 1 éléments », car 1 est vrai.</td><td>Accordez en PHP et envoyez un libellé déjà écrit.</td></tr>
      <tr><td>La valeur zéro</td><td><code>0</code> est faux : <code>default:</code> le remplace, <code>{% if %}</code> le saute. Un score de 0 disparaît.</td><td>Envoyez un <code>_label</code> préparé en PHP quand zéro est une valeur légitime.</td></tr>
    </table>
    <div class="callout"><span class="i">🎓</span><div>Ces trois pièges sont commentés en situation dans le module <strong>Exemple</strong>, livré avec le framework — voir son <code>README.md</code> et <code>themes/default/header.html</code>.</div></div>

    <h2 id="tpl-manager">Le gestionnaire de thèmes</h2>
    <p><code>Framework\Templating\ThemeManager</code> fait tout le travail : trouver les thèmes, lire leurs manifestes, appliquer les options, rendre les gabarits, installer un ZIP, servir les assets. Un module s'y branche en <strong>une classe de dix lignes</strong>.</p>
    <pre><code><?= $h($code_manager) ?></code></pre>
    <p>Et le rendu tient en un appel :</p>
    <pre><code><?= $h($code_render) ?></code></pre>
    <table class="doc-table">
      <tr><th>Méthode</th><th>Rôle</th></tr>
      <tr><td><code>activeKey()</code> / <code>setActive($clé)</code></td><td>Lire / changer le thème actif</td></tr>
      <tr><td><code>availableThemes()</code></td><td>Tous les thèmes trouvés, avec leur manifeste et leur vignette</td></tr>
      <tr><td><code>render($gabarit, $données)</code></td><td>Rend un gabarit du thème actif</td></tr>
      <tr><td><code>declaredOptions($clé)</code></td><td>Les options déclarées dans <code>meta.json</code></td></tr>
      <tr><td><code>optionValues($clé)</code></td><td>Les valeurs courantes, défauts appliqués</td></tr>
      <tr><td><code>saveOptions($clé, $post, $files)</code></td><td>Enregistre le formulaire d'options, <strong>téléversements compris</strong></td></tr>
      <tr><td><code>installZip($fichier)</code></td><td>Installe un thème téléversé, après contrôle</td></tr>
      <tr><td><code>delete($clé)</code></td><td>Supprime un thème</td></tr>
      <tr><td><code>assetsUrl($clé)</code> / <code>streamAsset()</code></td><td>URL publique et service des fichiers du thème</td></tr>
      <tr><td><code>ensureAssetDirs($clé)</code></td><td>Recrée les quatre dossiers d'assets s'ils manquent</td></tr>
    </table>

    <h2 id="tpl-fallback">Le repli par gabarit</h2>
    <p>Un thème n'a <strong>pas besoin de fournir tous les gabarits</strong>. Si <code>item.html</code> manque dans le thème actif, le moteur va le chercher dans le thème de repli (le troisième argument du constructeur, en général <code>default</code>).</p>
    <div class="callout ok"><span class="i">🧩</span><div>Conséquence pratique : un thème peut ne redéfinir que <code>header.html</code> et une feuille de style. Tout le reste continue de fonctionner. C'est ce qui rend les thèmes légers à écrire.</div></div>

    <h2 id="tpl-zip">Téléverser un thème</h2>
    <p>L'administrateur installe un thème par <strong>ZIP</strong>, depuis l'écran Thèmes du module. L'archive est refusée si :</p>
    <ul>
      <li>elle contient un chemin remontant (<code>../</code>) ou absolu ;</li>
      <li>elle contient une extension non autorisée — <strong>aucun type exécutable n'est dans la liste blanche</strong> ;</li>
      <li>il n'y a pas de <code>meta.json</code> à la racine du dossier du thème ;</li>
      <li>l'archive contient plusieurs dossiers racine, ou des fichiers directement à sa racine.</li>
    </ul>
    <p>Les dossiers <code>__MACOSX/</code> et les fichiers <code>.DS_Store</code> sont ignorés silencieusement — une archive faite sur un Mac s'installe donc normalement.</p>
    <div class="callout"><span class="i">🔒</span><div>Le contrôle a lieu <strong>avant extraction</strong> : rien n'est écrit sur le disque tant que l'archive entière n'a pas été jugée saine.</div></div>

    <h2 id="tpl-assets">Servir les assets</h2>
    <p>Les fichiers d'un thème ne sont pas exposés directement : ils passent par une route du module, qui délègue à <code>streamAsset()</code>. Dans vos gabarits, l'URL vient des données :</p>
    <pre><code><?= $h('<link rel="stylesheet" href="{{ urls.assets }}/css/theme.css">' . "\n" . '<script src="{{ urls.assets }}/js/theme.js" defer></script>') ?></code></pre>
    <p>Ne codez jamais un chemin de thème en dur dans un gabarit : <code>{{ urls.assets }}</code> suit automatiquement le thème actif.</p>

    <div class="doc-foot">
      <span>Moteur de templates · <code>framework/Templating/</code></span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../inc/foot.php'; ?>
