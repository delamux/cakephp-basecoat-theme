/*
 * BasecoatTheme drag and drop (webroot/js/basecoat-dnd.js).
 *
 * Load it after the page content with the CSP nonce, e.g. in a template:
 *   $this->Html->script('BasecoatTheme.basecoat-dnd', ['block' => true, 'defer' => true]);
 *   $this->Html->css('BasecoatTheme.interactivity', ['block' => true]);   // drag states (optional)
 * Vanilla, no dependencies, no inline styles, no innerHTML: the real node is moved between drop targets
 * (lists) or its grid placement classes are swapped (grids); nothing floats under the pointer.
 *
 * LIST MODE (sortable lists, kanban columns, day cells)
 *   [data-dnd-container]          drop target; its [data-dnd-item] descendants are the sortable items
 *     data-dnd-group="name"       containers sharing a group accept each other's items
 *     data-dnd-axis="y|x"         list direction (default y): arrows along it reorder, the other arrows
 *                                 move to the previous/next container of the group (document order)
 *     data-dnd-id / id            id sent in events and POSTs;  data-dnd-label: name announced
 *   [data-dnd-item]               draggable (put it on the element that must move, e.g. the <li>)
 *     data-dnd-id / id, data-dnd-label (default: its text)
 *     [data-dnd-handle]           optional descendant: only the handle starts a drag and takes focus
 *
 * GRID MODE (bookings, schedules: items placed in a CSS grid by classes)
 *   [data-dnd-grid]               grid container (data-dnd-id, data-dnd-label as above)
 *     data-dnd-classes='{"row": ["row-start-2", …], "col": ["col-start-2", …], "span": {"1": "row-span-1", …}}'
 *                                 class per 0-based row / col index and per span (JSON array or object)
 *     data-dnd-rows / data-dnd-cols  bounds (default: size of the "row" / "col" maps)
 *     data-dnd-resize-axis="row|col" what the span extends (default row: duration downwards)
 *     data-dnd-span-max           largest span (default: up to the grid edge)
 *   [data-dnd-slot] data-dnd-row="r" data-dnd-col="c"   empty cells, pointer drop targets (data-dnd-label
 *                                 names the slot in announcements, e.g. "Monday 09:00, Room A")
 *   [data-dnd-item] data-dnd-row data-dnd-col data-dnd-span   current placement (0-based, span >= 1)
 *     [data-dnd-resize]           optional descendant handle: drag to change the span
 *   Overlapping items are allowed: they get [data-dnd-collision] and the overlap is announced.
 *
 * KEYBOARD: focus an item (or its handle; items get tabindex="0" when needed) -> Space or Enter picks it
 *   up (Space only when the item is a link) -> arrows move (lists: Home/End too) -> Space/Enter drops,
 *   Escape (or leaving the item with Tab) cancels. Grids: Shift + arrows along the resize axis change
 *   the span, also without picking the item up. Focus stays on the item.
 * POINTER: mouse/pen drag after 4px; touch after a 250ms press (so the page still scrolls).
 * ANNOUNCER: one shared [data-basecoat-announcer] aria-live="polite" region (created when missing).
 *   Messages are translatable: data-dnd-i18n-<key>="…" on the container or any ancestor, or a JSON block
 *   <script type="application/json" data-dnd-i18n>{"<key>": "…"}</script>. Keys and placeholders: see
 *   MESSAGES below ({label}, {container}, {position}, {total}, {slot}, {row}, {col}, {span}, {others}).
 *
 * EVENTS (bubbling, cancelable, dispatched on the item after a drop; preventDefault() reverts):
 *   basecoat:move   list: {mode: 'list', item, id, from, fromId, to, toId, index, fromIndex}
 *                   grid: {mode: 'grid', item, id, grid, row, col, span, from: {row, col, span}, collisions}
 *   basecoat:resize grid: same detail as a grid move (span changed)
 *   basecoat:move-error after a failed POST (detail of the move + {status}); the change was reverted.
 * ENDPOINT: data-endpoint="/url" on the container or an ancestor: the change is POSTed as JSON
 *   (list: {action: 'move', id, from, to, index, fromIndex}; grid: {action: 'move'|'resize', id, grid,
 *   row, col, span}) with X-CSRF-Token (data-csrf-token on an ancestor, <meta name="csrfToken">, or the
 *   page's input[name=_csrfToken]). Non-2xx or network error -> revert + "failed" announcement.
 * STATE ATTRIBUTES (style them; webroot/css/interactivity.css does): [data-dnd-dragging] on the item,
 *   [data-dnd-over] on the container under it, [data-dnd-busy] while a POST is pending,
 *   [data-dnd-collision], html[data-dnd-active].
 * API: window.BasecoatDnd.init(root) (auto: DOMContentLoaded, htmx:load, htmx:afterSwap; idempotent),
 *   .cancel(), .announce(text). Pure helpers are exported for node --test (tests/js/).
 */
(() => {
  // ---------------------------------------------------------------- pure helpers (unit tested)
  const clamp = (n, min, max) => Math.min(Math.max(n, min), max);

  /** Insertion index for a pointer coordinate, given the midpoints (ascending) of the other items. */
  const insertionIndex = (midpoints, coord) => {
    let i = 0;
    while (i < midpoints.length && coord > midpoints[i]) i += 1;
    return i;
  };

  /** Fills {placeholders}; unknown ones stay as they are. */
  const format = (template, vars) => String(template).replace(/\{(\w+)\}/g, (m, key) => (
    vars && vars[key] !== undefined && vars[key] !== null ? String(vars[key]) : m
  ));

  const splitClasses = (value) => (value === undefined || value === null ? [] : String(value).split(/\s+/).filter(Boolean));

  /** Every class of a map (array or object of class strings). */
  const mapClasses = (map) => {
    const all = [];
    Object.keys(map || {}).forEach((key) => splitClasses(map[key]).forEach((c) => { if (!all.includes(c)) all.push(c); }));
    return all;
  };

  /** Class list after swapping the class of `map` to the one for `key` (others of the map removed). */
  const swapClasses = (current, map, key) => {
    const owned = mapClasses(map);
    const next = current.filter((c) => !owned.includes(c));
    splitClasses(map ? map[key] : undefined).forEach((c) => { if (!next.includes(c)) next.push(c); });
    return next;
  };

  const mapSize = (map) => (map ? Object.keys(map).length : 0);

  /** Clamps a grid placement {row, col, span} to the grid {rows, cols, axis, spanMax}. */
  const clampPlacement = (p, grid) => {
    const axis = grid.axis === 'col' ? 'col' : 'row';
    const along = axis === 'row' ? grid.rows : grid.cols;
    const max = Math.max(1, Math.min(grid.spanMax || along, along));
    const span = clamp(Math.round(p.span || 1), 1, max);
    const row = clamp(p.row, 0, Math.max(0, grid.rows - (axis === 'row' ? span : 1)));
    const col = clamp(p.col, 0, Math.max(0, grid.cols - (axis === 'col' ? span : 1)));
    return { row, col, span };
  };

  /** Whether two placements overlap (span along `axis`). */
  const overlaps = (a, b, axis = 'row') => {
    if (axis === 'col') return a.row === b.row && a.col < b.col + b.span && b.col < a.col + a.span;
    return a.col === b.col && a.row < b.row + b.span && b.row < a.row + a.span;
  };

  /** Indexes of `others` overlapping `placement`. */
  const collisions = (placement, others, axis = 'row') => others.reduce((found, other, i) => {
    if (overlaps(placement, other, axis)) found.push(i);
    return found;
  }, []);

  const pure = { clamp, insertionIndex, format, splitClasses, mapClasses, swapClasses, mapSize, clampPlacement, overlaps, collisions };
  if (typeof module !== 'undefined' && module.exports) module.exports = pure;
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  // Loaded twice? Keep the first instance (delegated listeners registered once).
  if (window.BasecoatDnd) return;

  // ---------------------------------------------------------------- messages
  const MESSAGES = {
    instructions: 'Press Space or Enter to pick up. Use the arrow keys to move, Space or Enter to drop, Escape to cancel.',
    'grid-instructions': 'Press Space or Enter to pick up. Use the arrow keys to move, Space or Enter to drop, Escape to cancel. Shift and arrow keys change the length.',
    'picked-up': 'Picked up {label}. {container}, position {position} of {total}.',
    moved: '{container}, position {position} of {total}.',
    dropped: 'Dropped {label}. {container}, position {position} of {total}.',
    cancelled: 'Cancelled. {label} is back in {container}, position {position} of {total}.',
    failed: 'Could not save the change. {label} was moved back.',
    'grid-picked-up': 'Picked up {label}. {slot}.',
    'grid-moved': '{slot}.',
    'grid-dropped': 'Dropped {label}. {slot}.',
    'grid-cancelled': 'Cancelled. {label} is back at {slot}.',
    resized: '{label}: {span} slots.',
    collision: 'Overlaps {others}.',
    slot: 'Row {row}, column {col}',
    list: 'List',
  };

  const jsonCache = new Map();
  const parseJson = (text) => {
    if (!text) return null;
    if (jsonCache.has(text)) return jsonCache.get(text);
    let value = null;
    try { value = JSON.parse(text); } catch (e) { console.error('[BasecoatDnd] Invalid JSON', e); }
    jsonCache.set(text, value);
    return value;
  };

  const t = (context, key, vars) => {
    for (let el = context; el && el.getAttribute; el = el.parentElement) {
      const value = el.getAttribute(`data-dnd-i18n-${key}`);
      if (value !== null) return format(value, vars || {});
    }
    const script = document.querySelector('script[type="application/json"][data-dnd-i18n]');
    const block = script ? parseJson(script.textContent) : null;
    if (block && typeof block[key] === 'string') return format(block[key], vars || {});
    return format(MESSAGES[key] || key, vars || {});
  };

  let announceTimer = null;
  const announce = (text) => {
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
    // A short delay so the same text is announced again.
    announceTimer = setTimeout(() => { region.textContent = text; }, 50);
  };

  // ---------------------------------------------------------------- DOM helpers
  const CONTAINER = '[data-dnd-container],[data-dnd-grid]';
  const INTERACTIVE = 'a[href],button,input,select,textarea,label,summary,[contenteditable=""],[contenteditable="true"]';

  const containerOf = (item) => item.parentElement && item.parentElement.closest(CONTAINER);
  const itemsOf = (container) => Array.from(container.querySelectorAll('[data-dnd-item]'))
    .filter((item) => containerOf(item) === container);
  const isGrid = (container) => container.hasAttribute('data-dnd-grid');
  const idOf = (el) => (el ? el.getAttribute('data-dnd-id') || el.id || null : null);
  const labelOf = (el) => {
    const explicit = el.getAttribute('data-dnd-label') || el.getAttribute('aria-label');
    if (explicit) return explicit;
    const text = (el.textContent || '').replace(/\s+/g, ' ').trim();
    return text.length > 60 ? `${text.slice(0, 57)}…` : text;
  };
  const containerLabel = (container) => {
    const by = container.getAttribute('aria-labelledby');
    const labelled = by && document.getElementById(by.split(/\s+/)[0]);
    return container.getAttribute('data-dnd-label') || container.getAttribute('aria-label')
      || (labelled ? labelled.textContent.trim() : '') || t(container, 'list');
  };
  const handleOf = (item) => {
    const handle = item.querySelector('[data-dnd-handle]');
    return handle && handle.closest('[data-dnd-item]') === item ? handle : null;
  };
  const focusTarget = (item) => handleOf(item) || item;
  const reducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const reveal = (el) => {
    if (typeof el.scrollIntoView === 'function') el.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: reducedMotion() ? 'auto' : 'smooth' });
  };
  const refocus = (item) => {
    const target = focusTarget(item);
    if (document.activeElement !== target) target.focus({ preventScroll: true });
    reveal(item);
  };
  const compatible = (source, target) => {
    if (source === target) return true;
    if (isGrid(source) || isGrid(target)) return false;
    const group = source.getAttribute('data-dnd-group');
    return !!group && group === target.getAttribute('data-dnd-group');
  };
  const groupContainers = (container) => {
    const group = container.getAttribute('data-dnd-group');
    if (!group || isGrid(container)) return [container];
    return Array.from(document.querySelectorAll('[data-dnd-container]'))
      .filter((c) => c.getAttribute('data-dnd-group') === group && !c.hasAttribute('data-dnd-disabled'));
  };

  /** Puts `item` at `index` among the items of `container` (the item itself excluded). */
  const placeAt = (container, item, index) => {
    const others = itemsOf(container).filter((i) => i !== item);
    const ref = others[index];
    if (ref) ref.parentElement.insertBefore(item, ref);
    else if (others.length) others[others.length - 1].after(item);
    else container.appendChild(item);
  };

  const listPosition = (item) => {
    const container = containerOf(item);
    const items = itemsOf(container);
    return { container, index: items.indexOf(item), total: items.length };
  };
  const listVars = (item) => {
    const pos = listPosition(item);
    return { label: labelOf(item), container: containerLabel(pos.container), position: pos.index + 1, total: pos.total };
  };

  // ---- grid
  const gridConfig = (grid) => {
    const classes = parseJson(grid.getAttribute('data-dnd-classes')) || {};
    const rows = parseInt(grid.getAttribute('data-dnd-rows'), 10) || mapSize(classes.row) || 1;
    const cols = parseInt(grid.getAttribute('data-dnd-cols'), 10) || mapSize(classes.col) || 1;
    return {
      classes,
      rows,
      cols,
      axis: grid.getAttribute('data-dnd-resize-axis') === 'col' ? 'col' : 'row',
      spanMax: parseInt(grid.getAttribute('data-dnd-span-max'), 10) || 0,
    };
  };
  const placementOf = (item) => ({
    row: parseInt(item.getAttribute('data-dnd-row'), 10) || 0,
    col: parseInt(item.getAttribute('data-dnd-col'), 10) || 0,
    span: parseInt(item.getAttribute('data-dnd-span'), 10) || 1,
  });
  const applyPlacement = (item, grid, placement) => {
    const config = gridConfig(grid);
    const p = clampPlacement(placement, config);
    let list = Array.from(item.classList);
    list = swapClasses(list, config.classes.row, p.row);
    list = swapClasses(list, config.classes.col, p.col);
    list = swapClasses(list, config.classes.span, p.span);
    item.className = list.join(' ');
    item.setAttribute('data-dnd-row', String(p.row));
    item.setAttribute('data-dnd-col', String(p.col));
    item.setAttribute('data-dnd-span', String(p.span));
    return p;
  };
  const slotLabel = (grid, p) => {
    const slot = grid.querySelector(`[data-dnd-slot][data-dnd-row="${p.row}"][data-dnd-col="${p.col}"]`);
    return (slot && slot.getAttribute('data-dnd-label')) || t(grid, 'slot', { row: p.row + 1, col: p.col + 1 });
  };
  /** Flags overlapping items; returns the items overlapping `item`. */
  const markCollisions = (grid, item) => {
    const axis = gridConfig(grid).axis;
    const items = itemsOf(grid);
    const placements = items.map(placementOf);
    let hits = [];
    items.forEach((current, i) => {
      const others = placements.filter((_, j) => j !== i);
      const otherItems = items.filter((_, j) => j !== i);
      const found = collisions(placements[i], others, axis).map((j) => otherItems[j]);
      current.toggleAttribute('data-dnd-collision', found.length > 0);
      if (current === item) hits = found;
    });
    return hits;
  };
  const gridVars = (item, grid) => {
    const p = placementOf(item);
    return { label: labelOf(item), slot: slotLabel(grid, p), row: p.row + 1, col: p.col + 1, span: p.span };
  };
  const collisionText = (grid, hits) => (hits.length ? ` ${t(grid, 'collision', { others: hits.map(labelOf).join(', ') })}` : '');

  // ---------------------------------------------------------------- endpoint
  const csrfToken = (el) => {
    const holder = el && el.closest('[data-csrf-token]');
    if (holder) return holder.getAttribute('data-csrf-token');
    const meta = document.querySelector('meta[name="csrfToken"],meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="_csrfToken"]');
    return input ? input.value : '';
  };
  const post = (url, payload, el) => {
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const token = csrfToken(el);
    if (token) headers['X-CSRF-Token'] = token;
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload) })
      .then((response) => ({ ok: response.ok, status: response.status }), () => ({ ok: false, status: 0 }));
  };

  // ---------------------------------------------------------------- drag state
  // drag: {item, source, grid, kind: 'pointer'|'keyboard', resize, before, offset, pointerId, ...}
  let drag = null;
  let pending = null;
  let suppressClick = false;

  const snapshot = (item) => {
    const container = containerOf(item);
    return isGrid(container) ? { container, placement: placementOf(item) } : { container, index: itemsOf(container).indexOf(item) };
  };

  const setOver = (container) => {
    document.querySelectorAll('[data-dnd-over]').forEach((el) => { if (el !== container) el.removeAttribute('data-dnd-over'); });
    if (container) container.setAttribute('data-dnd-over', '');
  };

  const begin = (item, kind, extra) => {
    const container = containerOf(item);
    drag = Object.assign({ item, kind, source: container, before: snapshot(item) }, extra || {});
    item.setAttribute('data-dnd-dragging', '');
    document.documentElement.setAttribute('data-dnd-active', '');
    setOver(container);
    if (isGrid(container)) {
      const vars = gridVars(item, container);
      announce(t(container, 'grid-picked-up', vars));
    } else {
      announce(t(container, 'picked-up', listVars(item)));
    }
  };

  const finish = () => {
    if (!drag) return;
    drag.item.removeAttribute('data-dnd-dragging');
    document.documentElement.removeAttribute('data-dnd-active');
    setOver(null);
    drag = null;
  };

  const revert = (item, before) => {
    if (isGrid(before.container)) {
      applyPlacement(item, before.container, before.placement);
      markCollisions(before.container, item);
    } else {
      placeAt(before.container, item, before.index);
    }
  };

  const cancel = () => {
    if (!drag) return;
    const { item, before } = drag;
    const hadFocus = item.contains(document.activeElement);
    revert(item, before);
    if (hadFocus) refocus(item);
    finish();
    if (isGrid(before.container)) announce(t(before.container, 'grid-cancelled', gridVars(item, before.container)));
    else announce(t(before.container, 'cancelled', listVars(item)));
  };

  const dispatch = (item, name, detail) => item.dispatchEvent(new CustomEvent(name, { bubbles: true, cancelable: true, detail }));

  const commit = (item, before) => {
    const container = containerOf(item);
    const endpointHolder = container.closest('[data-endpoint]');
    let detail;
    let payload;
    let names;
    if (isGrid(container)) {
      const now = placementOf(item);
      const moved = now.row !== before.placement.row || now.col !== before.placement.col;
      const resized = now.span !== before.placement.span;
      if (!moved && !resized) return 'unchanged';
      const hits = markCollisions(container, item);
      detail = { mode: 'grid', item, id: idOf(item), grid: container, row: now.row, col: now.col, span: now.span, from: before.placement, collisions: hits };
      payload = { action: moved ? 'move' : 'resize', id: idOf(item), grid: idOf(container), row: now.row, col: now.col, span: now.span };
      names = [moved ? 'basecoat:move' : null, resized ? 'basecoat:resize' : null].filter(Boolean);
    } else {
      const index = itemsOf(container).indexOf(item);
      if (container === before.container && index === before.index) return 'unchanged';
      detail = { mode: 'list', item, id: idOf(item), from: before.container, fromId: idOf(before.container), to: container, toId: idOf(container), index, fromIndex: before.index };
      payload = { action: 'move', id: detail.id, from: detail.fromId, to: detail.toId, index, fromIndex: before.index };
      names = ['basecoat:move'];
    }
    const allowed = names.every((name) => dispatch(item, name, detail));
    if (!allowed) {
      revert(item, before);
      return 'reverted';
    }
    if (endpointHolder) {
      const url = endpointHolder.getAttribute('data-endpoint');
      item.setAttribute('data-dnd-busy', '');
      post(url, payload, container).then((result) => {
        item.removeAttribute('data-dnd-busy');
        if (result.ok) return;
        revert(item, before);
        announce(t(container, 'failed', { label: labelOf(item) }));
        item.dispatchEvent(new CustomEvent('basecoat:move-error', { bubbles: true, detail: Object.assign({}, detail, { status: result.status }) }));
      });
    }
    return 'committed';
  };

  const drop = () => {
    if (!drag) return;
    const { item, before } = drag;
    finish();
    const result = commit(item, before);
    const container = containerOf(item);
    if (result === 'reverted') {
      if (isGrid(container)) announce(t(container, 'grid-cancelled', gridVars(item, container)));
      else announce(t(container, 'cancelled', listVars(item)));
    } else if (isGrid(container)) {
      const hits = markCollisions(container, item);
      announce(t(container, 'grid-dropped', gridVars(item, container)) + collisionText(container, hits));
    } else {
      announce(t(container, 'dropped', listVars(item)));
    }
  };

  // ---------------------------------------------------------------- pointer
  const pointFrom = (x, y) => (typeof document.elementsFromPoint === 'function' ? document.elementsFromPoint(x, y) : []);

  const listTargetAt = (x, y) => {
    for (const el of pointFrom(x, y)) {
      if (drag.item.contains(el)) continue;
      const container = el.closest('[data-dnd-container]');
      if (container && !container.hasAttribute('data-dnd-disabled') && compatible(drag.source, container)) return container;
    }
    return null;
  };

  const slotAt = (grid, x, y) => {
    for (const el of pointFrom(x, y)) {
      const slot = el.closest('[data-dnd-slot]');
      if (slot && grid.contains(slot)) return { row: parseInt(slot.getAttribute('data-dnd-row'), 10) || 0, col: parseInt(slot.getAttribute('data-dnd-col'), 10) || 0 };
    }
    // In a grid gap (or over a padding): the nearest slot, as long as the pointer is inside the grid.
    const box = grid.getBoundingClientRect();
    if (x < box.left || x > box.right || y < box.top || y > box.bottom) return null;
    let best = null;
    let bestDistance = Infinity;
    grid.querySelectorAll('[data-dnd-slot]').forEach((slot) => {
      const r = slot.getBoundingClientRect();
      const distance = Math.hypot(Math.max(r.left - x, 0, x - r.right), Math.max(r.top - y, 0, y - r.bottom));
      if (distance < bestDistance) {
        bestDistance = distance;
        best = slot;
      }
    });
    return best ? { row: parseInt(best.getAttribute('data-dnd-row'), 10) || 0, col: parseInt(best.getAttribute('data-dnd-col'), 10) || 0 } : null;
  };

  const pointerUpdate = (x, y) => {
    const { item } = drag;
    if (drag.grid) {
      const slot = slotAt(drag.grid, x, y);
      if (!slot) return;
      const now = placementOf(item);
      const config = gridConfig(drag.grid);
      let next;
      if (drag.resize) {
        const extent = config.axis === 'row' ? slot.row - now.row + 1 : slot.col - now.col + 1;
        next = Object.assign({}, now, { span: extent });
      } else {
        next = { row: slot.row - drag.offset.row, col: slot.col - drag.offset.col, span: now.span };
      }
      const p = clampPlacement(next, config);
      if (p.row !== now.row || p.col !== now.col || p.span !== now.span) {
        applyPlacement(item, drag.grid, p);
        markCollisions(drag.grid, item);
      }
      return;
    }
    const container = listTargetAt(x, y);
    if (!container) return;
    setOver(container);
    const vertical = container.getAttribute('data-dnd-axis') !== 'x';
    const others = itemsOf(container).filter((i) => i !== item);
    const mids = others.map((other) => {
      const r = other.getBoundingClientRect();
      return vertical ? r.top + r.height / 2 : r.left + r.width / 2;
    });
    const index = insertionIndex(mids, vertical ? y : x);
    const current = itemsOf(container).indexOf(item);
    if (containerOf(item) !== container || current !== index) placeAt(container, item, index);
  };

  const startPointerDrag = () => {
    const p = pending;
    pending = null;
    const container = containerOf(p.item);
    const extra = { pointerId: p.pointerId, resize: p.resize };
    if (isGrid(container)) {
      extra.grid = container;
      const slot = slotAt(container, p.x, p.y);
      const now = placementOf(p.item);
      extra.offset = slot && !p.resize ? { row: slot.row - now.row, col: slot.col - now.col } : { row: 0, col: 0 };
    }
    begin(p.item, 'pointer', extra);
  };

  const clearPending = () => {
    if (pending && pending.timer) clearTimeout(pending.timer);
    pending = null;
  };

  document.addEventListener('pointerdown', (event) => {
    if (drag || (event.pointerType === 'mouse' && event.button !== 0) || !(event.target instanceof Element)) return;
    const item = event.target.closest('[data-dnd-item]');
    if (!item || item.hasAttribute('data-dnd-busy')) return;
    const container = containerOf(item);
    if (!container || container.hasAttribute('data-dnd-disabled')) return;
    const resizeHandle = isGrid(container) ? event.target.closest('[data-dnd-resize]') : null;
    const handle = handleOf(item);
    if (!resizeHandle) {
      if (handle && !handle.contains(event.target)) return;
      const interactive = event.target.closest(INTERACTIVE);
      if (!handle && interactive && interactive !== item && item.contains(interactive)) return;
    }
    pending = { item, pointerId: event.pointerId, pointerType: event.pointerType, x: event.clientX, y: event.clientY, resize: !!resizeHandle, timer: null };
    if (event.pointerType === 'touch') {
      pending.timer = setTimeout(() => { if (pending && pending.item === item) startPointerDrag(); }, 250);
    }
  });

  document.addEventListener('pointermove', (event) => {
    if (pending && event.pointerId === pending.pointerId) {
      const distance = Math.hypot(event.clientX - pending.x, event.clientY - pending.y);
      if (pending.pointerType === 'touch') {
        if (distance > 8) clearPending(); // the user is scrolling
        return;
      }
      if (distance < 4) return;
      startPointerDrag();
    }
    if (drag && drag.kind === 'pointer' && event.pointerId === drag.pointerId) {
      event.preventDefault();
      pointerUpdate(event.clientX, event.clientY);
    }
  });

  document.addEventListener('pointerup', (event) => {
    if (pending && event.pointerId === pending.pointerId) clearPending();
    if (drag && drag.kind === 'pointer' && event.pointerId === drag.pointerId) {
      suppressClick = true;
      setTimeout(() => { suppressClick = false; }, 0);
      drop();
    }
  });

  document.addEventListener('pointercancel', (event) => {
    if (pending && event.pointerId === pending.pointerId) clearPending();
    if (drag && drag.kind === 'pointer' && event.pointerId === drag.pointerId) cancel();
  });

  // Touch: once a drag started, keep the page from scrolling.
  document.addEventListener('touchmove', (event) => {
    if (drag && drag.kind === 'pointer') event.preventDefault();
  }, { passive: false });

  // No text selection, native link/image drag or click at the end of a pointer drag.
  document.addEventListener('selectstart', (event) => { if (drag || (pending && pending.pointerType !== 'touch')) event.preventDefault(); });
  document.addEventListener('dragstart', (event) => {
    if (event.target instanceof Element && event.target.closest('[data-dnd-item]')) event.preventDefault();
  });
  document.addEventListener('click', (event) => {
    if (!suppressClick) return;
    suppressClick = false;
    event.preventDefault();
    event.stopPropagation();
  }, true);
  document.addEventListener('contextmenu', (event) => { if (pending && pending.pointerType === 'touch') event.preventDefault(); });

  // ---------------------------------------------------------------- keyboard
  const keyboardListMove = (key) => {
    const { item } = drag;
    const container = containerOf(item);
    const vertical = container.getAttribute('data-dnd-axis') !== 'x';
    const along = vertical ? { ArrowUp: -1, ArrowDown: 1 } : { ArrowLeft: -1, ArrowRight: 1 };
    const across = vertical ? { ArrowLeft: -1, ArrowRight: 1 } : { ArrowUp: -1, ArrowDown: 1 };
    const items = itemsOf(container);
    const index = items.indexOf(item);
    if (key === 'Home' || key === 'End' || along[key]) {
      const target = key === 'Home' ? 0 : key === 'End' ? items.length - 1 : clamp(index + along[key], 0, items.length - 1);
      if (target === index) return false;
      placeAt(container, item, target);
    } else if (across[key]) {
      const containers = groupContainers(container);
      const next = containers[containers.indexOf(container) + across[key]];
      if (!next) return false;
      placeAt(next, item, clamp(index, 0, itemsOf(next).filter((i) => i !== item).length));
      setOver(next);
    } else {
      return false;
    }
    refocus(item);
    announce(t(containerOf(item), 'moved', listVars(item)));
    return true;
  };

  const keyboardGridMove = (key, shift) => {
    const { item } = drag;
    const grid = containerOf(item);
    const config = gridConfig(grid);
    const now = placementOf(item);
    const next = Object.assign({}, now);
    const resizeKeys = config.axis === 'row' ? { ArrowUp: -1, ArrowDown: 1 } : { ArrowLeft: -1, ArrowRight: 1 };
    if (shift && resizeKeys[key]) next.span += resizeKeys[key];
    else if (key === 'ArrowUp') next.row -= 1;
    else if (key === 'ArrowDown') next.row += 1;
    else if (key === 'ArrowLeft') next.col -= 1;
    else if (key === 'ArrowRight') next.col += 1;
    else return false;
    const p = clampPlacement(next, config);
    if (p.row === now.row && p.col === now.col && p.span === now.span) return true;
    applyPlacement(item, grid, p);
    const hits = markCollisions(grid, item);
    refocus(item);
    const vars = gridVars(item, grid);
    announce((p.span !== now.span ? t(grid, 'resized', vars) : t(grid, 'grid-moved', vars)) + collisionText(grid, hits));
    return true;
  };

  document.addEventListener('keydown', (event) => {
    if (!(event.target instanceof Element) || event.altKey || event.ctrlKey || event.metaKey) return;
    const item = event.target.closest('[data-dnd-item]');
    if (!item || event.target !== focusTarget(item)) return;
    const container = containerOf(item);
    if (!container || container.hasAttribute('data-dnd-disabled')) return;
    const key = event.key;
    const pickKey = key === ' ' || key === 'Spacebar' || (key === 'Enter' && !event.target.matches('a[href]'));

    if (drag && drag.item === item && drag.kind === 'keyboard') {
      if (pickKey) { event.preventDefault(); drop(); return; }
      if (key === 'Escape') { event.preventDefault(); cancel(); return; }
      const handled = isGrid(container) ? keyboardGridMove(key, event.shiftKey) : keyboardListMove(key);
      if (handled || key.startsWith('Arrow')) event.preventDefault();
      return;
    }
    if (drag || item.hasAttribute('data-dnd-busy')) return;
    if (pickKey && !event.shiftKey) {
      event.preventDefault();
      begin(item, 'keyboard');
      return;
    }
    // Grid: Shift + arrows resize without picking up (committed at once).
    if (isGrid(container) && event.shiftKey && key.startsWith('Arrow')) {
      const axis = gridConfig(container).axis;
      if ((axis === 'row' && (key === 'ArrowUp' || key === 'ArrowDown')) || (axis === 'col' && (key === 'ArrowLeft' || key === 'ArrowRight'))) {
        event.preventDefault();
        const before = snapshot(item);
        drag = { item, kind: 'keyboard', source: container, before };
        keyboardGridMove(key, true);
        drag = null;
        commit(item, before);
      }
    }
  });

  // Focus left the picked-up item (Tab, click elsewhere): cancel.
  document.addEventListener('focusout', (event) => {
    if (!drag || drag.kind !== 'keyboard' || !drag.item.contains(event.target)) return;
    setTimeout(() => {
      if (drag && drag.kind === 'keyboard' && !drag.item.contains(document.activeElement)) cancel();
    }, 0);
  });

  // ---------------------------------------------------------------- init / cleanup
  const helpIds = new Map();
  const helpId = (text) => {
    if (helpIds.has(text) && document.getElementById(helpIds.get(text))) return helpIds.get(text);
    const id = `basecoat-dnd-help-${helpIds.size + 1}`;
    const p = document.createElement('p');
    p.id = id;
    p.hidden = true;
    p.textContent = text;
    document.body.appendChild(p);
    helpIds.set(text, id);
    return id;
  };

  const init = (root = document) => {
    if (!root || !root.querySelectorAll) return;
    const containers = Array.from(root.querySelectorAll(CONTAINER));
    if (root instanceof Element && root.matches(CONTAINER)) containers.unshift(root);
    const ancestor = root instanceof Element ? root.closest(CONTAINER) : null;
    if (ancestor && !containers.includes(ancestor)) containers.unshift(ancestor);
    containers.forEach((container) => {
      container.setAttribute('data-dnd-ready', '');
      const grid = isGrid(container);
      const help = helpId(t(container, grid ? 'grid-instructions' : 'instructions'));
      itemsOf(container).forEach((item) => {
        if (item.hasAttribute('data-dnd-ready')) return;
        item.setAttribute('data-dnd-ready', '');
        const target = focusTarget(item);
        if (target.tabIndex < 0 && !target.hasAttribute('tabindex')) target.setAttribute('tabindex', '0');
        if (!target.hasAttribute('aria-describedby')) target.setAttribute('aria-describedby', help);
      });
      if (grid) markCollisions(container, null);
    });
  };

  const boot = () => init(document);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  document.addEventListener('htmx:load', (event) => init(event.target));
  document.addEventListener('htmx:afterSwap', (event) => init(event.target));
  document.addEventListener('htmx:beforeCleanupElement', (event) => {
    if (drag && event.target instanceof Element && event.target.contains(drag.item)) {
      drag.item.removeAttribute('data-dnd-dragging');
      document.documentElement.removeAttribute('data-dnd-active');
      setOver(null);
      drag = null;
    }
    if (pending && event.target instanceof Element && event.target.contains(pending.item)) clearPending();
  });

  window.BasecoatDnd = Object.assign({ init, cancel, announce }, pure);
})();
