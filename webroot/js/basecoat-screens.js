/*
 * BasecoatTheme screen behaviours (webroot/js/basecoat-screens.js).
 *
 * Load it with the CSP nonce, e.g.:  $this->Html->script('BasecoatTheme.basecoat-screens', ['block' => true, 'defer' => true]);
 * Vanilla, no dependencies, event delegation, no inline styles (visibility through the `hidden`
 * attribute), no innerHTML (text through textContent, markup cloned from <template>). Every behaviour
 * is progressive enhancement: links/forms keep working without JS; with JS their default is prevented.
 * Selectors in attribute values ("#list") are CSS selectors resolved with document.querySelector.
 *
 * FILTER   input[data-filter-input="#root"] (search text; Enter filters at once, never submits the
 *          form) and/or facet controls [data-filter-facet="#root"] (button/link with data-filter-value,
 *          or a <select>). data-filter-key="type" on a facet control: one facet per key (default
 *          "facet"), items must match every key (AND), e.g. a Status and a Type select on one list.
 *          Items: [data-filter-item] inside root; text = data-filter-text or textContent (case and accent
 *          insensitive, every word must match); facet tokens = data-state + data-filter-tags.
 *          Facet value: "" = all, "done" = has token, "!done" = lacks it, "[data-starred]" / ":not(…)" =
 *          CSS selector. Non-matching items get `hidden`; [data-filter-group] (hidden when empty),
 *          [data-filter-empty] (shown when nothing matches). Facet buttons of the same key get
 *          aria-pressed / aria-current (see ACTIVE). Event basecoat:filter on root {query, facet, facets,
 *          visible, total}.
 * VIEW     [data-view-switch="grid"][data-view-target="#root"]: root gets data-view="grid";
 *          [data-view-panel="list"] inside root are hidden unless they match; [data-view-classes='{"grid":
 *          "grid grid-cols-3","list":"flex flex-col"}'] (root or inside) swap classes. Controls follow
 *          ACTIVE; links also replace the URL. Event basecoat:view on root {view}.
 * ACTIVE   facet and view controls: aria-current="true" (links) or aria-pressed (buttons), plus
 *          data-active-class / data-inactive-class (classes toggled) and data-active-variant /
 *          data-inactive-variant (the button's data-variant; "" removes it). role="tab" controls are
 *          left to Basecoat tabs.
 * SELECT   input[data-select-all="#scope"] + input[data-select-item] (inside scope, value = id) +
 *          [data-bulk-bar="#scope"] (hidden when nothing is selected) with [data-bulk-count] and
 *          [data-bulk-clear]. Select-all only affects visible items; indeterminate when some are checked.
 *          The row (closest tr or [data-select-row]) gets data-selected. Event basecoat:select on scope
 *          {selected: [values], count}.
 * IDS      the id sent in events/POSTs is data-id, else data-dnd-id, else the element id (same lookup as
 *          basecoat-dnd.js, so one data-id serves both libraries).
 * TOGGLE   [data-toggle="name"] checkbox or button inside [data-toggle-item] (data-id), or anywhere with
 *          data-toggle-target="#row" (e.g. Star in a reading pane acting on the list row):
 *          on -> item[data-state]="name" (or data-toggle-attr="data-starred": attribute set to "true"),
 *          off -> data-toggle-off value or attribute removed. Checkbox: on = checked. Button with
 *          aria-pressed: flips; other buttons set "on" (Mark as read, Accept). On the control:
 *          data-toggle-class (classes toggled on the item), data-toggle-unset="data-unread" (attributes
 *          removed while on), data-toggle-also="read answered" (switching on also switches those on,
 *          through their own control on the item, else as the attribute data-<name>="true"),
 *          data-announce-on / data-announce-off="{label} starred." (announced), data-toggle-focus="sel"
 *          (where focus goes when the control hides itself; default: the first control the toggle
 *          revealed, else the item). Inside the item, [data-toggle-for="name"] elements get data-text-on /
 *          data-text-off as text and `hidden` when data-toggle-hidden="on|off" matches. Every control of
 *          the toggle (inside the item or targeting it) gets checked / aria-pressed.
 *          [data-toggle-all="name"][data-toggle-scope="#list"] (+ data-announce-on): turns every item on.
 *          Event basecoat:toggle (cancelable) on the item {item, id, name, on} (chained toggles fire their
 *          own). Focus: when the toggle hides its row (an active filter such as Status = Unread), focus
 *          moves to the next visible row, else the previous one, else the filter root.
 * REMOVE   [data-remove-item="archive"] button: removes the element of its data-remove-target="#row", else
 *          its closest [data-toggle-item] / [data-filter-item] / [data-remove-target] ancestor. Focus goes
 *          to the next visible row. When the row is open in a master-detail pane, the pane gets the
 *          master's data-master-empty template. Event basecoat:remove (cancelable) {item, id, action}.
 * COUNT    [data-count-for="selector"]: text = number of matches in the document (data-count-visible:
 *          only those not inside [hidden]) + data-count-offset (e.g. unread rows on other pages);
 *          data-count-hide-zero[=".badge"] hides the element (or that ancestor) at 0. Refreshed after
 *          every basecoat:* event of both libraries.
 * MASTER   [data-master="#detail"] with [data-master-item="template-id"] (button or link): the detail
 *          pane gets a clone of <template id="template-id">; the item gets aria-current="true" (others
 *          lose it); its [data-master-clear] descendants (unread badges) are removed; "opened" is
 *          announced (data-label names the item). data-master-focus on the master moves focus to the
 *          pane; data-master-empty="template-id" is shown when the open row is removed. Event
 *          basecoat:open on the item {item, id, detail}.
 * COMPOSER form[data-composer="#log"] data-composer-template="tpl" [data-composer-reply="tpl2"]:
 *          on submit the text of [data-composer-input] (or the textarea / text input) goes into the
 *          template's [data-composer-text] (textContent; [data-composer-time] gets the time), the clone
 *          is appended to the log, the input cleared and the log scrolled; the reply template (canned
 *          answer) follows after 600ms (0 with reduced motion). Enter sends, Shift+Enter is a new line.
 *          [data-composer-fill="text"] (data-composer-target="#form"; data-composer-send to send at
 *          once) fills the composer. Event basecoat:send (cancelable) on the form {text, log, message}.
 * STEPPER  [data-stepper] with [data-step] panels (data-step-title), [data-step-next], [data-step-prev],
 *          [data-step-go="n"] (0-based), [data-step-finish], [data-step-indicator] (data-state complete /
 *          current / upcoming, aria-current="step"), [data-step-current], [data-step-total],
 *          [data-step-progress] (<progress> value, or data-progress-value for the theme's bars).
 *          Next validates the panel's fields (checkValidity) first; values are kept. A field with
 *          aria-errormessage="id" gets its validation message in that element (shown, announced, linked
 *          with aria-describedby, aria-invalid) instead of the browser bubble; cleared once valid.
 *          [data-bind="field-name other-field"] (data-bind-empty="Not set") shows the current values of
 *          those fields joined with ", " (select: option text, checkbox / radio: label of the checked
 *          ones, or data-bind-map='{"value": "Text"}'), e.g. on a Review step.
 *          Nav controls outside the panels are hidden when useless (prev on the first step, next on the
 *          last, finish until the last). Event basecoat:step (cancelable) {from, to, total}.
 * DROPZONE [data-dropzone] with input[type=file][data-dropzone-input] (the picker, also for keyboard
 *          users), [data-dropzone-list] (hidden until files are chosen) holding <template
 *          data-dropzone-item> ([data-dropzone-name], [data-dropzone-size], [data-dropzone-remove] with
 *          data-dropzone-remove-label="Remove {name}"), [data-dropzone-actions] ([data-dropzone-upload],
 *          [data-dropzone-clear]). The zone gets data-over while files are dragged over it. Files are only
 *          listed; Upload fires basecoat:upload (cancelable) on the zone {files: File[]}: send them
 *          yourself (FormData + CSRF token). Messages files-added, file-removed, files-cleared.
 * ENDPOINT data-endpoint="/url" on the item / form / list or an ancestor: toggles, removals and sends are
 *          POSTed as JSON ({action: 'toggle', id, name, on[, also: [chained names]]} | {action: 'toggle-all',
 *          name, on: true, ids} | {action, id} (removals) | {action: 'send', id (form id), text})
 *          with X-CSRF-Token (data-csrf-token on an ancestor, <meta name="csrfToken"> or
 *          input[name=_csrfToken]). The URL is resolved against document.baseURI and must be same-origin
 *          http(s), otherwise it is refused (console.error). On failure or refusal the change is reverted
 *          (sends: the bubble gets data-state="failed"), announced, and basecoat:toggle-error /
 *          basecoat:remove-error / basecoat:send-error fire with the event detail + {status, reason:
 *          'response'|'origin'}. A toggle and its data-toggle-also chain are one POST and are reverted
 *          together; data-toggle-all is one POST for every item (all reverted on failure; the error event
 *          fires on the scope with {ids, name, on}). A failed removal puts the row back, the reading pane
 *          and the focus too. While a toggle POST is pending the item has data-busy + aria-busy and
 *          ignores further toggles and removals.
 * ANNOUNCER shared [data-basecoat-announcer] aria-live region (also used by basecoat-dnd.js). Messages:
 *          data-screens-i18n-<key> on the element or an ancestor, or
 *          <script type="application/json" data-screens-i18n>{…}</script>; keys in MESSAGES below.
 * API      window.BasecoatScreens.init(root) (auto: DOMContentLoaded, htmx:load, htmx:afterSwap;
 *          idempotent), .refreshCounts(), .applyFilters(root), .announce(text).
 */
(() => {
  // ---------------------------------------------------------------- pure helpers (unit tested)
  const normalize = (text) => String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();

  /** Every word of the query appears in the text (case and accent insensitive). */
  const matchesQuery = (text, query) => {
    const haystack = normalize(text);
    return normalize(query).split(' ').filter(Boolean).every((word) => haystack.includes(word));
  };

  /** Facet value against tokens: '' or '*' all, 'x' has token, '!x' lacks it. */
  const matchesFacet = (tokens, value) => {
    const facet = String(value || '').trim();
    if (facet === '' || facet === '*') return true;
    if (facet.startsWith('!')) return !tokens.includes(facet.slice(1));
    return tokens.includes(facet);
  };

  const tokensOf = (...values) => values.join(' ').split(/\s+/).filter(Boolean);

  /** Every facet (key => value) matches the tokens (AND across keys). */
  const matchesFacets = (tokens, facets) => Object.keys(facets || {}).every((key) => matchesFacet(tokens, facets[key]));

  /** Count shown by [data-count-for]: matches + data-count-offset, never below 0. */
  const countValue = (matches, offset) => Math.max(0, matches + (parseInt(offset, 10) || 0));

  /** Human file size: 0 B, 1.5 KB, 15 MB. */
  const formatSize = (bytes) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = Math.max(0, Number(bytes) || 0);
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit += 1; }
    return `${unit === 0 || value >= 10 ? Math.round(value) : Math.round(value * 10) / 10} ${units[unit]}`;
  };

  /** Bound field values joined with ", " (blank ones dropped), or `empty`. */
  const joinValues = (values, empty) => {
    const kept = values.map((v) => String(v === undefined || v === null ? '' : v).trim()).filter(Boolean);
    return kept.length ? kept.join(', ') : String(empty || '');
  };

  /** 'none' | 'some' | 'all' for a select-all checkbox. */
  const selectAllState = (checked, total) => (total === 0 || checked === 0 ? 'none' : checked >= total ? 'all' : 'some');

  /** States of the steps for the current index. */
  const stepStates = (current, total) => Array.from({ length: total }, (_, i) => (i < current ? 'complete' : i === current ? 'current' : 'upcoming'));

  const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
  const format = (template, vars) => String(template).replace(/\{([\w-]+)\}/g, (m, key) => (
    vars && vars[key] !== undefined && vars[key] !== null ? String(vars[key]) : m
  ));

  /** Class list after replacing the classes of `map` with those of `key`. */
  const swapClasses = (current, map, key) => {
    const split = (v) => String(v || '').split(/\s+/).filter(Boolean);
    const owned = Object.keys(map || {}).reduce((all, k) => all.concat(split(map[k])), []);
    const next = current.filter((c) => !owned.includes(c));
    split(map ? map[key] : '').forEach((c) => { if (!next.includes(c)) next.push(c); });
    return next;
  };

  /** Absolute URL of a data-endpoint when it is same-origin http(s), else null. */
  const resolveEndpoint = (url, base, origin) => {
    if (!url) return null;
    try {
      const resolved = new URL(url, base);
      return resolved.origin === origin && /^https?:$/.test(resolved.protocol) ? resolved.href : null;
    } catch (e) {
      return null;
    }
  };

  /**
   * POSTs `payload` as JSON to `endpoint` ({raw: data-endpoint value, token: CSRF token}) through
   * env.fetch; env {base, origin, fetch, error}. Returns null without an endpoint, else a promise of
   * {ok, status, reason: 'response'|'origin'}; a cross-origin / non-http(s) endpoint is refused (env.error)
   * without any request, so the token never leaves the site.
   */
  const endpointRequest = (endpoint, payload, env) => {
    if (!endpoint) return null;
    const url = resolveEndpoint(endpoint.raw, env.base, env.origin);
    if (!url) {
      env.error(`data-endpoint must be a same-origin http(s) URL; change reverted: ${endpoint.raw}`);
      return Promise.resolve({ ok: false, status: 0, reason: 'origin' });
    }
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (endpoint.token) headers['X-CSRF-Token'] = endpoint.token;
    return env.fetch(url, { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload) })
      .then((response) => ({ ok: response.ok, status: response.status, reason: 'response' }), () => ({ ok: false, status: 0, reason: 'response' }));
  };

  /** Settles an optimistic change: hooks.busy(true / false) around `pending`, hooks.revert(result) on failure. */
  const settle = (pending, hooks) => {
    if (!pending) return null;
    if (hooks.busy) hooks.busy(true);
    return pending.then((result) => {
      if (hooks.busy) hooks.busy(false);
      if (!result.ok && hooks.revert) hooks.revert(result);
      return result;
    });
  };

  /**
   * A toggle and its chained toggles (data-toggle-also) as one transaction, DOM-free. `main` and each of
   * `chained` are adapters {name, on, isOn(), read() -> snapshot, write(on), restore(snapshot), confirm()
   * -> false when basecoat:toggle was cancelled}. A busy item ignores the change (the control is resynced).
   * The chained toggles (only when switching on, skipping those already on) are applied directly, so they
   * never hit the busy flag, and go in the same request: send(alsoNames) -> null | promise. On failure every
   * applied toggle is restored (last first) and fail(result) runs. Returns {status: 'busy'|'unchanged'|
   * 'cancelled'|'applied', also, pending, undo}.
   */
  const toggleTransaction = ({ busy, main, chained, after, send, setBusy, fail }) => {
    if (busy()) {
      main.write(main.isOn());
      return { status: 'busy', also: [], pending: null, undo: () => {} };
    }
    if (main.isOn() === main.on) {
      main.write(main.on);
      return { status: 'unchanged', also: [], pending: null, undo: () => {} };
    }
    const applied = [];
    const undo = () => applied.slice().reverse().forEach((entry) => entry.change.restore(entry.snapshot));
    const apply = (change) => {
      const snapshot = change.read();
      change.write(change.on);
      if (!change.confirm()) {
        change.restore(snapshot);
        return false;
      }
      applied.push({ change, snapshot });
      return true;
    };
    if (!apply(main)) return { status: 'cancelled', also: [], pending: null, undo: () => {} };
    if (main.on) (chained || []).forEach((change) => { if (change.isOn() !== change.on) apply(change); });
    const also = applied.slice(1).map((entry) => entry.change.name);
    if (after) after();
    const pending = settle(send(also), {
      busy: setBusy,
      revert: (result) => {
        undo();
        if (fail) fail(result);
      },
    });
    return { status: 'applied', also, pending, undo };
  };

  /** The next visible ({hidden: false}) sibling of `el`, else the previous one, else null. */
  const visibleNeighbour = (el) => {
    const walk = (start, step) => {
      let current = start;
      while (current && current.hidden) current = current[step];
      return current || null;
    };
    return walk(el.nextElementSibling, 'nextElementSibling') || walk(el.previousElementSibling, 'previousElementSibling');
  };

  /** JSON.parse with a cache of at most `limit` texts (cleared when full); invalid JSON -> null + onError. */
  const createJsonCache = (limit, onError) => {
    const cache = new Map();
    return {
      parse: (text) => {
        if (!text) return null;
        if (cache.has(text)) return cache.get(text);
        let value = null;
        try { value = JSON.parse(text); } catch (e) { if (onError) onError(e); }
        if (cache.size >= limit) cache.clear();
        cache.set(text, value);
        return value;
      },
      clear: () => cache.clear(),
      size: () => cache.size,
    };
  };

  const pure = { normalize, matchesQuery, matchesFacet, matchesFacets, tokensOf, countValue, formatSize, joinValues, selectAllState, stepStates, clamp, format, swapClasses, resolveEndpoint, endpointRequest, settle, toggleTransaction, visibleNeighbour, createJsonCache };
  if (typeof module !== 'undefined' && module.exports) module.exports = pure;
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  if (window.BasecoatScreens) return;

  // ---------------------------------------------------------------- messages / announcer
  const MESSAGES = {
    results: '{visible} of {total} shown.',
    step: 'Step {step} of {total}: {title}',
    removed: '{label} removed.',
    failed: 'Could not save the change.',
    'send-failed': 'The message could not be sent.',
    selected: '{count} selected.',
    opened: '{label} opened.',
    'files-added': '{count} added.',
    'file-removed': '{name} removed.',
    'files-cleared': 'List cleared.',
  };

  // Parsed data-* JSON, cached (bounded; cleared on htmx swaps)
  const jsonCache = createJsonCache(200, (e) => console.error('[BasecoatScreens] Invalid JSON', e));
  const parseJson = jsonCache.parse;

  const t = (context, key, vars) => {
    for (let el = context; el && el.getAttribute; el = el.parentElement) {
      const value = el.getAttribute(`data-screens-i18n-${key}`);
      if (value !== null) return format(value, vars || {});
    }
    const script = document.querySelector('script[type="application/json"][data-screens-i18n]');
    const block = script ? parseJson(script.textContent) : null;
    if (block && typeof block[key] === 'string') return format(block[key], vars || {});
    return format(MESSAGES[key] || key, vars || {});
  };

  let announceTimer = null;
  const liveRegion = () => {
    let region = document.querySelector('[data-basecoat-announcer]');
    if (!region && document.body) {
      region = document.createElement('div');
      region.setAttribute('data-basecoat-announcer', '');
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'true');
      region.className = 'sr-only';
      document.body.appendChild(region);
    }
    return region;
  };
  const announce = (text) => {
    if (window.BasecoatDnd && typeof window.BasecoatDnd.announce === 'function') {
      window.BasecoatDnd.announce(text);
      return;
    }
    const region = liveRegion();
    if (!region) return;
    region.textContent = '';
    clearTimeout(announceTimer);
    announceTimer = setTimeout(() => { region.textContent = text; }, 50);
  };

  // ---------------------------------------------------------------- helpers
  const $ = (selector) => {
    if (!selector) return null;
    try { return document.querySelector(selector); } catch (e) { return null; }
  };
  const reducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // Same id lookup as basecoat-dnd.js, so one attribute serves both libraries.
  const idOf = (el) => (el ? el.getAttribute('data-id') || el.getAttribute('data-dnd-id') || el.id || null : null);
  const labelOf = (el) => el.getAttribute('data-label') || el.getAttribute('aria-label') || (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 60);
  const isVisible = (el) => !el.closest('[hidden]');
  const dispatch = (el, name, detail, cancelable = true) => el.dispatchEvent(new CustomEvent(name, { bubbles: true, cancelable, detail }));
  const replaceUrl = (control) => {
    if (control.matches('a[href]') && window.history && typeof window.history.replaceState === 'function') {
      try { window.history.replaceState(window.history.state, '', control.href); } catch (e) { /* other origin */ }
    }
  };
  const splitList = (value) => String(value || '').split(/\s+/).filter(Boolean);
  /**
   * Marks a facet / view control active or not: aria-current (links) or aria-pressed (buttons), plus
   * data-active-class / data-inactive-class and data-active-variant / data-inactive-variant (data-variant
   * of Basecoat buttons; "" removes it). role="tab" controls are left to Basecoat tabs.
   */
  const setActive = (control, active) => {
    if (control.getAttribute('role') === 'tab') return;
    if (control.matches('a[href]')) {
      if (active) control.setAttribute('aria-current', 'true'); else control.removeAttribute('aria-current');
    } else {
      control.setAttribute('aria-pressed', String(active));
    }
    splitList(control.getAttribute('data-active-class')).forEach((c) => control.classList.toggle(c, active));
    splitList(control.getAttribute('data-inactive-class')).forEach((c) => control.classList.toggle(c, !active));
    const variant = control.getAttribute(active ? 'data-active-variant' : 'data-inactive-variant');
    if (variant === '') control.removeAttribute('data-variant');
    else if (variant !== null) control.setAttribute('data-variant', variant);
  };
  const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
  const focusableIn = (el) => (el.matches(FOCUSABLE) ? el : el.querySelector(FOCUSABLE));
  const focusElement = (el) => {
    if (!el) return;
    if (!el.matches(FOCUSABLE) && !el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1');
    el.focus();
  };
  const within = (root, selector) => {
    const found = Array.from(root.querySelectorAll(selector));
    if (root instanceof Element && root.matches(selector)) found.unshift(root);
    return found;
  };

  const csrfToken = (el) => {
    const holder = el && el.closest('[data-csrf-token]');
    if (holder) return holder.getAttribute('data-csrf-token');
    const meta = document.querySelector('meta[name="csrfToken"],meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="_csrfToken"]');
    return input ? input.value : '';
  };
  /** The data-endpoint of `el` (or an ancestor) and the CSRF token, read now (before `el` leaves the DOM). */
  const endpointOf = (el) => {
    const holder = el && el.closest('[data-endpoint]');
    return holder ? { raw: holder.getAttribute('data-endpoint'), token: csrfToken(el) } : null;
  };
  const browserEnv = () => ({
    base: document.baseURI,
    origin: window.location.origin,
    fetch: (url, init) => window.fetch(url, init),
    error: (message) => console.error(`[BasecoatScreens] ${message}`),
  });
  /**
   * POSTs `payload` to `endpoint` (endpointOf). Returns null without an endpoint, else a promise of
   * {ok, status, reason: 'response'|'origin'}; cross-origin endpoints are refused.
   */
  const request = (endpoint, payload) => endpointRequest(endpoint, payload, browserEnv());
  const failEvent = (el, name, detail, result) => dispatch(el, name, Object.assign({}, detail, { status: result.status, reason: result.reason }), false);

  // ---------------------------------------------------------------- counts
  let countsQueued = false;
  const refreshCounts = () => {
    countsQueued = false;
    document.querySelectorAll('[data-count-for]').forEach((el) => {
      let matches = [];
      try { matches = Array.from(document.querySelectorAll(el.getAttribute('data-count-for'))); } catch (e) { return; }
      if (el.hasAttribute('data-count-visible')) matches = matches.filter(isVisible);
      const value = countValue(matches.length, el.getAttribute('data-count-offset'));
      if (el.textContent !== String(value)) el.textContent = String(value);
      if (el.hasAttribute('data-count-hide-zero')) {
        // data-count-hide-zero=".badge": hide that ancestor (else the element itself) at 0
        let target = el;
        const selector = el.getAttribute('data-count-hide-zero');
        if (selector) { try { target = el.closest(selector) || el; } catch (e) { target = el; } }
        target.hidden = value === 0;
      }
    });
  };
  const queueCounts = () => {
    if (countsQueued) return;
    countsQueued = true;
    setTimeout(refreshCounts, 0);
  };

  // ---------------------------------------------------------------- filter
  // State per root: {query, facets: {key: value}}; facet controls with another data-filter-key AND together.
  const filterState = new WeakMap();
  const stateOf = (root) => {
    let state = filterState.get(root);
    if (!state) {
      state = { query: '', facets: {} };
      filterState.set(root, state);
    }
    return state;
  };
  const facetKey = (control) => control.getAttribute('data-filter-key') || 'facet';
  const facetMatches = (item, value) => {
    const facet = String(value || '').trim();
    if (/^[[:]/.test(facet)) {
      try { return item.matches(facet); } catch (e) { return true; }
    }
    return matchesFacets(tokensOf(item.getAttribute('data-state') || '', item.getAttribute('data-filter-tags') || ''), { facet });
  };

  const applyFilters = (root) => {
    if (!root) return;
    const state = stateOf(root);
    const items = Array.from(root.querySelectorAll('[data-filter-item]'));
    let visible = 0;
    items.forEach((item) => {
      const text = item.getAttribute('data-filter-text') || item.textContent;
      const show = Object.keys(state.facets).every((key) => facetMatches(item, state.facets[key])) && matchesQuery(text, state.query);
      item.hidden = !show;
      if (show) visible += 1;
    });
    root.querySelectorAll('[data-filter-group]').forEach((group) => {
      const groupItems = Array.from(group.querySelectorAll('[data-filter-item]'));
      group.hidden = groupItems.length > 0 && groupItems.every((item) => item.hidden);
    });
    root.querySelectorAll('[data-filter-empty]').forEach((empty) => { empty.hidden = visible > 0; });
    dispatch(root, 'basecoat:filter', { query: state.query, facet: state.facets.facet || '', facets: Object.assign({}, state.facets), visible, total: items.length }, false);
    queueCounts();
    return { visible, total: items.length };
  };
  const applyAllFilters = () => {
    const roots = new Set();
    document.querySelectorAll('[data-filter-input],[data-filter-facet]').forEach((control) => {
      const root = $(control.getAttribute('data-filter-input') || control.getAttribute('data-filter-facet'));
      if (root && filterState.has(root)) roots.add(root);
    });
    roots.forEach(applyFilters);
  };

  let filterAnnounceTimer = null;
  const setQuery = (input, now) => {
    const root = $(input.getAttribute('data-filter-input'));
    if (!root) return;
    stateOf(root).query = input.value;
    const result = applyFilters(root);
    clearTimeout(filterAnnounceTimer);
    const speak = () => announce(t(input, 'results', result));
    if (now) speak(); else filterAnnounceTimer = setTimeout(speak, 500);
  };

  const setFacet = (control, value) => {
    const target = control.getAttribute('data-filter-facet');
    const root = $(target);
    if (!root) return false;
    const key = facetKey(control);
    stateOf(root).facets[key] = value;
    if (control.tagName !== 'SELECT') {
      document.querySelectorAll('[data-filter-facet]').forEach((other) => {
        if (other.getAttribute('data-filter-facet') !== target || other.tagName === 'SELECT' || facetKey(other) !== key) return;
        setActive(other, other === control);
      });
    }
    const result = applyFilters(root);
    announce(t(control, 'results', result));
    return true;
  };

  // ---------------------------------------------------------------- view switch
  const setView = (control) => {
    const root = $(control.getAttribute('data-view-target'));
    if (!root) return false;
    const view = control.getAttribute('data-view-switch');
    root.setAttribute('data-view', view);
    root.querySelectorAll('[data-view-panel]').forEach((panel) => { panel.hidden = panel.getAttribute('data-view-panel') !== view; });
    within(root, '[data-view-classes]').forEach((el) => {
      const map = parseJson(el.getAttribute('data-view-classes'));
      if (map) el.className = swapClasses(Array.from(el.classList), map, view).join(' ');
    });
    document.querySelectorAll('[data-view-switch][data-view-target]').forEach((other) => {
      if (other.getAttribute('data-view-target') !== control.getAttribute('data-view-target')) return;
      setActive(other, other.getAttribute('data-view-switch') === view);
    });
    replaceUrl(control);
    dispatch(root, 'basecoat:view', { view }, false);
    return true;
  };

  // ---------------------------------------------------------------- select all + bulk bar
  const scopeItems = (scope) => Array.from(scope.querySelectorAll('input[type="checkbox"][data-select-item]'));

  const updateSelection = (scope, notify) => {
    const items = scopeItems(scope);
    const visibleItems = items.filter(isVisible);
    const selected = items.filter((item) => item.checked);
    items.forEach((item) => {
      const row = item.closest('tr,[data-select-row]');
      if (row) row.toggleAttribute('data-selected', item.checked);
    });
    const state = selectAllState(visibleItems.filter((item) => item.checked).length, visibleItems.length);
    document.querySelectorAll('[data-select-all]').forEach((all) => {
      if ($(all.getAttribute('data-select-all')) !== scope) return;
      all.checked = state === 'all';
      all.indeterminate = state === 'some';
    });
    document.querySelectorAll('[data-bulk-bar]').forEach((bar) => {
      if ($(bar.getAttribute('data-bulk-bar')) !== scope) return;
      bar.hidden = selected.length === 0;
      bar.querySelectorAll('[data-bulk-count]').forEach((count) => { count.textContent = String(selected.length); });
    });
    if (notify) {
      dispatch(scope, 'basecoat:select', { selected: selected.map((item) => item.value), count: selected.length }, false);
      queueCounts();
    }
  };
  const scopesOf = (checkbox) => {
    const scopes = new Set();
    document.querySelectorAll('[data-select-all],[data-bulk-bar]').forEach((el) => {
      const scope = $(el.getAttribute('data-select-all') || el.getAttribute('data-bulk-bar'));
      if (scope && (!checkbox || scope.contains(checkbox))) scopes.add(scope);
    });
    return scopes;
  };

  // ---------------------------------------------------------------- toggle / remove
  /** The item of a toggle: data-toggle-target="#row" (a control outside the row, e.g. in a reading pane) or the closest [data-toggle-item]. */
  const toggleItemOf = (control) => {
    const target = control.getAttribute('data-toggle-target');
    return (target && $(target)) || control.closest('[data-toggle-item]');
  };
  /** Controls of toggle `name` for `item`: inside it, and elsewhere with data-toggle-target pointing at it. */
  const controlsFor = (item, name) => {
    const inside = Array.from(item.querySelectorAll(`[data-toggle="${CSS.escape(name)}"]`)).filter((c) => !c.hasAttribute('data-toggle-target'));
    const outside = Array.from(document.querySelectorAll(`[data-toggle="${CSS.escape(name)}"][data-toggle-target]`)).filter((c) => toggleItemOf(c) === item);
    return inside.concat(outside);
  };

  const readToggle = (item, name, attr) => (attr === 'data-state' ? item.getAttribute(attr) === name : item.hasAttribute(attr) && item.getAttribute(attr) !== 'false');
  /** Sets a checkbox / aria-pressed control to the current state of its item. */
  const syncControl = (control) => {
    const item = toggleItemOf(control);
    if (!item) return;
    const on = readToggle(item, control.getAttribute('data-toggle'), control.getAttribute('data-toggle-attr') || 'data-state');
    if (control.matches('input[type="checkbox"]')) control.checked = on;
    else if (control.hasAttribute('aria-pressed')) control.setAttribute('aria-pressed', String(on));
  };

  const writeToggle = (item, control, name, on) => {
    const attr = control.getAttribute('data-toggle-attr') || 'data-state';
    const off = control.getAttribute('data-toggle-off');
    if (on) item.setAttribute(attr, attr === 'data-state' ? name : 'true');
    else if (off !== null) item.setAttribute(attr, off);
    else item.removeAttribute(attr);
    // data-toggle-unset="data-unread": attributes removed while on (set to "true" again when off)
    splitList(control.getAttribute('data-toggle-unset')).forEach((unset) => {
      if (on) item.removeAttribute(unset); else item.setAttribute(unset, 'true');
    });
    splitList(control.getAttribute('data-toggle-class')).forEach((c) => item.classList.toggle(c, on));
    controlsFor(item, name).forEach((other) => {
      if (other.matches('input[type="checkbox"]')) other.checked = on;
      else if (other.hasAttribute('aria-pressed')) other.setAttribute('aria-pressed', String(on));
    });
    item.querySelectorAll(`[data-toggle-for="${CSS.escape(name)}"]`).forEach((el) => {
      const text = el.getAttribute(on ? 'data-text-on' : 'data-text-off');
      if (text !== null) el.textContent = text;
      const hide = el.getAttribute('data-toggle-hidden');
      if (hide === 'on' || hide === 'off') el.hidden = (hide === 'on') === on;
    });
  };

  /**
   * The focused control was hidden by its own toggle ("+N more", Accept, Mark as read): focus
   * data-toggle-focus (selector, inside the item first), else the first control the toggle revealed,
   * else the item itself.
   */
  const keepFocus = (item, control, name) => {
    if (!control.closest('[hidden]')) return;
    const selector = control.getAttribute('data-toggle-focus');
    let target = null;
    if (selector) {
      try { target = item.querySelector(selector) || document.querySelector(selector); } catch (e) { target = null; }
    }
    if (!target) {
      const revealed = Array.from(item.querySelectorAll(`[data-toggle-for="${CSS.escape(name)}"]`)).filter((el) => !el.closest('[hidden]'));
      target = revealed.map(focusableIn).find(Boolean) || null;
    }
    focusElement(target || item);
  };

  /** The filter root an item belongs to (the nearest ancestor with filter state). */
  const filterRootOf = (item) => {
    for (let el = item.parentElement; el; el = el.parentElement) if (filterState.has(el)) return el;
    return null;
  };
  /**
   * After the filters ran: the focus was in the item, but an active filter hid the item (Status=Unread and
   * the row was just read): focus the next visible row (else the previous one), else the filter root.
   */
  const rescueFocus = (item) => {
    const active = document.activeElement;
    if (active && active !== document.body && !active.closest('[hidden]')) return;
    if (!item.isConnected) return;
    const sibling = visibleNeighbour(item);
    const target = (sibling && focusableIn(sibling)) || sibling || filterRootOf(item) || item.parentElement;
    if (target) focusElement(target);
  };

  /** Adapter of toggle `name` of `item` through `control` for toggleTransaction. */
  const toggleChange = (item, control, name, on) => {
    const attr = control.getAttribute('data-toggle-attr') || 'data-state';
    return {
      name,
      on,
      isOn: () => readToggle(item, name, attr),
      read: () => ({ value: item.getAttribute(attr), on: readToggle(item, name, attr) }),
      write: (value) => writeToggle(item, control, name, value),
      restore: (snapshot) => {
        writeToggle(item, control, name, snapshot.on);
        if (snapshot.value === null) item.removeAttribute(attr); else item.setAttribute(attr, snapshot.value);
      },
      confirm: () => dispatch(item, 'basecoat:toggle', { item, id: idOf(item), name, on }),
    };
  };
  /** data-toggle-also="read": switching on also switches those on, through their control or as data-<name>="true". */
  const chainedChanges = (item, control, name) => splitList(control.getAttribute('data-toggle-also')).filter((also) => also !== name).map((also) => {
    let alsoControl = controlsFor(item, also)[0];
    if (!alsoControl) {
      alsoControl = document.createElement('button');
      alsoControl.setAttribute('data-toggle', also);
      alsoControl.setAttribute('data-toggle-attr', `data-${also}`);
    }
    return toggleChange(item, alsoControl, also, true);
  });
  const setBusy = (item, busy) => {
    item.toggleAttribute('data-busy', busy);
    if (busy) item.setAttribute('aria-busy', 'true'); else item.removeAttribute('aria-busy');
  };

  /**
   * Switches toggle `name` of `item` (and its data-toggle-also chain) and POSTs it as one request
   * {action: 'toggle', id, name, on, also}. options.quiet: no announcement; options.local: no request
   * (data-toggle-all sends one for every item). Returns the transaction ({status, undo, ...}).
   */
  const setToggle = (item, control, name, on, options = {}) => {
    const hadFocus = item.contains(document.activeElement) || control.contains(document.activeElement);
    const detail = { item, id: idOf(item), name, on };
    const endpoint = options.local ? null : endpointOf(item);
    const refresh = () => {
      applyAllFilters();
      queueCounts();
    };
    const result = toggleTransaction({
      busy: () => item.hasAttribute('data-busy'),
      main: toggleChange(item, control, name, on),
      chained: chainedChanges(item, control, name),
      after: () => {
        // data-announce-on / data-announce-off="{label} marked as read." on the control
        const message = options.quiet ? null : control.getAttribute(on ? 'data-announce-on' : 'data-announce-off');
        if (message) announce(format(message, { label: labelOf(item) }));
        // After the chained toggles, which may hide the control too (Accept hides the whole answer group)
        if (hadFocus) keepFocus(item, control, name);
        refresh();
        // ... and after the filters, which may hide the whole row (Status = Unread, then Mark as read)
        if (hadFocus) rescueFocus(item);
      },
      send: (also) => request(endpoint, Object.assign({ action: 'toggle', id: detail.id, name, on }, also.length ? { also } : {})),
      setBusy: (busy) => setBusy(item, busy),
      fail: (failure) => {
        refresh();
        announce(t(item, 'failed'));
        failEvent(item, 'basecoat:toggle-error', detail, failure);
      },
    });
    if (result.status === 'cancelled') refresh();
    return result;
  };

  /**
   * [data-toggle-all="read"]: every item of the scope on, locally, then one request
   * {action: 'toggle-all', name, on: true, ids}; a failure reverts every item.
   */
  const toggleAll = (button) => {
    const scope = $(button.getAttribute('data-toggle-scope')) || document;
    const name = button.getAttribute('data-toggle-all');
    const changed = [];
    scope.querySelectorAll('[data-toggle-item]').forEach((item) => {
      const control = controlsFor(item, name)[0] || button;
      const result = setToggle(item, control, name, true, { quiet: true, local: true });
      if (result.status === 'applied') changed.push({ item, result });
    });
    const message = button.getAttribute('data-announce-on');
    if (message) announce(message);
    if (!changed.length) return;
    const ids = changed.map((entry) => idOf(entry.item));
    const detail = { item: scope instanceof Element ? scope : button, ids, name, on: true };
    settle(request(endpointOf(scope instanceof Element ? scope : button), { action: 'toggle-all', name, on: true, ids }), {
      busy: (busy) => changed.forEach((entry) => setBusy(entry.item, busy)),
      revert: (failure) => {
        changed.slice().reverse().forEach((entry) => entry.result.undo());
        applyAllFilters();
        queueCounts();
        announce(t(button, 'failed'));
        failEvent(detail.item, 'basecoat:toggle-error', detail, failure);
      },
    });
  };

  /**
   * The item a remove button acts on: data-remove-target="#row" (e.g. Archive in a reading pane), else
   * the closest [data-toggle-item] / [data-filter-item] / [data-remove-target] ancestor.
   */
  const removeTargetOf = (button) => {
    const selector = button.getAttribute('data-remove-target');
    const target = selector ? $(selector) : null;
    if (target) return target;
    return button.parentElement ? button.parentElement.closest('[data-toggle-item],[data-filter-item],[data-remove-target]') : null;
  };

  /**
   * Removing the row open in a master-detail pane: the pane gets the master's data-master-empty template.
   * Returns {show(), undo()} (undo puts the previous pane content back), or null.
   */
  const emptyPaneFor = (item) => {
    const open = item.matches('[data-master-item][aria-current="true"]') ? item : item.querySelector('[data-master-item][aria-current="true"]');
    const master = open && open.closest('[data-master]');
    if (!master) return null;
    const pane = $(master.getAttribute('data-master'));
    const template = document.getElementById(master.getAttribute('data-master-empty') || '');
    if (!pane || !(template instanceof HTMLTemplateElement)) return null;
    let previous = [];
    return {
      show: () => {
        previous = Array.from(pane.childNodes);
        pane.replaceChildren(template.content.cloneNode(true));
      },
      undo: () => pane.replaceChildren(...previous),
    };
  };

  const removeItem = (button) => {
    const item = removeTargetOf(button);
    if (!item || item.hasAttribute('data-busy')) return;
    const action = button.getAttribute('data-remove-item') || 'remove';
    const detail = { item, id: idOf(item), action };
    if (!dispatch(item, 'basecoat:remove', detail)) return;
    const parent = item.parentElement;
    const next = item.nextElementSibling;
    const label = labelOf(item);
    const focusNext = visibleNeighbour(item);
    const emptyPane = emptyPaneFor(item);
    // Read before the item leaves the DOM: data-endpoint / data-csrf-token may be on the item itself
    const endpoint = endpointOf(item);
    item.remove();
    if (emptyPane) emptyPane.show();
    const target = focusNext && focusableIn(focusNext);
    let focused = null;
    if (target) {
      focused = target;
    } else if (parent) {
      // Last item gone: keep the focus in the list instead of losing it to <body>.
      if (!parent.hasAttribute('tabindex')) parent.setAttribute('tabindex', '-1');
      focused = parent;
    }
    if (focused) focused.focus();
    announce(t(button, 'removed', { label }));
    applyAllFilters();
    queueCounts();
    settle(request(endpoint, { action, id: detail.id }), {
      revert: (result) => {
        parent.insertBefore(item, next && next.parentElement === parent ? next : null);
        if (emptyPane) emptyPane.undo();
        // Focus back on the restored row, unless the user moved on meanwhile
        const active = document.activeElement;
        if (!active || active === document.body || active === focused) focusElement(focusableIn(item) || item);
        announce(t(parent, 'failed'));
        applyAllFilters();
        queueCounts();
        failEvent(item, 'basecoat:remove-error', detail, result);
      },
    });
  };

  // ---------------------------------------------------------------- master-detail
  const openDetail = (control) => {
    const master = control.closest('[data-master]');
    const detail = master && $(master.getAttribute('data-master'));
    const template = document.getElementById(control.getAttribute('data-master-item'));
    if (!detail || !(template instanceof HTMLTemplateElement)) return false;
    if (!dispatch(control, 'basecoat:open', { item: control, id: idOf(control), detail })) return true;
    detail.replaceChildren(template.content.cloneNode(true));
    master.querySelectorAll('[data-master-item]').forEach((other) => {
      if (other === control) other.setAttribute('aria-current', 'true'); else other.removeAttribute('aria-current');
    });
    control.querySelectorAll('[data-master-clear]').forEach((el) => el.remove());
    control.removeAttribute('data-unread');
    replaceUrl(control);
    // Pane controls acting on a row (data-toggle-target) show the row's current state, not the template's
    detail.querySelectorAll('[data-toggle][data-toggle-target]').forEach(syncControl);
    init(detail);
    if (window.BasecoatDnd && typeof window.BasecoatDnd.init === 'function') window.BasecoatDnd.init(detail);
    if (master.hasAttribute('data-master-focus')) {
      if (!detail.hasAttribute('tabindex')) detail.setAttribute('tabindex', '-1');
      detail.focus();
    }
    announce(t(control, 'opened', { label: labelOf(control) }));
    queueCounts();
    return true;
  };

  // ---------------------------------------------------------------- composer
  const timers = new Set();
  const composerInput = (form) => form.querySelector('[data-composer-input]') || form.querySelector('textarea') || form.querySelector('input[type="text"],input:not([type])');
  const scrollLog = (log, node) => {
    log.scrollTop = log.scrollHeight;
    if (node && typeof node.scrollIntoView === 'function') node.scrollIntoView({ block: 'nearest', behavior: reducedMotion() ? 'auto' : 'smooth' });
  };
  const bubble = (templateId, text) => {
    const template = document.getElementById(templateId || '');
    if (!(template instanceof HTMLTemplateElement)) return null;
    const fragment = template.content.cloneNode(true);
    const node = fragment.firstElementChild;
    fragment.querySelectorAll('[data-composer-text]').forEach((el) => { if (text !== null) el.textContent = text; });
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    fragment.querySelectorAll('[data-composer-time]').forEach((el) => { el.textContent = time; });
    return { fragment, node };
  };

  const send = (form) => {
    const log = $(form.getAttribute('data-composer'));
    const input = composerInput(form);
    if (!log || !input) return;
    const text = input.value.trim();
    if (!text) {
      input.focus();
      return;
    }
    const made = bubble(form.getAttribute('data-composer-template'), text);
    if (!made) return;
    if (!dispatch(form, 'basecoat:send', { text, log, message: made.node })) return;
    log.appendChild(made.fragment);
    input.value = '';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.focus();
    scrollLog(log, made.node);
    settle(request(endpointOf(form), { action: 'send', id: idOf(form), text }), {
      revert: (result) => {
        if (made.node) made.node.setAttribute('data-state', 'failed');
        announce(t(form, 'send-failed'));
        failEvent(form, 'basecoat:send-error', { text, log, message: made.node }, result);
      },
    });
    const replyId = form.getAttribute('data-composer-reply');
    if (replyId) {
      const timer = setTimeout(() => {
        timers.delete(entry);
        if (!log.isConnected) return;
        const reply = bubble(replyId, null);
        if (!reply) return;
        log.appendChild(reply.fragment);
        scrollLog(log, reply.node);
      }, reducedMotion() ? 0 : 600);
      const entry = { timer, log };
      timers.add(entry);
    }
    queueCounts();
  };

  // ---------------------------------------------------------------- stepper
  const panelsOf = (stepper) => Array.from(stepper.querySelectorAll('[data-step]')).filter((p) => p.closest('[data-stepper]') === stepper);
  const currentStep = (stepper) => {
    const panels = panelsOf(stepper);
    const index = panels.findIndex((p) => !p.hidden);
    return index < 0 ? 0 : index;
  };
  const ownControls = (stepper, selector) => Array.from(stepper.querySelectorAll(selector))
    .filter((el) => el.closest('[data-stepper]') === stepper && !el.closest('[data-step]'));

  /**
   * Inline error of a field: the element named by its aria-errormessage gets the browser's validation
   * message (shown, linked through aria-describedby) and the field aria-invalid; cleared when valid.
   */
  const showFieldError = (field, valid) => {
    const id = field.getAttribute('aria-errormessage');
    const box = id ? document.getElementById(id) : null;
    if (!box) return false;
    const described = splitList(field.getAttribute('aria-describedby')).filter((d) => d !== id);
    if (valid) {
      field.removeAttribute('aria-invalid');
      box.textContent = '';
      box.hidden = true;
    } else {
      field.setAttribute('aria-invalid', 'true');
      box.textContent = field.validationMessage;
      box.hidden = false;
      described.push(id);
    }
    if (described.length) field.setAttribute('aria-describedby', described.join(' ')); else field.removeAttribute('aria-describedby');
    return true;
  };

  const validatePanel = (panel) => {
    const fields = Array.from(panel.querySelectorAll('input,select,textarea')).filter((f) => !f.disabled && typeof f.checkValidity === 'function');
    let invalid = null;
    fields.forEach((f) => {
      const valid = f.checkValidity();
      showFieldError(f, valid);
      if (!valid && !invalid) invalid = f;
    });
    if (!invalid) return true;
    if (invalid.hasAttribute('aria-errormessage') && document.getElementById(invalid.getAttribute('aria-errormessage'))) announce(invalid.validationMessage);
    else if (typeof invalid.reportValidity === 'function') invalid.reportValidity();
    invalid.focus();
    return false;
  };

  /**
   * [data-bind="field-name"] inside the stepper shows the value of that field (select: option text;
   * checkbox / radio: label text of the checked ones; joined with ", "), or data-bind-empty.
   */
  const fieldText = (field) => {
    if (field instanceof HTMLSelectElement) return Array.from(field.selectedOptions).filter((o) => o.value !== '').map((o) => o.textContent.trim());
    if (field.type === 'checkbox' || field.type === 'radio') {
      if (!field.checked) return [];
      const label = field.labels && field.labels[0];
      return [label ? label.textContent.replace(/\s+/g, ' ').trim() : field.value];
    }
    return [field.value];
  };
  const refreshBindings = (stepper) => {
    stepper.querySelectorAll('[data-bind]').forEach((el) => {
      const map = parseJson(el.getAttribute('data-bind-map')) || {};
      const fields = splitList(el.getAttribute('data-bind')).reduce((all, name) => all.concat(
        Array.from(stepper.querySelectorAll(`[name="${CSS.escape(name)}"]`)).filter((f) => f.type !== 'hidden' && !f.disabled),
      ), []);
      if (!fields.length) return;
      const values = fields.reduce((all, f) => all.concat(
        // data-bind-map='{"value": "Text"}' names values (radio cards whose label holds more than a name)
        map && (f.type === 'radio' || f.type === 'checkbox') ? (f.checked ? [map[f.value] !== undefined ? map[f.value] : fieldText(f)[0]] : []) : fieldText(f),
      ), []);
      const text = joinValues(values, el.getAttribute('data-bind-empty'));
      if (el.textContent !== text) el.textContent = text;
    });
  };

  const renderStep = (stepper, index, focus) => {
    const panels = panelsOf(stepper);
    const total = panels.length;
    panels.forEach((panel, i) => { panel.hidden = i !== index; });
    const states = stepStates(index, total);
    Array.from(stepper.querySelectorAll('[data-step-indicator]')).filter((el) => el.closest('[data-stepper]') === stepper).forEach((el, i) => {
      el.setAttribute('data-state', states[i] || 'upcoming');
      if (i === index) el.setAttribute('aria-current', 'step'); else el.removeAttribute('aria-current');
    });
    stepper.querySelectorAll('[data-step-current]').forEach((el) => { el.textContent = String(index + 1); });
    stepper.querySelectorAll('[data-step-total]').forEach((el) => { el.textContent = String(total); });
    const percent = total ? Math.round(((index + 1) / total) * 100) : 0;
    stepper.querySelectorAll('[data-step-progress]').forEach((el) => {
      if (el instanceof HTMLProgressElement) {
        el.max = 100;
        el.value = percent;
      } else {
        el.setAttribute('data-progress-value', String(percent));
      }
    });
    if (window.BasecoatTheme && typeof window.BasecoatTheme.initProgress === 'function') window.BasecoatTheme.initProgress(stepper);
    ownControls(stepper, '[data-step-prev]').forEach((el) => { el.hidden = index === 0; });
    ownControls(stepper, '[data-step-next]').forEach((el) => { el.hidden = index >= total - 1; });
    ownControls(stepper, '[data-step-finish]').forEach((el) => { el.hidden = index < total - 1; });
    stepper.setAttribute('data-step-index', String(index));
    refreshBindings(stepper);
    if (focus && panels[index]) {
      const panel = panels[index];
      const heading = panel.querySelector('h1,h2,h3,h4,legend') || panel;
      if (!heading.hasAttribute('tabindex')) heading.setAttribute('tabindex', '-1');
      heading.focus();
      const title = panel.getAttribute('data-step-title') || (heading !== panel ? heading.textContent.trim() : '');
      announce(t(stepper, 'step', { step: index + 1, total, title }));
    }
  };

  const goToStep = (stepper, target) => {
    const panels = panelsOf(stepper);
    const from = currentStep(stepper);
    const to = clamp(target, 0, panels.length - 1);
    if (to === from) return;
    // Forward: every step in between must be valid.
    for (let i = from; i < to; i += 1) {
      if (i !== from) renderStep(stepper, i, false);
      if (!validatePanel(panels[i])) return;
    }
    if (!dispatch(stepper, 'basecoat:step', { from, to, total: panels.length })) {
      renderStep(stepper, from, false);
      return;
    }
    renderStep(stepper, to, true);
  };

  // ---------------------------------------------------------------- drop zone
  // [data-dropzone] with input[type=file][data-dropzone-input], [data-dropzone-list] holding
  // <template data-dropzone-item> ([data-dropzone-name], [data-dropzone-size], [data-dropzone-remove] with
  // data-dropzone-remove-label="Remove {name}"), [data-dropzone-actions] ([data-dropzone-upload],
  // [data-dropzone-clear]). Files are only listed; Upload fires basecoat:upload {files}.
  const sameFile = (a, b) => a.name === b.name && a.size === b.size && a.lastModified === b.lastModified;
  const setupDropzone = (zone) => {
    if (zone.hasAttribute('data-dropzone-ready')) return;
    const input = zone.querySelector('[data-dropzone-input]');
    const list = zone.querySelector('[data-dropzone-list]');
    const template = zone.querySelector('template[data-dropzone-item]');
    const actions = zone.querySelector('[data-dropzone-actions]');
    if (!input || !list || !(template instanceof HTMLTemplateElement) || !template.content.firstElementChild) return;
    zone.setAttribute('data-dropzone-ready', '');
    let files = [];

    const render = () => {
      list.querySelectorAll('[data-dropzone-row]').forEach((row) => row.remove());
      files.forEach((file, index) => {
        const row = template.content.firstElementChild.cloneNode(true);
        row.setAttribute('data-dropzone-row', String(index));
        row.querySelectorAll('[data-dropzone-name]').forEach((el) => { el.textContent = file.name; });
        row.querySelectorAll('[data-dropzone-size]').forEach((el) => { el.textContent = formatSize(file.size); });
        row.querySelectorAll('[data-dropzone-remove]').forEach((remove) => {
          const label = remove.getAttribute('data-dropzone-remove-label');
          if (label) remove.setAttribute('aria-label', format(label, { name: file.name }));
        });
        list.appendChild(row);
      });
      list.hidden = files.length === 0;
      if (actions) actions.hidden = files.length === 0;
    };
    const add = (incoming) => {
      const added = Array.from(incoming || []).filter((file) => !files.some((known) => sameFile(known, file)));
      if (!added.length) return;
      files = files.concat(added);
      render();
      announce(t(zone, 'files-added', { count: added.length, total: files.length }));
    };

    input.addEventListener('change', () => {
      add(input.files);
      input.value = '';
    });
    let depth = 0;
    const hasFiles = (event) => !!event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files');
    zone.addEventListener('dragenter', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      depth += 1;
      zone.setAttribute('data-over', '');
    });
    zone.addEventListener('dragover', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      event.dataTransfer.dropEffect = 'copy';
    });
    zone.addEventListener('dragleave', () => {
      depth = Math.max(0, depth - 1);
      if (depth === 0) zone.removeAttribute('data-over');
    });
    zone.addEventListener('drop', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      depth = 0;
      zone.removeAttribute('data-over');
      add(event.dataTransfer.files);
    });
    zone.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) return;
      const remove = target.closest('[data-dropzone-remove]');
      if (remove) {
        const row = remove.closest('[data-dropzone-row]');
        const index = row ? Number(row.getAttribute('data-dropzone-row')) : -1;
        if (index >= 0 && files[index]) {
          const [gone] = files.splice(index, 1);
          render();
          announce(t(zone, 'file-removed', { name: gone.name, total: files.length }));
          input.focus();
        }
        return;
      }
      if (target.closest('[data-dropzone-clear]')) {
        files = [];
        render();
        announce(t(zone, 'files-cleared'));
        input.focus();
        return;
      }
      if (target.closest('[data-dropzone-upload]') && files.length) dispatch(zone, 'basecoat:upload', { files: files.slice() });
    });
  };

  // ---------------------------------------------------------------- delegated listeners
  document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.matches('[data-filter-input]')) setQuery(target);
    const stepper = target.closest('[data-stepper]');
    if (stepper) {
      refreshBindings(stepper);
      if (target.getAttribute('aria-invalid') === 'true' && target.checkValidity()) showFieldError(target, true);
    }
  });

  document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.matches('select[data-filter-facet]')) {
      setFacet(target, target.value);
      return;
    }
    if (target.matches('input[type="checkbox"][data-select-all]')) {
      const scope = $(target.getAttribute('data-select-all'));
      if (!scope) return;
      scopeItems(scope).filter(isVisible).forEach((item) => { item.checked = target.checked; });
      updateSelection(scope, true);
      announce(t(target, 'selected', { count: scopeItems(scope).filter((i) => i.checked).length }));
      return;
    }
    if (target.matches('input[type="checkbox"][data-select-item]')) {
      scopesOf(target).forEach((scope) => updateSelection(scope, true));
      return;
    }
    if (target.matches('input[type="checkbox"][data-toggle]')) {
      const item = toggleItemOf(target);
      if (item) setToggle(item, target, target.getAttribute('data-toggle'), target.checked);
    }
    const stepper = target.closest('[data-stepper]');
    if (stepper) {
      refreshBindings(stepper);
      if (target.getAttribute('aria-invalid') === 'true' && target.checkValidity()) showFieldError(target, true);
    }
  });

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target || event.defaultPrevented) return;
    const facet = target.closest('[data-filter-facet]:not(select)');
    if (facet && setFacet(facet, facet.getAttribute('data-filter-value') || '')) {
      event.preventDefault();
      return;
    }
    const view = target.closest('[data-view-switch][data-view-target]');
    if (view && setView(view)) {
      event.preventDefault();
      return;
    }
    const clear = target.closest('[data-bulk-clear]');
    if (clear) {
      const bar = clear.closest('[data-bulk-bar]');
      const scope = bar && $(bar.getAttribute('data-bulk-bar'));
      if (scope) {
        scopeItems(scope).forEach((item) => { item.checked = false; });
        updateSelection(scope, true);
      }
      return;
    }
    const allControl = target.closest('[data-toggle-all]');
    if (allControl) {
      event.preventDefault();
      toggleAll(allControl);
      return;
    }
    const toggle = target.closest('button[data-toggle],a[data-toggle]');
    if (toggle) {
      const item = toggleItemOf(toggle);
      if (!item) return;
      event.preventDefault();
      const name = toggle.getAttribute('data-toggle');
      const on = toggle.hasAttribute('aria-pressed') ? toggle.getAttribute('aria-pressed') !== 'true' : true;
      setToggle(item, toggle, name, on);
      return;
    }
    const remove = target.closest('[data-remove-item]');
    if (remove) {
      event.preventDefault();
      removeItem(remove);
      return;
    }
    const masterItem = target.closest('[data-master-item]');
    if (masterItem && openDetail(masterItem)) {
      event.preventDefault();
      return;
    }
    const fill = target.closest('[data-composer-fill]');
    if (fill) {
      const form = $(fill.getAttribute('data-composer-target')) || fill.closest('form[data-composer]') || document.querySelector('form[data-composer]');
      const input = form && composerInput(form);
      if (!input) return;
      event.preventDefault();
      input.value = fill.getAttribute('data-composer-fill') || fill.textContent.trim();
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.focus();
      if (fill.hasAttribute('data-composer-send')) send(form);
      return;
    }
    const stepControl = target.closest('[data-step-next],[data-step-prev],[data-step-go]');
    if (stepControl) {
      const stepper = stepControl.closest('[data-stepper]');
      if (!stepper) return;
      event.preventDefault();
      const current = currentStep(stepper);
      if (stepControl.hasAttribute('data-step-next')) goToStep(stepper, current + 1);
      else if (stepControl.hasAttribute('data-step-prev')) goToStep(stepper, current - 1);
      else goToStep(stepper, parseInt(stepControl.getAttribute('data-step-go'), 10) || 0);
    }
  });

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-composer]')) return;
    event.preventDefault();
    send(form);
  });

  // Enter in a filter input filters at once instead of submitting its (no-JS) GET form.
  document.addEventListener('keydown', (event) => {
    const target = event.target;
    if (event.key !== 'Enter' || event.isComposing || !(target instanceof HTMLInputElement) || !target.matches('[data-filter-input]')) return;
    event.preventDefault();
    setQuery(target, true);
  });

  document.addEventListener('keydown', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLTextAreaElement) || event.key !== 'Enter' || event.shiftKey || event.isComposing) return;
    const form = target.closest('form[data-composer]');
    if (!form || target !== composerInput(form)) return;
    event.preventDefault();
    if (typeof form.requestSubmit === 'function') form.requestSubmit(); else send(form);
  });

  // Counts follow every interaction of both libraries.
  ['basecoat:move', 'basecoat:resize', 'basecoat:move-error'].forEach((name) => document.addEventListener(name, queueCounts));
  document.addEventListener('basecoat:move', () => setTimeout(applyAllFilters, 0));

  // ---------------------------------------------------------------- init / cleanup
  const init = (root = document) => {
    if (!root || !root.querySelectorAll) return;
    within(root, '[data-filter-input]').forEach((input) => {
      const target = $(input.getAttribute('data-filter-input'));
      if (target) stateOf(target).query = input.value;
    });
    within(root, '[data-filter-facet]').forEach((control) => {
      const target = $(control.getAttribute('data-filter-facet'));
      if (!target) return;
      const state = stateOf(target);
      if (control.tagName === 'SELECT') state.facets[facetKey(control)] = control.value;
      else if (control.getAttribute('aria-pressed') === 'true' || control.getAttribute('aria-selected') === 'true' || control.getAttribute('aria-current') === 'true') state.facets[facetKey(control)] = control.getAttribute('data-filter-value') || '';
    });
    applyAllFilters();
    scopesOf(null).forEach((scope) => updateSelection(scope, false));
    within(root, '[data-stepper]').forEach((stepper) => {
      if (stepper.hasAttribute('data-stepper-ready')) return;
      refreshBindings(stepper);
      stepper.setAttribute('data-stepper-ready', '');
      const start = stepper.getAttribute('data-stepper-start');
      renderStep(stepper, start !== null ? clamp(parseInt(start, 10) || 0, 0, Math.max(panelsOf(stepper).length - 1, 0)) : currentStep(stepper), false);
    });
    within(root, '[data-dropzone]').forEach(setupDropzone);
    within(root, 'form[data-composer]').forEach((form) => {
      const log = $(form.getAttribute('data-composer'));
      if (log && !log.hasAttribute('aria-live') && log.getAttribute('role') !== 'log') log.setAttribute('aria-live', 'polite');
    });
    refreshCounts();
  };

  const boot = () => {
    liveRegion(); // present before the first announcement, so screen readers pick it up
    init(document);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  document.addEventListener('htmx:load', (event) => init(event.target));
  document.addEventListener('htmx:afterSwap', (event) => {
    jsonCache.clear(); // swapped-out markup leaves stale data-* JSON behind
    init(event.target);
  });
  document.addEventListener('htmx:beforeCleanupElement', (event) => {
    if (!(event.target instanceof Element)) return;
    timers.forEach((entry) => {
      if (event.target.contains(entry.log)) {
        clearTimeout(entry.timer);
        timers.delete(entry);
      }
    });
  });

  window.BasecoatScreens = Object.assign({ init, refreshCounts, applyFilters, announce }, pure);
})();
