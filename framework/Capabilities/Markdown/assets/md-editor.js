/* =============================================================================
 * Capacité Markdown — éditeur automatique.
 * Transforme tout <textarea data-md> en éditeur (barre d'outils + aperçu).
 * L'aperçu est une approximation côté client ; le rendu final, sécurisé, est
 * fait côté serveur par MarkdownService (md_render()).
 * =========================================================================== */
(function () {
    'use strict';

    // Copie d'un bloc de code (utilisé par le rendu serveur .md-code-copy)
    window.mdCopyCode = function (btn) {
        var pre = btn.parentElement.querySelector('code');
        if (!pre) return;
        navigator.clipboard.writeText(pre.innerText).then(function () {
            var old = btn.textContent; btn.textContent = '✅';
            setTimeout(function () { btn.textContent = old; }, 1200);
        });
    };

    var TOOLS = [
        { t: 'B',  title: 'Gras',        wrap: ['**', '**'], ph: 'texte' },
        { t: 'I',  title: 'Italique',    wrap: ['*', '*'],   ph: 'texte' },
        { t: 'S',  title: 'Barré',       wrap: ['~~', '~~'], ph: 'texte' },
        { t: '</>',title: 'Code inline', wrap: ['`', '`'],   ph: 'code' },
        { sep: true },
        { t: 'H',  title: 'Titre',       line: '## ',  ph: 'Titre' },
        { t: '“”', title: 'Citation',    line: '> ',   ph: 'citation' },
        { t: '•',  title: 'Liste',       line: '- ',   ph: 'élément' },
        { t: '1.', title: 'Liste num.',  line: '1. ',  ph: 'élément' },
        { sep: true },
        { t: '🔗', title: 'Lien',        tpl: '[texte](https://)' },
        { t: '🖼️', title: 'Image',       tpl: '![alt](https://)' },
        { t: '⧉',  title: 'Bloc de code',tpl: '\n```\ncode\n```\n' },
        { t: '👁', title: 'Spoiler',     tpl: '[spoiler]texte caché[/spoiler]' }
    ];

    function surround(ta, before, after, placeholder) {
        var s = ta.selectionStart, e = ta.selectionEnd;
        var sel = ta.value.slice(s, e) || placeholder || '';
        ta.value = ta.value.slice(0, s) + before + sel + after + ta.value.slice(e);
        ta.focus();
        ta.selectionStart = s + before.length;
        ta.selectionEnd   = s + before.length + sel.length;
        ta.dispatchEvent(new Event('input'));
    }

    function prefixLines(ta, prefix, placeholder) {
        var s = ta.selectionStart, e = ta.selectionEnd;
        var val = ta.value;
        var lineStart = val.lastIndexOf('\n', s - 1) + 1;
        var seg = val.slice(lineStart, e) || placeholder || '';
        var out = seg.split('\n').map(function (l) { return prefix + l; }).join('\n');
        ta.value = val.slice(0, lineStart) + out + val.slice(e);
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    function insert(ta, tpl) {
        var s = ta.selectionStart;
        ta.value = ta.value.slice(0, s) + tpl + ta.value.slice(ta.selectionEnd);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = s + tpl.length;
        ta.dispatchEvent(new Event('input'));
    }

    // Aperçu client léger (approximation ; échappe le HTML d'abord)
    function esc(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }
    function preview(md) {
        var h = esc(md);
        h = h.replace(/```([\s\S]*?)```/g, function (_, c) { return '<pre class="md-code-block"><code>' + c.trim() + '</code></pre>'; });
        h = h.replace(/^#{4}\s+(.+)$/gm, '<h4 class="md-heading">$1</h4>')
             .replace(/^#{3}\s+(.+)$/gm, '<h3 class="md-heading">$1</h3>')
             .replace(/^#{2}\s+(.+)$/gm, '<h2 class="md-heading">$1</h2>')
             .replace(/^#\s+(.+)$/gm,   '<h1 class="md-heading">$1</h1>');
        h = h.replace(/^\s*>\s?(.+)$/gm, '<blockquote class="md-blockquote">$1</blockquote>');
        h = h.replace(/(?:^\s*[-*+]\s+.+\n?)+/gm, function (m) {
            return '<ul class="md-list">' + m.trim().split('\n').map(function (l) {
                return '<li>' + l.replace(/^\s*[-*+]\s+/, '') + '</li>';
            }).join('') + '</ul>';
        });
        h = h.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img class="md-img" alt="$1" src="$2">')
             .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a class="md-link" href="$2">$1</a>')
             .replace(/`([^`]+)`/g, '<code class="md-inline-code">$1</code>')
             .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
             .replace(/\*(.+?)\*/g, '<em>$1</em>')
             .replace(/~~(.+?)~~/g, '<s>$1</s>');
        h = h.split(/\n{2,}/).map(function (b) {
            b = b.trim();
            if (!b) return '';
            if (/^<(h[1-6]|ul|ol|blockquote|pre|div)/.test(b)) return b;
            return '<p class="md-p">' + b.replace(/\n/g, '<br>') + '</p>';
        }).join('\n');
        return h;
    }

    function build(ta) {
        if (ta.dataset.mdReady) return;
        ta.dataset.mdReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'md-editor';
        var bar = document.createElement('div');
        bar.className = 'md-toolbar';

        TOOLS.forEach(function (tool) {
            if (tool.sep) {
                var sp = document.createElement('span');
                sp.className = 'md-tool-sep';
                bar.appendChild(sp);
                return;
            }
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'md-tool';
            b.textContent = tool.t;
            b.title = tool.title;
            b.addEventListener('click', function () {
                if (tool.wrap) surround(ta, tool.wrap[0], tool.wrap[1], tool.ph);
                else if (tool.line) prefixLines(ta, tool.line, tool.ph);
                else if (tool.tpl) insert(ta, tool.tpl);
            });
            bar.appendChild(b);
        });

        var pv = document.createElement('button');
        pv.type = 'button';
        pv.className = 'md-tool md-tool-preview';
        pv.textContent = '👁 Aperçu';
        pv.title = 'Basculer aperçu / édition';
        bar.appendChild(pv);

        var prevBox = document.createElement('div');
        prevBox.className = 'md-preview md-body';

        ta.parentNode.insertBefore(wrap, ta);
        wrap.appendChild(bar);
        wrap.appendChild(ta);
        wrap.appendChild(prevBox);

        pv.addEventListener('click', function () {
            var on = wrap.classList.toggle('md-show-preview');
            pv.classList.toggle('active', on);
            if (on) {
                var v = ta.value.trim();
                prevBox.innerHTML = v ? preview(ta.value) : '<span class="md-preview-empty">Rien à prévisualiser…</span>';
            }
        });
    }

    function init() {
        document.querySelectorAll('textarea[data-md]').forEach(build);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
