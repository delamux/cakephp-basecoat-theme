/*
 * BasecoatTheme screen behaviours (webroot/js/basecoat-screens.js).
 *
 * Load it with the CSP nonce, e.g.:  $this->Html->script('BasecoatTheme.basecoat-screens', ['block' => true, 'defer' => true]);
 * Vanilla, no dependencies, event delegation, no inline styles (visibility through the `hidden`
 * attribute), no innerHTML (text through textContent, markup cloned from <template>). Every behaviour
 * is progressive enhancement: links/forms keep working without JS; with JS their default is prevented.
 * Selectors in attribute values ("#list") are CSS selectors resolved with document.querySelector.
 *
 * FILTER   input[data-filter-input="#root"] (search text)  and/or  facet controls
 *          [data-filter-facet="#root"] (button/link with data-filter-value, or a <select>).
 *          Items: [data-filter-item] inside root; text = data-filter-text or textContent (case and accent
 *          insensitive, every word must match); facet tokens = data-state + data-filter-tags.
 *          Facet value: "" = all, "done" = has token, "!done" = lacks it, "[data-starred]" / ":not(…)" =
 *          CSS selector. Non-matching items get `hidden`; [data-filter-group] (hidden when empty),
 *          [data-filter-empty] (shown when nothing matches). Facet buttons get aria-pressed (role=tab: left
 *          to Basecoat tabs). Event basecoat:filter on root {query, facet, visible, total}.
 * VIEW     [data-view-switch="grid"][data-view-target="#root"]: root gets data-view="grid";
 *          [data-view-panel="list"] inside root are hidden unless they match; [data-view-classes='{"grid":
 *          "grid grid-cols-3","list":"flex flex-col"}'] (root or inside) swap classes. Controls get
 *          aria-pressed (links: aria-current + URL replaced). Event basecoat:view on root {view}.
 * SELECT   input[data-select-all="#scope"] + input[data-select-item] (inside scope, value = id) +
 *          [data-bulk-bar="#scope"] (hidden when nothing is selected) with [data-bulk-count] and
 *          [data-bulk-clear]. Select-all only affects visible items; indeterminate when some are checked.
 *          The row (closest tr or [data-select-row]) gets data-selected. Event basecoat:select on scope
 *          {selected: [values], count}.
 * TOGGLE   [data-toggle="name"] checkbox or button inside [data-toggle-item] (data-id = id):
 *          on -> item[data-state]="name" (or data-toggle-attr="data-starred": attribute set to "true"),
 *          off -> data-toggle-off value or attribute removed. Checkbox: on = checked. Button with
 *          aria-pressed: flips; other buttons set "on" (Mark as read, Accept). data-toggle-class on the
 *          control: classes toggled on the item. Inside the item, [data-toggle-for="name"] elements get
 *          data-text-on / data-text-off as text and `hidden` when data-toggle-hidden="on|off" matches.
 *          [data-toggle-all="name"][data-toggle-scope="#list"]: turns every item in scope on.
 *          Event basecoat:toggle (cancelable) on the item {item, id, name, on}.
 * REMOVE   [data-remove-item="archive"] button: removes its closest [data-toggle-item]/[data-filter-item]/
 *          [data-remove-target]. Event basecoat:remove (cancelable) {item, id, action}.
 * COUNT    [data-count-for="selector"]: text = number of matches in the document (data-count-visible:
 *          only those not inside [hidden]). Refreshed after every basecoat:* event of both libraries.
 * MASTER   [data-master="#detail"] with [data-master-item="template-id"] (button or link): the detail
 *          pane gets a clone of <template id="template-id">; the item gets aria-current="true" (others
 *          lose it); its [data-master-clear] descendants (unread badges) are removed. data-master-focus
 *          on the master moves focus to the pane. Event basecoat:open on the item {item, id, detail}.
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
 *          Next validates the panel's fields (checkValidity / reportValidity) first; values are kept.
 *          Nav controls outside the panels are hidden when useless (prev on the first step, next on the
 *          last, finish until the last). Event basecoat:step (cancelable) {from, to, total}.
 * ENDPOINT data-endpoint="/url" on the item / form / list or an ancestor: toggles, removals and sends are
 *          POSTed as JSON ({action: 'toggle', id, name, on} | {action, id} | {action: 'send', text})
 *          with X-CSRF-Token (data-csrf-token on an ancestor, <meta name="csrfToken"> or
 *          input[name=_csrfToken]); on failure the change is reverted and announced.
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

  /** 'none' | 'some' | 'all' for a select-all checkbox. */
  const selectAllState = (checked, total) => (total === 0 || checked === 0 ? 'none' : checked >= total ? 'all' : 'some');

  /** States of the steps for the current index. */
  const stepStates = (current, total) => Array.from({ length: total }, (_, i) => (i < current ? 'complete' : i === current ? 'current' : 'upcoming'));

  const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
  const format = (template, vars) => String(template).replace(/\{(\w+)\}/g, (m, key) => (
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

  const pure = { normalize, matchesQuery, matchesFacet, tokensOf, selectAllState, stepStates, clamp, format, swapClasses };
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
  };

  const jsonCache = new Map();
  const parseJson = (text) => {
    if (!text) return null;
    if (jsonCache.has(text)) return jsonCache.get(text);
    let value = null;
    try { value = JSON.parse(text); } catch (e) { console.error('[BasecoatScreens] Invalid JSON', e); }
    jsonCache.set(text, value);
    return value;
  };

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
  const announce = (text) => {
    if (window.BasecoatDnd && typeof window.BasecoatDnd.announce === 'function') {
      window.BasecoatDnd.announce(text);
      return;
    }
    let region = document.querySelector('[data-basecoat-announcer]');
    if (!region) {
      region = document.createElement('div');
      region.setAttribute('data-basecoat-announcer', '');
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'true');
      region.className = 'sr-only';
      document.body.appendChild(region);
    }
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
  const idOf = (el) => (el ? el.getAttribute('data-id') || el.id || null : null);
  const labelOf = (el) => el.getAttribute('data-label') || el.getAttribute('aria-label') || (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 60);
  const isVisible = (el) => !el.closest('[hidden]');
  const dispatch = (el, name, detail, cancelable = true) => el.dispatchEvent(new CustomEvent(name, { bubbles: true, cancelable, detail }));
  const replaceUrl = (control) => {
    if (control.matches('a[href]') && window.history && typeof window.history.replaceState === 'function') {
      try { window.history.replaceState(window.history.state, '', control.href); } catch (e) { /* other origin */ }
    }
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
  const endpointOf = (el) => {
    const holder = el.closest('[data-endpoint]');
    return holder ? holder.getAttribute('data-endpoint') : null;
  };
  const post = (url, payload, el) => {
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const token = csrfToken(el);
    if (token) headers['X-CSRF-Token'] = token;
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload) })
      .then((response) => response.ok, () => false);
  };

  // ---------------------------------------------------------------- counts
  let countsQueued = false;
  const refreshCounts = () => {
    countsQueued = false;
    document.querySelectorAll('[data-count-for]').forEach((el) => {
      let matches = [];
      try { matches = Array.from(document.querySelectorAll(el.getAttribute('data-count-for'))); } catch (e) { return; }
      if (el.hasAttribute('data-count-visible')) matches = matches.filter(isVisible);
      el.textContent = String(matches.length);
    });
  };
  const queueCounts = () => {
    if (countsQueued) return;
    countsQueued = true;
    setTimeout(refreshCounts, 0);
  };

  // ---------------------------------------------------------------- filter
  const filterState = new WeakMap();

  const applyFilters = (root) => {
    if (!root) return;
    const state = filterState.get(root) || { query: '', facet: '' };
    const items = Array.from(root.querySelectorAll('[data-filter-item]'));
    let visible = 0;
    items.forEach((item) => {
      const text = item.getAttribute('data-filter-text') || item.textContent;
      let facetOk = true;
      if (state.facet && /^[[:]/.test(state.facet)) {
        try { facetOk = item.matches(state.facet); } catch (e) { facetOk = true; }
      } else {
        facetOk = matchesFacet(tokensOf(item.getAttribute('data-state') || '', item.getAttribute('data-filter-tags') || ''), state.facet);
      }
      const show = facetOk && matchesQuery(text, state.query);
      item.hidden = !show;
      if (show) visible += 1;
    });
    root.querySelectorAll('[data-filter-group]').forEach((group) => {
      const groupItems = Array.from(group.querySelectorAll('[data-filter-item]'));
      group.hidden = groupItems.length > 0 && groupItems.every((item) => item.hidden);
    });
    root.querySelectorAll('[data-filter-empty]').forEach((empty) => { empty.hidden = visible > 0; });
    dispatch(root, 'basecoat:filter', { query: state.query, facet: state.facet, visible, total: items.length }, false);
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
  const setQuery = (input) => {
    const root = $(input.getAttribute('data-filter-input'));
    if (!root) return;
    const state = filterState.get(root) || { query: '', facet: '' };
    state.query = input.value;
    filterState.set(root, state);
    const result = applyFilters(root);
    clearTimeout(filterAnnounceTimer);
    filterAnnounceTimer = setTimeout(() => announce(t(input, 'results', result)), 500);
  };

  const setFacet = (control, value) => {
    const target = control.getAttribute('data-filter-facet');
    const root = $(target);
    if (!root) return false;
    const state = filterState.get(root) || { query: '', facet: '' };
    state.facet = value;
    filterState.set(root, state);
    if (control.tagName !== 'SELECT') {
      document.querySelectorAll('[data-filter-facet]').forEach((other) => {
        if (other.getAttribute('data-filter-facet') !== target || other.tagName === 'SELECT' || other.getAttribute('role') === 'tab') return;
        const active = other === control;
        if (other.matches('a[href]')) {
          if (active) other.setAttribute('aria-current', 'true'); else other.removeAttribute('aria-current');
        } else {
          other.setAttribute('aria-pressed', String(active));
        }
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
      if (other.getAttribute('data-view-target') !== control.getAttribute('data-view-target') || other.getAttribute('role') === 'tab') return;
      const active = other.getAttribute('data-view-switch') === view;
      if (other.matches('a[href]')) {
        if (active) other.setAttribute('aria-current', 'true'); else other.removeAttribute('aria-current');
      } else {
        other.setAttribute('aria-pressed', String(active));
      }
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
  const toggleItemOf = (control) => control.closest('[data-toggle-item]');

  const readToggle = (item, name, attr) => (attr === 'data-state' ? item.getAttribute(attr) === name : item.hasAttribute(attr) && item.getAttribute(attr) !== 'false');

  const writeToggle = (item, control, name, on) => {
    const attr = control.getAttribute('data-toggle-attr') || 'data-state';
    const off = control.getAttribute('data-toggle-off');
    if (on) item.setAttribute(attr, attr === 'data-state' ? name : 'true');
    else if (off !== null) item.setAttribute(attr, off);
    else item.removeAttribute(attr);
    const classes = (control.getAttribute('data-toggle-class') || '').split(/\s+/).filter(Boolean);
    classes.forEach((c) => item.classList.toggle(c, on));
    item.querySelectorAll(`[data-toggle="${CSS.escape(name)}"]`).forEach((other) => {
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

  const setToggle = (item, control, name, on) => {
    const attr = control.getAttribute('data-toggle-attr') || 'data-state';
    const before = item.getAttribute(attr);
    const wasOn = readToggle(item, name, attr);
    if (wasOn === on) {
      writeToggle(item, control, name, on);
      return;
    }
    writeToggle(item, control, name, on);
    const restore = () => {
      writeToggle(item, control, name, wasOn);
      if (before === null) item.removeAttribute(attr); else item.setAttribute(attr, before);
      applyAllFilters();
      queueCounts();
    };
    const detail = { item, id: idOf(item), name, on };
    if (!dispatch(item, 'basecoat:toggle', detail)) {
      restore();
      return;
    }
    applyAllFilters();
    queueCounts();
    const url = endpointOf(item);
    if (url) {
      post(url, { action: 'toggle', id: detail.id, name, on }, item).then((ok) => {
        if (ok) return;
        restore();
        announce(t(item, 'failed'));
      });
    }
  };

  const removeItem = (button) => {
    const item = button.closest('[data-toggle-item],[data-filter-item],[data-remove-target]');
    if (!item) return;
    const action = button.getAttribute('data-remove-item') || 'remove';
    const detail = { item, id: idOf(item), action };
    if (!dispatch(item, 'basecoat:remove', detail)) return;
    const parent = item.parentElement;
    const next = item.nextElementSibling;
    const label = labelOf(item);
    const focusNext = next || item.previousElementSibling;
    item.remove();
    if (focusNext) {
      const target = focusNext.matches('a[href],button,input,[tabindex]') ? focusNext : focusNext.querySelector('a[href],button,input,[tabindex]');
      if (target) target.focus();
    }
    announce(t(button, 'removed', { label }));
    applyAllFilters();
    queueCounts();
    const url = endpointOf(button);
    if (url) {
      post(url, { action, id: detail.id }, parent).then((ok) => {
        if (ok) return;
        parent.insertBefore(item, next && next.parentElement === parent ? next : null);
        announce(t(parent, 'failed'));
        applyAllFilters();
        queueCounts();
      });
    }
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
    init(detail);
    if (window.BasecoatDnd && typeof window.BasecoatDnd.init === 'function') window.BasecoatDnd.init(detail);
    if (master.hasAttribute('data-master-focus')) {
      if (!detail.hasAttribute('tabindex')) detail.setAttribute('tabindex', '-1');
      detail.focus();
    }
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
    const url = endpointOf(form);
    if (url) {
      post(url, { action: 'send', text }, form).then((ok) => {
        if (ok) return;
        if (made.node) made.node.setAttribute('data-state', 'failed');
        announce(t(form, 'send-failed'));
      });
    }
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

  const validatePanel = (panel) => {
    const fields = Array.from(panel.querySelectorAll('input,select,textarea')).filter((f) => !f.disabled && typeof f.checkValidity === 'function');
    const invalid = fields.find((f) => !f.checkValidity());
    if (!invalid) return true;
    if (typeof invalid.reportValidity === 'function') invalid.reportValidity();
    invalid.focus();
    return false;
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

  // ---------------------------------------------------------------- delegated listeners
  document.addEventListener('input', (event) => {
    const target = event.target;
    if (target instanceof Element && target.matches('[data-filter-input]')) setQuery(target);
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
    const toggleAll = target.closest('[data-toggle-all]');
    if (toggleAll) {
      event.preventDefault();
      const scope = $(toggleAll.getAttribute('data-toggle-scope')) || document;
      const name = toggleAll.getAttribute('data-toggle-all');
      scope.querySelectorAll('[data-toggle-item]').forEach((item) => {
        const control = item.querySelector(`[data-toggle="${CSS.escape(name)}"]`) || toggleAll;
        setToggle(item, control, name, true);
      });
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
      if (!target) return;
      const state = filterState.get(target) || { query: '', facet: '' };
      state.query = input.value;
      filterState.set(target, state);
    });
    within(root, '[data-filter-facet]').forEach((control) => {
      const target = $(control.getAttribute('data-filter-facet'));
      if (!target) return;
      const state = filterState.get(target) || { query: '', facet: '' };
      if (control.tagName === 'SELECT') state.facet = control.value;
      else if (control.getAttribute('aria-pressed') === 'true' || control.getAttribute('aria-selected') === 'true' || control.getAttribute('aria-current') === 'true') state.facet = control.getAttribute('data-filter-value') || '';
      filterState.set(target, state);
    });
    applyAllFilters();
    scopesOf(null).forEach((scope) => updateSelection(scope, false));
    within(root, '[data-stepper]').forEach((stepper) => {
      if (stepper.hasAttribute('data-stepper-ready')) return;
      stepper.setAttribute('data-stepper-ready', '');
      const start = stepper.getAttribute('data-stepper-start');
      renderStep(stepper, start !== null ? clamp(parseInt(start, 10) || 0, 0, Math.max(panelsOf(stepper).length - 1, 0)) : currentStep(stepper), false);
    });
    within(root, 'form[data-composer]').forEach((form) => {
      const log = $(form.getAttribute('data-composer'));
      if (log && !log.hasAttribute('aria-live') && log.getAttribute('role') !== 'log') log.setAttribute('aria-live', 'polite');
    });
    refreshCounts();
  };

  const boot = () => init(document);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  document.addEventListener('htmx:load', (event) => init(event.target));
  document.addEventListener('htmx:afterSwap', (event) => init(event.target));
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
