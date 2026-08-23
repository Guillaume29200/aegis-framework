<?php
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
admin_header('Assistant IA — Aegis Framework');

$flashType = $_GET['flash_type'] ?? null;
$flashMsg  = $_GET['flash_msg'] ?? null;
$activeId  = $activeConversation['id'] ?? 0;

$defaultProviderKey = $activeConversation['provider'] ?? ($providers[0]['key'] ?? '');
$defaultModel        = $activeConversation['model_name'] ?? '';
if ($defaultModel === '' && !empty($providers)) {
    foreach ($providers as $p) {
        if ($p['key'] === $defaultProviderKey && !empty($p['models'])) { $defaultModel = $p['models'][0]['model_name']; break; }
    }
}
?>
<style>
.aegis-chat-shell { height: calc(100vh - var(--header-h) - 52px); min-height: 480px; display: flex; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; background: var(--surface); box-shadow: var(--shadow-sm); }

.aegis-chat-sidebar { width: 280px; flex-shrink: 0; border-right: 1px solid var(--border); background: var(--surface-2); display: flex; flex-direction: column; }
.aegis-chat-sidebar-head { padding: 14px; border-bottom: 1px solid var(--border); }
.aegis-chat-newbtn { width: 100%; }
.aegis-chat-list { flex: 1; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 3px; }
.aegis-chat-item { position: relative; display: block; padding: 10px 34px 10px 12px; border-radius: var(--radius-sm); text-decoration: none; color: var(--text); transition: var(--t-fast); }
.aegis-chat-item:hover { background: var(--surface-3); }
.aegis-chat-item.active { background: var(--accent-soft); color: var(--accent); font-weight: 600; }
.aegis-chat-item-title { display: block; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.aegis-chat-item-meta { display: block; font-size: 11px; color: var(--text-faint); margin-top: 2px; }
.aegis-chat-item.active .aegis-chat-item-meta { color: var(--accent); opacity: .75; }
.aegis-chat-item-del { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 22px; height: 22px; border: none; background: transparent; border-radius: 6px; color: var(--text-faint); cursor: pointer; opacity: 0; transition: var(--t-fast); display: flex; align-items: center; justify-content: center; font-size: 12px; }
.aegis-chat-item:hover .aegis-chat-item-del { opacity: 1; }
.aegis-chat-item-del:hover { background: var(--red-soft); color: var(--red); }
.aegis-chat-empty-list { padding: 18px 12px; font-size: 12.5px; color: var(--text-faint); text-align: center; }

.aegis-chat-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.aegis-chat-topbar { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.aegis-chat-topbar select { max-width: 220px; }
.aegis-chat-tokens { margin-left: auto; font-size: 12px; color: var(--text-soft); white-space: nowrap; }

.aegis-chat-thread { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.aegis-chat-hero { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 8px; padding: 20px; }
.aegis-chat-hero .icon { font-size: 46px; }
.aegis-chat-hero h2 { margin: 0; font-size: 19px; }
.aegis-chat-hero p { margin: 0; color: var(--text-soft); font-size: 13.5px; max-width: 420px; }

.aegis-msg { display: flex; gap: 10px; max-width: 780px; }
.aegis-msg.user { align-self: flex-end; flex-direction: row-reverse; }
.aegis-msg .avatar { width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 15px; background: var(--surface-2); }
.aegis-msg.assistant .avatar { background: var(--accent-soft); }
.aegis-msg-body { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.aegis-msg-bubble { padding: 10px 14px; border-radius: 14px; font-size: 13.5px; line-height: 1.55; white-space: normal; word-wrap: break-word; }
.aegis-msg.user .aegis-msg-bubble { background: var(--accent); color: var(--on-accent); border-bottom-right-radius: 4px; }
.aegis-msg.assistant .aegis-msg-bubble { background: var(--surface-2); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
.aegis-msg.assistant .aegis-msg-bubble.error { background: var(--red-soft); color: var(--red); border-color: var(--red-soft); }
.aegis-msg-meta { font-size: 10.5px; color: var(--text-faint); padding: 0 4px; }
.aegis-msg.user .aegis-msg-meta { text-align: right; }
.aegis-code { background: var(--surface-3); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; font-size: 12px; font-family: ui-monospace, Consolas, monospace; overflow-x: auto; white-space: pre-wrap; margin: 4px 0; }
.aegis-msg-bubble code:not(.aegis-code code) { background: var(--surface-3); padding: 1px 5px; border-radius: 4px; font-size: 12px; font-family: ui-monospace, Consolas, monospace; }

.aegis-chat-composer { border-top: 1px solid var(--border); padding: 12px 16px; }
.aegis-chat-composer-row { display: flex; gap: 10px; align-items: flex-end; }
.aegis-chat-composer textarea { flex: 1; resize: none; max-height: 160px; }
.aegis-chat-typing { display: flex; gap: 4px; padding: 4px 0; }
.aegis-chat-typing span { width: 6px; height: 6px; border-radius: 50%; background: var(--text-faint); animation: aegis-typing 1.1s ease-in-out infinite; }
.aegis-chat-typing span:nth-child(2) { animation-delay: .15s; }
.aegis-chat-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes aegis-typing { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

.aegis-chat-noprovider { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 12px; padding: 30px; }
.aegis-chat-updated { font-size: 11.5px; color: var(--text-faint); display: flex; align-items: center; gap: 6px; }
</style>

<div class="adm-page-head" style="margin-bottom:14px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><span>Assistant IA</span></div>
        <h1>🧠 Assistant IA</h1>
    </div>
    <?php if ($primerLastUpdated): ?>
    <span class="aegis-chat-updated" title="Date de dernière mise à jour de la base de connaissances Aegis Framework injectée dans l'assistant">🕓 Connaissances Aegis Framework mises à jour le <?= htmlspecialchars($primerLastUpdated) ?></span>
    <?php endif; ?>
</div>

<?php if ($flashType && $flashMsg): ?>
<div class="alert <?= $flashType === 'ok' ? 'alert-success' : 'alert-warning' ?>"><?= htmlspecialchars($flashMsg) ?></div>
<?php endif; ?>

<div class="aegis-chat-shell">
    <aside class="aegis-chat-sidebar">
        <div class="aegis-chat-sidebar-head">
            <a href="<?= u('/admin/chat') ?>" class="ui-btn primary sm aegis-chat-newbtn">+ Nouvelle conversation</a>
        </div>
        <div class="aegis-chat-list">
            <?php if (empty($conversations)): ?>
            <div class="aegis-chat-empty-list">Aucune conversation pour le moment.</div>
            <?php else: foreach ($conversations as $c): ?>
            <a href="<?= u('/admin/chat/' . (int)$c['id']) ?>" class="aegis-chat-item <?= (int)$c['id'] === $activeId ? 'active' : '' ?>">
                <span class="aegis-chat-item-title"><?= htmlspecialchars($c['title']) ?></span>
                <span class="aegis-chat-item-meta"><?= htmlspecialchars($c['provider']) ?> · <?= date('d/m H:i', strtotime($c['updated_at'])) ?></span>
                <button type="button" class="aegis-chat-item-del" data-del-conv="<?= (int)$c['id'] ?>" title="Supprimer">✕</button>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </aside>

    <div class="aegis-chat-main">
        <?php if (empty($providers)): ?>
        <div class="aegis-chat-noprovider">
            <div style="font-size:40px">🔌</div>
            <h2 style="margin:0">Aucun provider IA configuré</h2>
            <p class="u-muted" style="max-width:420px;margin:0">Ajoutez au moins une clé API (OpenAI, Claude ou Mistral) pour utiliser l'assistant.</p>
            <a href="<?= u('/admin/configuration') ?>#ai" class="ui-btn primary sm">⚙️ Configurer une clé API</a>
        </div>
        <?php else: ?>

        <div class="aegis-chat-topbar">
            <select id="aegisChatProvider" class="form-select sm">
                <?php foreach ($providers as $p): ?>
                <option value="<?= htmlspecialchars($p['key']) ?>" <?= $p['key'] === $defaultProviderKey ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="aegisChatModel" class="form-select sm"></select>
            <?php if ($activeConversation): ?>
            <span class="aegis-chat-tokens">🔢 <?= number_format((int)$activeConversation['tokens_input_total'] + (int)$activeConversation['tokens_output_total'], 0, ',', ' ') ?> tokens dans cette conversation</span>
            <?php endif; ?>
        </div>

        <div class="aegis-chat-thread" id="aegisChatThread">
            <?php if (empty($messages)): ?>
            <div class="aegis-chat-hero">
                <div class="icon">🧠</div>
                <h2>Assistant IA Aegis Framework</h2>
                <p>Posez une question sur la configuration, les modules, le code ou tout autre sujet lié à votre plateforme.</p>
            </div>
            <?php else: foreach ($messages as $m): ?>
                <?php if ($m['role'] === 'assistant' && $m['content'] === ''): ?>
                <div class="aegis-msg assistant">
                    <div class="avatar">🧠</div>
                    <div class="aegis-msg-body">
                        <div class="aegis-msg-bubble error"><?= htmlspecialchars((string)($m['error_message'] ?? 'Erreur inconnue')) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="aegis-msg <?= $m['role'] ?>">
                    <div class="avatar"><?= $m['role'] === 'user' ? '👤' : '🧠' ?></div>
                    <div class="aegis-msg-body">
                        <div class="aegis-msg-bubble aegis-msg-content" data-raw="<?= htmlspecialchars($m['content'], ENT_QUOTES) ?>"></div>
                        <?php if ($m['role'] === 'assistant' && ($m['tokens_input'] || $m['tokens_output'])): ?>
                        <span class="aegis-msg-meta"><?= (int)$m['tokens_input'] + (int)$m['tokens_output'] ?> tokens</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; endif; ?>
        </div>

        <div class="aegis-chat-composer">
            <div class="aegis-chat-composer-row">
                <textarea id="aegisChatInput" class="form-control" rows="1" placeholder="Écrivez votre message… (Entrée pour envoyer, Maj+Entrée pour un saut de ligne)"></textarea>
                <button type="button" id="aegisChatSend" class="ui-btn primary">Envoyer</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var providersData = <?= json_encode($providers, JSON_UNESCAPED_UNICODE) ?>;
    var csrfToken = <?= json_encode($csrfToken) ?>;
    var conversationId = <?= $activeId ? (int)$activeId : 'null' ?>;
    var defaultModel = <?= json_encode($defaultModel) ?>;

    var providerSelect = document.getElementById('aegisChatProvider');
    var modelSelect = document.getElementById('aegisChatModel');
    var thread = document.getElementById('aegisChatThread');
    var input = document.getElementById('aegisChatInput');
    var sendBtn = document.getElementById('aegisChatSend');

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = String(s == null ? '' : s);
        return d.innerHTML;
    }

    function renderMarkdownLite(raw) {
        var blocks = [];
        var withPlaceholders = raw.replace(/```[a-zA-Z0-9_+-]*\n?([\s\S]*?)```/g, function(m, code) {
            blocks.push(code.replace(/\n$/, ''));
            return ' CB' + (blocks.length - 1) + ' ';
        });
        var html = escapeHtml(withPlaceholders);
        html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\n/g, '<br>');
        html = html.replace(/ CB(\d+) /g, function(m, i) {
            return '<pre class="aegis-code"><code>' + escapeHtml(blocks[i]) + '</code></pre>';
        });
        return html;
    }

    // Rendu markdown-lite des messages déjà présents au chargement de la page.
    Array.prototype.slice.call(document.querySelectorAll('.aegis-msg-content')).forEach(function(el) {
        el.innerHTML = renderMarkdownLite(el.getAttribute('data-raw') || '');
    });

    function refreshModelOptions() {
        if (!providerSelect || !modelSelect) return;
        var provider = providerSelect.value;
        var entry = providersData.filter(function(p) { return p.key === provider; })[0];
        var list = entry ? entry.models : [];
        modelSelect.innerHTML = '';
        list.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.model_name;
            opt.textContent = m.display_name;
            if (m.model_name === defaultModel) opt.selected = true;
            modelSelect.appendChild(opt);
        });
    }
    if (providerSelect) {
        providerSelect.addEventListener('change', function() { defaultModel = ''; refreshModelOptions(); });
        refreshModelOptions();
    }

    if (input) {
        input.addEventListener('input', function() {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 160) + 'px';
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doSend(); }
        });
    }
    if (sendBtn) sendBtn.addEventListener('click', doSend);

    function appendMessage(role, html, metaText, isError) {
        var hero = thread.querySelector('.aegis-chat-hero');
        if (hero) hero.remove();
        var wrap = document.createElement('div');
        wrap.className = 'aegis-msg ' + role;
        wrap.innerHTML = '<div class="avatar">' + (role === 'user' ? '👤' : '🧠') + '</div>'
            + '<div class="aegis-msg-body">'
            + '<div class="aegis-msg-bubble' + (isError ? ' error' : '') + '">' + html + '</div>'
            + (metaText ? '<span class="aegis-msg-meta">' + metaText + '</span>' : '')
            + '</div>';
        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
        return wrap;
    }

    function appendTyping() {
        var wrap = document.createElement('div');
        wrap.className = 'aegis-msg assistant';
        wrap.id = 'aegisTypingIndicator';
        wrap.innerHTML = '<div class="avatar">🧠</div><div class="aegis-msg-body"><div class="aegis-msg-bubble"><div class="aegis-chat-typing"><span></span><span></span><span></span></div></div></div>';
        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
    }
    function removeTyping() {
        var el = document.getElementById('aegisTypingIndicator');
        if (el) el.remove();
    }

    function doSend() {
        var message = (input.value || '').trim();
        if (!message) return;
        var provider = providerSelect.value;
        var model = modelSelect.value;

        appendMessage('user', escapeHtml(message).replace(/\n/g, '<br>'));
        input.value = '';
        input.style.height = 'auto';
        input.disabled = true;
        sendBtn.disabled = true;
        appendTyping();

        var body = new URLSearchParams({
            csrf_token: csrfToken,
            message: message,
            provider: provider,
            model: model
        });
        if (conversationId) body.append('conversation_id', conversationId);

        fetch('<?= u('/admin/chat/send') ?>', {
            method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body.toString()
        }).then(function(r) { return r.json(); }).then(function(d) {
            removeTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();

            if (d.ok && d.message) {
                var wasNew = !conversationId;
                conversationId = d.conversation_id;
                var meta = (d.message.tokens_input + d.message.tokens_output) + ' tokens';
                appendMessage('assistant', renderMarkdownLite(d.message.content), meta, false);
                if (wasNew) {
                    // Nouvelle conversation créée : on recharge pour la faire apparaître
                    // dans la sidebar avec la bonne URL (garde l'historique déjà affiché
                    // côté serveur au prochain chargement).
                    window.location.href = '<?= u('/admin/chat/') ?>' + conversationId;
                }
            } else {
                appendMessage('assistant', escapeHtml(d.error || 'Erreur inconnue'), null, true);
            }
        }).catch(function() {
            removeTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            appendMessage('assistant', 'Erreur réseau — impossible de contacter le serveur.', null, true);
        });
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-del-conv]')).forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = btn.getAttribute('data-del-conv');
            if (!confirm('Supprimer définitivement cette conversation ?')) return;
            fetch('<?= u('/admin/chat/') ?>' + id + '/delete', {
                method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ csrf_token: csrfToken }).toString()
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.ok) {
                    if (String(conversationId) === String(id)) {
                        window.location.href = '<?= u('/admin/chat') ?>';
                    } else {
                        btn.closest('.aegis-chat-item').remove();
                    }
                }
            });
        });
    });

    if (thread) thread.scrollTop = thread.scrollHeight;
})();
</script>

<?php admin_footer(); ?>
