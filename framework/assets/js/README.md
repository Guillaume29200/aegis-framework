# TurboNav

**A zero-dependency, ~500-line vanilla JS script that gives any server-rendered app an SPA-like feel — no framework, no build step, no rewrite.**

TurboNav intercepts same-origin link clicks and `GET` form submissions, fetches the target page in the background, and swaps only a single content container instead of doing a full page reload. History, scroll position of the rest of the UI, and any state outside the swapped region are preserved. If the network fails, it falls back to a normal navigation automatically.

```html
<script>window.TURBONAV = { enabled: true };</script>
<script src="turbo-nav.js" defer></script>
```

That's it — no bundler, no npm install, no virtual DOM.

## Why

Server-rendered apps (classic MVC, PHP, Rails, Django, whatever) reload the whole page on every navigation: full parse, full re-render, a visible white flash. Rewriting the app as a React/Vue SPA fixes that, but costs a build pipeline, a router, and a rewrite of every view.

TurboNav takes the middle path popularized by libraries like Turbolinks/Turbo and htmx's `hx-boost`: keep your server-rendered HTML exactly as it is, and let a small script turn full-page navigations into AJAX content swaps. You get instant-feeling navigation and hover-prefetching for free, without touching your backend architecture.

## Features

- **Zero dependencies** — one script tag, no build step.
- **Link + form interception** — same-origin `<a>` clicks and `GET` `<form>` submissions (search, filters, pagination) are both turned into AJAX swaps.
- **Hover-prefetch** — starts fetching a link's target ~80ms after the cursor lands on it, so the click often resolves instantly.
- **In-memory cache with TTL** — up to 20 recently visited pages are cached; entries expire automatically (45s by default) so you don't need to think about staleness by hand.
- **Graceful degradation** — network error or timeout → automatic fallback to a real `window.location` navigation.
- **Lifecycle events** — `turbonav:before-swap` / `turbonav:after-swap` for reinitializing your own JS after a swap.
- **Small public API** — `TURBONAV.navigate()`, `TURBONAV.invalidateCache()`, `TURBONAV.prefetch()` to drive navigation from your own code.
- **Backend-aware headers** — every request carries `X-TurboNav: 1`, and prefetches additionally carry `X-TurboNav-Prefetch: 1`, so your server can tell a real navigation from a mere hover.

## Installation

Copy `turbo-nav.js` into your project and load it after setting the activation flag:

```html
<script>window.TURBONAV = { enabled: true };</script>
<script src="/assets/js/turbo-nav.js" defer></script>
```

Nothing is intercepted until `window.TURBONAV = { enabled: true }` is set *before* the script loads — this makes it trivial to gate the feature behind a settings toggle, a feature flag, or an A/B test, without shipping a different bundle.

## Requirement: a content container

TurboNav needs a single element to swap on every navigation. By default it looks for `#admin-content`:

```html
<body>
  <nav>...</nav>              <!-- untouched across navigations -->
  <div id="admin-content">
    <!-- this is what gets replaced -->
  </div>
</body>
```

Everything outside that container (navigation, header, sidebars) survives a swap untouched. If you rely on server-computed state in that surrounding markup — e.g. an "active" class on the current menu item — read the [How it works](#how-it-works) section below: TurboNav re-reads specific selectors from the fetched document and patches them in separately from the main content swap.

> **Note:** the container selector is currently a hardcoded internal constant (`#admin-content`), not a runtime option. If your project uses a different container id, rename your container to `admin-content`, or fork and adjust the `CONFIG.contentSelector` constant at the top of the file. Making this configurable via `window.TURBONAV` is a natural, low-risk contribution if you need it.

## How it works

1. Click on an eligible link (or submit an eligible `GET` form) → the native navigation is cancelled.
2. The target HTML is fetched (or served from cache if a fresh-enough entry exists).
3. The `#admin-content` region is replaced with the corresponding region from the fetched document; a small set of chrome selectors (nav/header classes, currently hardcoded — see note above) are resynchronized separately, since only the main content region is swapped.
4. Inline `<script>` tags in the new content are re-executed (in global scope — see [Writing compatible JS](#writing-compatible-js)); external `<script src>` tags already loaded on the page are skipped, unknown ones are loaded once.
5. The URL is updated via `history.pushState` (back/forward work, including serving from cache).
6. `turbonav:before-swap` then `turbonav:after-swap` are dispatched on `document`.

| Internal constant | Default | Role |
|---|---|---|
| `contentSelector` | `#admin-content` | Region swapped on every navigation |
| `cacheEnabled` | `true` | Enable/disable the in-memory cache |
| `cacheMaxSize` | `20` | Max cached pages (oldest evicted first) |
| `cacheTTL` | `45000` ms | Cache entry lifetime before it's treated as a miss |
| `timeout` | `8000` ms | Max time for a fetch before falling back |
| `prefetchDelay` | `80` ms | Hover delay before a prefetch fires |
| `reloadOnError` | `true` | Fall back to a full reload if the fetch fails |

These are internal `CONFIG` constants, not a public configuration API — adjust them by editing the top of `turbo-nav.js` if you fork it.

## Events

```js
document.addEventListener('turbonav:before-swap', (e) => {
  console.log('about to leave for', e.detail.url);
  clearInterval(myPollingTimer);
});

document.addEventListener('turbonav:after-swap', (e) => {
  console.log('now showing', e.detail.url);
  initMyWidgets();
});
```

Both events fire with `event.detail = { url }`. `after-swap` fires after content injection **and** after any newly-referenced external scripts have finished loading — so code relying on a library loaded by the swapped page can run safely inside the handler.

## Public API

Once initialized, TurboNav exposes `window.TURBONAV`:

```js
// Navigate programmatically, exactly like a click
TURBONAV.navigate('/dashboard');

// Navigate without pushing a new history entry
TURBONAV.navigate('/dashboard', false);

// Drop a stale cache entry (e.g. after a mutation)
TURBONAV.invalidateCache('/items');

// Drop the entire cache
TURBONAV.invalidateCache();

// Warm the cache manually
TURBONAV.prefetch('/likely-next-page');
```

```js
// Typical pattern: mutate, then invalidate + navigate back
fetch('/items/42', { method: 'DELETE' }).then(() => {
  TURBONAV.invalidateCache('/items');
  TURBONAV.navigate('/items');
});
```

`window.TURBONAV` only exposes this API **after** the script has initialized. If your code might run first, guard the call (`typeof TURBONAV !== 'undefined'`) or run it from an `after-swap` listener.

## Hover-prefetch

Hovering an eligible link for ~80ms triggers a background fetch (tagged with `X-TurboNav-Prefetch: 1`), deduplicated so a link hovered repeatedly is only fetched once. Moving the cursor away before the delay elapses cancels the pending prefetch. A successful prefetch populates the cache, so the eventual click (if it happens) resolves instantly.

## GET forms

`GET` form submissions (search boxes, filters, pagination) are intercepted the same way links are — turned into a fetch + swap instead of a full-page reload:

```html
<form action="/logs" method="get">
  <input type="text" name="q" placeholder="Search…">
  <button type="submit">Filter</button>
</form>
```

A form is intercepted when: its method is `GET` (or omitted, the HTML default), its `target` is `_self` (or omitted), its `action` is same-origin, and it doesn't carry the opt-out attribute below. `POST` forms are **never** intercepted — they mutate data, and always get a normal submission.

## Opting a link or form out

Add `data-no-turbonav` to force a full page reload on a specific element — useful for downloads, logout links, or anything outside the SPA-like region:

```html
<a href="/export.csv" data-no-turbonav>Export CSV</a>
<a href="/logout" data-no-turbonav>Log out</a>
<form action="/export" method="get" data-no-turbonav>…</form>
```

The alias `data-no-turbo` is also recognized, with identical behavior, on both links and forms.

Already skipped automatically, no attribute needed: cross-origin links, `#` anchors, `javascript:` links, `target="_blank"` (or any target other than `_self`), `download` links, and — for forms — any method other than `GET`.

## HTTP headers & backend integration

Every TurboNav-initiated request carries:

| Header | Present when | Value |
|---|---|---|
| `X-TurboNav` | Any TurboNav-driven request (click, form, prefetch, `TURBONAV.navigate()`) | `1` |
| `X-TurboNav-Prefetch` | Hover-prefetch requests only | `1` |
| `X-Requested-With` | Any TurboNav-driven request | `XMLHttpRequest` |

This lets a backend distinguish a real navigation from a mere hover — useful to avoid firing side effects (view counters, audit logs, notifications) on a prefetch that the user may never actually land on.

**PHP example:**

```php
function is_turbonav_prefetch(): bool {
    return ($_SERVER['HTTP_X_TURBONAV_PREFETCH'] ?? '') === '1';
}

// In a controller
if (!is_turbonav_prefetch()) {
    $articleService->incrementViews($id);
}
```

**Any other backend:** check for the same header (`X-Turbonav-Prefetch: 1` — header names are case-insensitive) before triggering a side effect. A prefetch still fully renders the page server-side (so caches, DB reads, etc. are exercised normally) — the header is meant to gate *side effects*, not the rendering work itself.

## Writing compatible JS

The classic pitfall: code that initializes on `DOMContentLoaded` never runs again after a swap, because the document is never reloaded.

```js
function initMyWidget() {
  const el = document.getElementById('my-widget');
  if (!el || el.dataset.ready) return; // idempotency guard
  el.dataset.ready = '1';
  // ... setup ...
}

document.addEventListener('DOMContentLoaded', initMyWidget);
document.addEventListener('turbonav:after-swap', initMyWidget);

document.addEventListener('turbonav:before-swap', () => {
  clearInterval(window.__myTimer);
});
```

If a widget "disappears" or "duplicates" after navigation, it's almost always a missing `after-swap` re-init, or a listener never cleaned up on `before-swap`.

### Inline scripts and `<script src>`

Inline `<script>` tags in swapped content are re-executed in **global scope** (not wrapped in an IIFE), deliberately — so a `function myFn(){}` declared inline stays callable from an `onclick="myFn()"` after navigation. Accepted trade-off: revisiting a page can log a harmless `"myFn has already been declared"` console warning — it has no functional effect.

External `<script src>` tags already present on the page's first load are not reloaded on a swap (tracked internally); an unseen external script is loaded once, before inline scripts run, then remembered for subsequent swaps.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Content doesn't swap over AJAX | TurboNav disabled, or `#admin-content` missing on the target page. |
| My script stops running after navigation | Init tied only to `DOMContentLoaded` → also listen for `turbonav:after-swap`. |
| Duplicate listeners / timers | No cleanup on `turbonav:before-swap`, or no idempotency guard. |
| A link/form should force a full reload | Add `data-no-turbonav` (`data-no-turbo` also accepted). |
| My search form doesn't go through AJAX | Check `method="get"`, no `target`, same-origin `action`, no `data-no-turbonav`. |
| "X has already been declared" in the console | Harmless — see [Inline scripts](#inline-scripts-and-script-src). |
| Unexpected full page reload | Automatic fallback: the fetch failed or exceeded the timeout (8s default). |
| Stale content shown after a change made elsewhere | The cache (45s TTL) served an unexpired-but-outdated entry — call `TURBONAV.invalidateCache(url)`. |

## Browser support

Any evergreen browser with `fetch`, `Promise`, and the History API (`pushState`/`popstate`) — no polyfills included, no transpilation required. No Internet Explorer support.

## Version history

**v2.1.0**
- `GET` form interception (search/filter forms are now AJAX-swapped like links).
- Cache TTL (`cacheTTL`, 45s default) — stale entries are treated as misses.
- `X-TurboNav-Prefetch` header + `is_turbonav_prefetch()` backend helper example.
- `data-no-turbo` alias accepted alongside `data-no-turbonav`, on both links and forms.
- Fix: leaving a link before the prefetch delay elapsed now reliably cancels the pending fetch, including for links combining an icon and text.

## Contributing

Issues and PRs welcome. Given the "no dependencies, no build step" philosophy, please keep contributions to plain ES5/ES6 in the single `turbo-nav.js` file rather than introducing a bundler or a package.

## License

MIT — see [LICENSE](LICENSE).
