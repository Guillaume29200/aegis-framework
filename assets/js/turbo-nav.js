/**
 * GSH TurboNav v2.0
 * Navigation AJAX ultra-légère — remplace les rechargements de page complets
 * par des swaps de contenu silencieux. Effet SPA sans framework.
 *
 * Nouveauté v2 : préchargement intelligent au survol des liens.
 * Le fetch démarre dès que la souris passe sur un lien éligible (~150ms
 * avant le clic), rendant la navigation quasi-instantanée même sans cache.
 *
 * Usage : inclure ce fichier dans le layout global APRÈS avoir défini :
 *   window.TURBONAV = { enabled: true/false }  (injecté depuis PHP/config)
 *
 * HTML requis : un élément avec id="admin-content" dans toutes les pages
 */

(function () {
    'use strict';

    // ─── Config ────────────────────────────────────────────────────────────────

    const CONFIG = {
        contentSelector: '#admin-content',   // wrapper du contenu principal
        cacheEnabled:    true,              // met en cache les pages déjà visitées
        cacheMaxSize:    20,                // nb de pages max en cache
        timeout:         8000,              // timeout fetch en ms
        reloadOnError:   true,              // fallback : rechargement complet si fetch échoue
        prefetchDelay:   80,                // ms à attendre avant de précharger au survol
                                            // évite les prefetch parasites sur les survols rapides
    };

    // ─── État interne ──────────────────────────────────────────────────────────

    const cache   = new Map();
    let   isNavigating  = false;
    let   prefetchTimer = null;   // timer du délai avant prefetch
    let   prefetching   = new Set(); // URLs en cours de prefetch (évite les doublons)

    // ─── Vérification activation ───────────────────────────────────────────────

    function isEnabled() {
        return window.TURBONAV && window.TURBONAV.enabled === true;
    }

    // ─── Extraction du contenu ─────────────────────────────────────────────────

    function extractContent(html) {
        const parser  = new DOMParser();
        const doc     = parser.parseFromString(html, 'text/html');
        const content = doc.querySelector(CONFIG.contentSelector);
        const title   = doc.title || '';

        // Collecte les scripts inline qui se retrouvent HORS de #admin-content.
        // Cela arrive quand la page contient des balises fermantes en trop (HTML
        // mal formé) : DOMParser les sort du conteneur de swap.
        // On les stocke pour les ré-exécuter quand même après le swap.
        const extraScripts = [];
        if (content) {
            doc.body.querySelectorAll('script:not([src])').forEach(function(s) {
                if (!content.contains(s)) {
                    extraScripts.push(s.textContent);
                }
            });
            if (extraScripts.length > 0) {
                console.warn('[TurboNav] ' + extraScripts.length + ' script(s) trouvé(s) hors de '
                    + CONFIG.contentSelector + ' — probablement du HTML mal formé. '
                    + 'Ils seront réinjectés après le swap.');
            }
        }

        return { content, title, doc, extraScripts };
    }

    // ─── Suivi des scripts externes déjà chargés ──────────────────────────────
    // On mémorise les URLs des scripts src déjà présents dans le DOM au
    // chargement initial. Ceux-là ne seront jamais rechargés.
    // Les scripts src trouvés dans du contenu swappé et ABSENTS de cette liste
    // seront chargés une fois puis mémorisés.

    const loadedScripts = new Set(
        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
    );

    // ─── Ré-exécution des scripts ─────────────────────────────────────────────

    function reExecuteScripts(container, extraScripts) {
        const scripts = Array.from(container.querySelectorAll('script'));

        // Sépare les scripts src (externes) des scripts inline
        const srcScripts    = scripts.filter(s => s.src);
        const inlineScripts = scripts.filter(s => !s.src);

        // ── 1. Scripts externes (src=) ───────────────────────────────────────
        // - Déjà connus (loadedScripts) → on les supprime sans les recharger
        //   (déjà en mémoire globale, ex: jQuery, Chart.js chargés dans footer)
        // - Inconnus → ils sont propres à cette vue (ex: Leaflet dans la page map)
        //   On les charge dynamiquement une fois, en séquence, AVANT les inline.

        const toLoad = srcScripts.filter(s => !loadedScripts.has(s.src));
        srcScripts.filter(s => loadedScripts.has(s.src)).forEach(s => s.remove());

        // Charge les scripts inconnus en séquence puis exécute les inline
        loadScriptsSequentially(toLoad).then(() => {
            executeInlineScripts(container, inlineScripts, extraScripts || []);
        });
    }

    function loadScriptsSequentially(scripts) {
        return scripts.reduce((promise, oldScript) => {
            return promise.then(() => new Promise((resolve) => {
                // Déjà chargé entre-temps (double appel) → on passe
                if (loadedScripts.has(oldScript.src)) {
                    oldScript.remove();
                    resolve();
                    return;
                }

                const newScript  = document.createElement('script');
                newScript.src    = oldScript.src;
                newScript.async  = false; // séquence garantie

                newScript.onload = () => {
                    loadedScripts.add(oldScript.src);
                    oldScript.remove();
                    resolve();
                };
                newScript.onerror = () => {
                    console.warn('[TurboNav] Échec chargement script :', oldScript.src);
                    oldScript.remove();
                    resolve(); // on continue quand même
                };

                document.head.appendChild(newScript);
            }));
        }, Promise.resolve());
    }

    function executeInlineScripts(container, scripts, extraScripts) {
        scripts.forEach(oldScript => {
            // Enveloppé dans un IIFE pour isoler le scope et éviter les
            // erreurs "X has already been declared" lors des navigations
            // successives (const/let déclarés au niveau global).
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = ';(function(){\n' + oldScript.textContent + '\n})();';
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Ré-exécuter les scripts orphelins détectés hors de #admin-content
        // lors de l'extraction (HTML mal formé → DOMParser les sort du conteneur).
        if (extraScripts && extraScripts.length) {
            extraScripts.forEach(function(code) {
                const s = document.createElement('script');
                s.textContent = ';(function(){\n' + code + '\n})();';
                container.appendChild(s);
            });
        }

        // Scroll en haut et event after-swap déclenchés ici car on est async
        // (ils étaient dans swapContent mais les inline doivent s'exécuter après)
        window.scrollTo(0, 0);
        document.dispatchEvent(new CustomEvent('turbonav:after-swap', {
            detail: { url: window.location.href }
        }));
    }

    // ─── Swap du contenu ──────────────────────────────────────────────────────

    function swapContent(newContentNode, newTitle, url, extraScripts) {
        const current = document.querySelector(CONFIG.contentSelector);

        if (!current) {
            console.warn('[TurboNav] Élément ' + CONFIG.contentSelector + ' introuvable dans la page courante.');
            return false;
        }

        if (!newContentNode) {
            console.warn('[TurboNav] Élément ' + CONFIG.contentSelector + ' introuvable dans la page fetchée : ' + url);
            return false;
        }

        // Émet un event avant le swap (pour permettre cleanup externe si besoin)
        document.dispatchEvent(new CustomEvent('turbonav:before-swap', { detail: { url } }));

        // Swap
        current.innerHTML = newContentNode.innerHTML;

        // Met à jour le titre
        if (newTitle) document.title = newTitle;

        // Ré-exécute les scripts de la zone swappée + scripts orphelins éventuels
        // (scroll + turbonav:after-swap émis à la fin de reExecuteScripts, après chargement async)
        reExecuteScripts(current, extraScripts || []);

        return true;
    }

    // ─── Resynchronisation du « chrome » (sidebar / topnav / titre) ─────────────
    // TurboNav ne remplace que #admin-content ; sans cela, l'état actif du menu
    // resterait celui de la page précédente. On réinjecte le menu rendu par le
    // serveur (qui calcule déjà le bon item actif via AdminMenuService::isActive).
    function syncChrome(doc) {
        if (!doc) return;
        ['.adm-nav', '.adm-topnav'].forEach(function (sel) {
            var cur = document.querySelector(sel);
            var neu = doc.querySelector(sel);
            if (cur && neu) cur.innerHTML = neu.innerHTML;
        });
        var curTitle = document.querySelector('.adm-header-title');
        var newTitle = doc.querySelector('.adm-header-title');
        if (curTitle && newTitle) curTitle.textContent = newTitle.textContent;
    }

    // ─── Préchargement au survol (V2) ─────────────────────────────────────────
    // Démarre un fetch silencieux dès que la souris survole un lien éligible.
    // Le résultat est mis en cache — au clic, la page est déjà disponible.

    async function prefetch(url) {
        // Déjà en cache ou déjà en cours de prefetch → inutile
        if (cache.has(url) || prefetching.has(url)) return;

        prefetching.add(url);

        try {
            const controller = new AbortController();
            const timeoutId  = setTimeout(() => controller.abort(), CONFIG.timeout);

            const response = await fetch(url, {
                signal:  controller.signal,
                headers: {
                    'X-TurboNav':        '1',
                    'X-TurboNav-Prefetch': '1',   // permet de distinguer prefetch/navigation côté PHP
                    'X-Requested-With':  'XMLHttpRequest',
                },
            });

            clearTimeout(timeoutId);

            if (!response.ok) return;

            const html = await response.text();

            // Stocke en cache pour la navigation à venir
            if (CONFIG.cacheEnabled) {
                if (cache.size >= CONFIG.cacheMaxSize) {
                    cache.delete(cache.keys().next().value);
                }
                cache.set(url, html);
            }

        } catch (err) {
            // Erreur silencieuse — le prefetch est best-effort, pas critique
        } finally {
            prefetching.delete(url);
        }
    }

    function handleMouseEnter(e) {
        const anchor = e.target.closest('a');
        if (!shouldIntercept(anchor)) return;

        const url = anchor.href;
        if (url === window.location.href) return;

        // Délai court pour ignorer les survols de passage (menu, etc.)
        clearTimeout(prefetchTimer);
        prefetchTimer = setTimeout(() => prefetch(url), CONFIG.prefetchDelay);
    }

    function handleMouseLeave(e) {
        // Annule le prefetch si la souris quitte avant le délai
        clearTimeout(prefetchTimer);
    }

    // ─── Navigation principale ────────────────────────────────────────────────

    async function navigate(url, pushState = true) {
        if (isNavigating) return;
        isNavigating = true;

        try {
            let html;

            // Vérifie le cache d'abord
            if (CONFIG.cacheEnabled && cache.has(url)) {
                html = cache.get(url);
            } else {
                const controller = new AbortController();
                const timeoutId  = setTimeout(() => controller.abort(), CONFIG.timeout);

                const response = await fetch(url, {
                    signal:  controller.signal,
                    headers: {
                        'X-TurboNav': '1',         // header custom pour identifier les requêtes TurboNav côté PHP si besoin
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                html = await response.text();

                // Mise en cache
                if (CONFIG.cacheEnabled) {
                    if (cache.size >= CONFIG.cacheMaxSize) {
                        // Supprime la plus ancienne entrée
                        cache.delete(cache.keys().next().value);
                    }
                    cache.set(url, html);
                }
            }

            const { content, title, extraScripts, doc } = extractContent(html);
            const swapped = swapContent(content, title, url, extraScripts);

            if (!swapped) {
                throw new Error('Swap échoué');
            }

            // Resynchronise l'état actif du menu (sidebar + topnav) depuis le serveur.
            syncChrome(doc);

            // Mise à jour de l'URL dans le navigateur
            if (pushState) {
                history.pushState({ turboNav: true, url }, title, url);
            }

        } catch (err) {
            console.error('[TurboNav] Erreur de navigation :', err);

            if (CONFIG.reloadOnError) {
                // Fallback : rechargement classique
                window.location.href = url;
            }
        } finally {
            isNavigating = false;
        }
    }

    // ─── Interception des clics ───────────────────────────────────────────────

    function shouldIntercept(anchor) {
        // Ignore si pas un lien interne
        if (!anchor || anchor.tagName !== 'A') return false;

        // Laisse passer les liens avec target (_blank, etc.)
        if (anchor.target && anchor.target !== '_self') return false;

        // Laisse passer les liens avec data-no-turbonav
        if (anchor.hasAttribute('data-no-turbonav')) return false;

        // Laisse passer les téléchargements
        if (anchor.hasAttribute('download')) return false;

        // Laisse passer les ancres (#)
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;

        // Laisse passer les protocoles externes (mailto:, tel:...)
        if (anchor.protocol && anchor.protocol !== window.location.protocol) return false;

        // Laisse passer les domaines externes
        if (anchor.hostname && anchor.hostname !== window.location.hostname) return false;

        // Laisse passer les modificateurs clavier (ctrl+clic, cmd+clic...)
        return true;
    }

    function handleClick(e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

        const anchor = e.target.closest('a');
        if (!shouldIntercept(anchor)) return;

        const url = anchor.href;

        // Pas de re-navigation si on est déjà sur la page
        if (url === window.location.href) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        navigate(url, true);
    }

    // ─── Boutons Précédent / Suivant ──────────────────────────────────────────

    function handlePopState(e) {
        if (e.state && e.state.turboNav) {
            navigate(e.state.url || window.location.href, false);
        } else {
            // Fallback pour l'état initial
            navigate(window.location.href, false);
        }
    }

    // ─── Invalidation du cache ────────────────────────────────────────────────
    // Expose une fonction globale pour vider le cache depuis PHP/JS si besoin
    // ex: après une action qui modifie des données

    function invalidateCache(url) {
        if (url) {
            cache.delete(url);
        } else {
            cache.clear();
        }
    }

    // ─── Initialisation ───────────────────────────────────────────────────────

    function init() {
        if (!isEnabled()) {
            console.info('[TurboNav] Désactivé (TURBONAV.enabled = false)');
            return;
        }

        // Enregistre l'état initial dans l'historique
        history.replaceState(
            { turboNav: true, url: window.location.href },
            document.title,
            window.location.href
        );

        // Écoute les clics
        document.addEventListener('click', handleClick);

        // Écoute les survols pour le préchargement (V2)
        document.addEventListener('mouseover',  handleMouseEnter);
        document.addEventListener('mouseleave', handleMouseLeave, true);

        // Écoute les boutons back/forward
        window.addEventListener('popstate', handlePopState);

        // API publique
        window.TURBONAV.navigate        = navigate;
        window.TURBONAV.invalidateCache = invalidateCache;
        window.TURBONAV.prefetch        = prefetch;

        console.info('[TurboNav] ✅ v2.0 actif — préchargement au survol activé');
    }

    // Lance au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();