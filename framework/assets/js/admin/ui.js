/* ==========================================================================
   eSport-CMS V4 — Admin UI (vanilla, sans dépendance)
   Gère : thème, disposition, plein écran, accent, panneau de contrôle,
   navigation mobile, dropdowns, et compat des widgets Bootstrap restants.
   Les préférences sont persistées en localStorage.
   La pré-application (anti-FOUC) est faite par un script inline dans <head>.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var LS = {
        theme: 'adm.theme',
        layout: 'adm.layout',
        accent: 'adm.accent',
        sidebar: 'adm.sidebar'
    };

    /* Palette d'accents proposée (la 1re = défaut « modern ») */
    var ACCENTS = {
        indigo: { hex: '#6366f1', hover: '#4f46e5', rgb: '99, 102, 241' },
        violet: { hex: '#8b5cf6', hover: '#7c3aed', rgb: '139, 92, 246' },
        blue: { hex: '#3b82f6', hover: '#2563eb', rgb: '59, 130, 246' },
        emerald: { hex: '#10b981', hover: '#059669', rgb: '16, 185, 129' },
        rose: { hex: '#f43f5e', hover: '#e11d48', rgb: '244, 63, 94' },
        amber: { hex: '#f59e0b', hover: '#d97706', rgb: '245, 158, 11' },
        cyan: { hex: '#06b6d4', hover: '#0891b2', rgb: '6, 182, 212' }
    };

    function get(k, d) { try { return localStorage.getItem(k) || d; } catch (e) { return d; } }
    function set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    /* ---------- Thème ---------- */
    var themeAnimTimer = null;
    function applyTheme(mode) {
        var effective = mode;
        if (mode === 'auto') {
            effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        // Active une transition uniforme le temps du basculement
        root.classList.add('theme-anim');
        clearTimeout(themeAnimTimer);
        themeAnimTimer = setTimeout(function () { root.classList.remove('theme-anim'); }, 280);

        root.setAttribute('data-theme', effective);
        root.setAttribute('data-theme-mode', mode);
        set(LS.theme, mode);
        syncPanel();
    }

    /* ---------- Disposition ---------- */
    function applyLayout(layout) {
        root.setAttribute('data-layout', layout);
        set(LS.layout, layout);
        syncPanel();
    }

    /* ---------- Accent ---------- */
    function applyAccent(value) {
        var conf = ACCENTS[value];
        var hex, hover, rgb;
        if (conf) {
            hex = conf.hex; hover = conf.hover; rgb = conf.rgb;
        } else {
            hex = value; hover = value; rgb = hexToRgb(value);
        }
        root.style.setProperty('--accent', hex);
        root.style.setProperty('--accent-hover', hover);
        root.style.setProperty('--accent-rgb', rgb);
        root.style.setProperty('--accent-soft', 'rgba(' + rgb + ', .12)');
        set(LS.accent, value);
        syncPanel();
    }

    function hexToRgb(hex) {
        var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return m ? (parseInt(m[1], 16) + ', ' + parseInt(m[2], 16) + ', ' + parseInt(m[3], 16)) : '99, 102, 241';
    }

    /* ---------- Sidebar repliée ---------- */
    function toggleSidebar() {
        var v = root.getAttribute('data-sidebar') === 'collapsed' ? 'expanded' : 'collapsed';
        root.setAttribute('data-sidebar', v);
        set(LS.sidebar, v);
    }

    /* ---------- Plein écran ---------- */
    function toggleFullscreen() {
        try {
            if (!document.fullscreenElement) {
                var el = document.documentElement;
                var req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
                if (req) { req.call(el); }
            } else {
                var exit = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
                if (exit) { exit.call(document); }
            }
        } catch (e) { /* ignoré */ }
    }
    document.addEventListener('fullscreenchange', function () {
        root.setAttribute('data-fullscreen', document.fullscreenElement ? 'on' : 'off');
        var btns = document.querySelectorAll('[data-action="fullscreen"] .adm-fs-icon');
        btns.forEach(function (el) { el.textContent = document.fullscreenElement ? '🗗' : '⛶'; });
    });

    /* ---------- Navigation mobile ---------- */
    function setMobNav(open) { root.setAttribute('data-mobnav', open ? 'open' : 'closed'); }

    /* ---------- Panneau de contrôle ---------- */
    function setPanel(open) { root.setAttribute('data-panel', open ? 'open' : 'closed'); }

    function syncPanel() {
        var themeMode = root.getAttribute('data-theme-mode') || 'light';
        var layout = root.getAttribute('data-layout') || 'sidebar';
        var accent = get(LS.accent, 'indigo');
        document.querySelectorAll('[data-set-theme]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-set-theme') === themeMode);
        });
        document.querySelectorAll('[data-set-layout]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-set-layout') === layout);
        });
        document.querySelectorAll('[data-set-accent]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-set-accent') === accent);
        });
    }

    /* ---------- Délégation de clics ---------- */
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-action], [data-set-theme], [data-set-layout], [data-set-accent], [data-toggle-submenu]');

        /* Toggle sous-menu sidebar */
        var sub = e.target.closest('[data-toggle-submenu]');
        if (sub) {
            e.preventDefault();
            var item = sub.closest('.adm-nav-item');
            if (item) item.classList.toggle('open');
            return;
        }

        /* Toggle mega-menu (topbar) : seuls les items parents (span) togglent */
        var topLink = e.target.closest('.adm-topnav-link');
        if (topLink && topLink.tagName !== 'A') {
            var topItem = topLink.closest('.adm-topnav-item');
            if (topItem && topItem.querySelector('.adm-topnav-sub')) {
                e.preventDefault();
                var wasOpen = topItem.classList.contains('open');
                document.querySelectorAll('.adm-topnav-item.open').forEach(function (i) { i.classList.remove('open'); });
                if (!wasOpen) topItem.classList.add('open');
                return;
            }
        }

        if (!t) {
            /* Clic à l'extérieur : fermer dropdowns user + mega-menus ouverts */
            document.querySelectorAll('.adm-user.open').forEach(function (u) { u.classList.remove('open'); });
            if (!e.target.closest('.adm-topnav-item')) {
                document.querySelectorAll('.adm-topnav-item.open').forEach(function (i) { i.classList.remove('open'); });
            }
            return;
        }

        if (t.hasAttribute('data-set-theme')) { applyTheme(t.getAttribute('data-set-theme')); return; }
        if (t.hasAttribute('data-set-layout')) { applyLayout(t.getAttribute('data-set-layout')); return; }
        if (t.hasAttribute('data-set-accent')) { applyAccent(t.getAttribute('data-set-accent')); return; }

        var action = t.getAttribute('data-action');
        switch (action) {
            case 'panel-open':
                setPanel(true);
                document.querySelectorAll('.adm-user.open').forEach(function (u) { u.classList.remove('open'); });
                break;
            case 'panel-close': setPanel(false); break;
            case 'theme-toggle':
                applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); break;
            case 'fullscreen': toggleFullscreen(); break;
            case 'sidebar-toggle': toggleSidebar(); break;
            case 'mobnav-open': setMobNav(true); break;
            case 'mobnav-close': setMobNav(false); break;
            case 'user-toggle':
                e.stopPropagation();
                t.closest('.adm-user').classList.toggle('open'); break;
            case 'dismiss-install':
                var w = document.getElementById('adm-install-warn');
                if (w) { w.style.display = 'none'; try { sessionStorage.setItem('adm.hideInstallWarn', '1'); } catch (e) {} }
                break;
            case 'reset-ui':
                applyTheme('light'); applyLayout('sidebar'); applyAccent('indigo');
                root.setAttribute('data-sidebar', 'expanded'); set(LS.sidebar, 'expanded');
                break;
        }
    });

    /* Custom color picker */
    document.addEventListener('input', function (e) {
        if (e.target.matches('[data-accent-custom]')) { applyAccent(e.target.value); }
    });

    /* Echap ferme panneau / nav mobile */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { setPanel(false); setMobNav(false); }
    });

    /* Suivi du thème système si mode auto */
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (root.getAttribute('data-theme-mode') === 'auto') applyTheme('auto');
        });
    }

    /* ======================================================================
       COMPAT widgets Bootstrap restants (sans Bootstrap JS)
       ====================================================================== */
    document.addEventListener('click', function (e) {
        /* data-bs-toggle="collapse" */
        var col = e.target.closest('[data-bs-toggle="collapse"]');
        if (col) {
            e.preventDefault();
            var sel = col.getAttribute('data-bs-target') || col.getAttribute('href');
            var target = sel && document.querySelector(sel);
            if (target) target.classList.toggle('show');
            return;
        }
        /* data-bs-toggle="dropdown" */
        var dd = e.target.closest('[data-bs-toggle="dropdown"]');
        if (dd) {
            e.preventDefault();
            var menu = dd.nextElementSibling;
            document.querySelectorAll('.dropdown-menu.show').forEach(function (m) { if (m !== menu) m.classList.remove('show'); });
            if (menu) menu.classList.toggle('show');
            return;
        }
        if (!e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function (m) { m.classList.remove('show'); });
        }
        /* Modales : data-bs-toggle="modal" / data-bs-target */
        var mo = e.target.closest('[data-bs-toggle="modal"]');
        if (mo) {
            e.preventDefault();
            var ms = mo.getAttribute('data-bs-target');
            var modal = ms && document.querySelector(ms);
            if (modal) openModal(modal);
            return;
        }
        /* Fermeture modale */
        if (e.target.closest('[data-bs-dismiss="modal"]') || e.target.classList.contains('modal')) {
            var open = e.target.closest('.modal.show') || (e.target.classList.contains('modal') ? e.target : null);
            if (open) closeModal(open);
        }
        /* Offcanvas (nav mobile legacy) */
        var oc = e.target.closest('[data-bs-toggle="offcanvas"]');
        if (oc) { e.preventDefault(); setMobNav(true); return; }

        /* Back to top */
        if (e.target.closest('.back-to-top, [data-action="to-top"]')) {
            e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    function openModal(modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function closeModal(modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    window.AdminUI = {
        applyTheme: applyTheme, applyLayout: applyLayout, applyAccent: applyAccent,
        openModal: openModal, closeModal: closeModal, accents: ACCENTS
    };

    /* Init affichage panneau au chargement */
    document.addEventListener('DOMContentLoaded', function () {
        root.setAttribute('data-fullscreen', 'off');
        syncPanel();
        // Restaurer l'état de la bannière /install masquée pour la session
        try {
            if (sessionStorage.getItem('adm.hideInstallWarn') === '1') {
                var w = document.getElementById('adm-install-warn');
                if (w) w.style.display = 'none';
            }
        } catch (e) {}
    });
})();
