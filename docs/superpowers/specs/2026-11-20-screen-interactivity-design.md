# Screen interactivity (JS) — design

Date: 2026-11-20 · Status: approved by standing instruction; all calls ours. User request: "que las cosas sean interactivas como los bookings etc. todos esos flows tienen que funcionar con JS. Mover cajas etc."

## Principles
- **Progressive enhancement**: every screen keeps working without JS exactly as today; JS upgrades it in place. No framework, vanilla ES modules-free scripts (IIFE), no build step.
- **CSP-safe**: files under `webroot/js/`, loaded with the nonce via view blocks; no inline JS/handlers, no `eval`, no `innerHTML` with data (build DOM with `textContent`/`setAttribute`; `<template>` cloning allowed). Styles via classes, not inline `style` (exception: transform during an active pointer drag is not allowed either — use a positioned class + CSS custom property set with `style.setProperty` is still a style attribute; therefore drag previews move by toggling grid placement / classes or by using the View Transitions-free approach of moving the real node between drop targets; if a free-floating ghost is unavoidable, document the CSP `style-src` implication like the workflow screen).
- **Accessible**: every drag interaction has a keyboard equivalent (focus item → Space/Enter to pick up → arrows to move between targets → Space/Enter to drop, Escape to cancel) and an `aria-live="polite"` announcer ("Picked up X. Column In progress, position 2 of 4." / "Dropped." / "Cancelled."). Pointer (mouse + touch + pen) via Pointer Events. Focus is kept on the moved item. Respects `prefers-reduced-motion`.
- **Server hook, client-first**: interactions update the DOM immediately and dispatch bubbling CustomEvents (`basecoat:move`, `basecoat:toggle`, `basecoat:send`, `basecoat:select` …) with a detail payload. If the container has `data-endpoint` (+ CSRF from the page's `csrfToken` meta/hidden field), the change is also POSTed with `fetch` (JSON) and reverted with an announcement on failure. Generated screens ship without endpoints (TODO comments show where to wire them).
- **No regressions**: the catalog previews must work (static site: no endpoints), strict build green, all PHP tests green.

## Pieces
1. `webroot/js/basecoat-dnd.js` — reusable sortable/drag-drop + keyboard + announcer: `data-dnd-container` (drop target, `data-dnd-group` to allow cross-container moves, optional `data-dnd-axis`), `data-dnd-item` (with `data-dnd-label` for announcements), `data-dnd-handle` optional; events `basecoat:move` {item, from, to, index}. Also `data-dnd-grid` mode for 2D slot grids (bookings/calendar): drop targets are cells with `data-dnd-slot` (row/col), items move by updating their grid classes from a map provided as JSON (`data-dnd-classes`), plus optional resize handle (`data-dnd-resize`, keyboard Shift+Arrow) that changes the span class.
2. `webroot/js/basecoat-screens.js` — small behaviours shared by screens: client-side filter/search of lists (`data-filter-input` / `data-filter-item` / `data-filter-text`), tabs/segmented toggles that switch views without reload (`data-view-switch`), select-all + bulk bar (`data-select-all`, `data-bulk-bar` with live count), toggle state (checkbox → item `data-state`, strike, counts update, `data-count-for`), master-detail selection (`data-master` list → `data-detail` pane filled from a `<template>` per item), composer append (`data-composer` → appends a bubble from a `<template>`, clears input, scrolls the log), client stepper (`data-stepper` with `data-step` panels, validates `required` fields before Next).
3. Screen wiring (each screen template adds the data hooks + loads the scripts with `$this->Html->script(... ['block' => 'script', 'defer' => true])`):
   - **kanban**: drag cards between columns and reorder (keyboard too), column counts update, List view stays in sync.
   - **bookings**: drag class blocks to another time slot / resource column, resize duration with a handle (and Shift+Arrow), capacity stays; collisions: allowed but flagged visually + announced.
   - **calendar**: drag event chips to another day; "+N more" expands in place; month navigation stays server-side (GET).
   - **todo**: toggles complete instantly (strike, sr-only, group counts), client-side tabs All/Active/Completed and search, drag to reorder within a group.
   - **notes**: checklist toggles, grid/list switch without reload, client search, drag to reorder notes.
   - **chats**: select a conversation → thread swaps (from per-conversation `<template>`), unread badge clears; composer appends message.
   - **ai-chat**: composer appends the user message + a canned assistant reply (`<template>`), suggestion buttons fill the composer.
   - **mail**: select message → reading pane; Unread/All tabs + search client-side; star/archive/delete update the list (client-only, events).
   - **users / file-manager**: select-all + bulk bar with count; client search/filter; file-manager grid/list switch without reload and a drop zone for uploads (lists chosen files client-side; upload itself is a TODO/event).
   - **notifications**: mark one/all as read, accept/decline update the row (events; POST only if wired).
   - **onboarding**: client stepper (no reload), required-field validation before Next, Back keeps values.
   - **workflow**: already interactive (library) — only make sure it coexists.
4. Catalog: copy `webroot/js/*.js` (incl. new files) into the build; previews interactive.
5. README: "Interactivity" section (principles, data hooks, events payloads, wiring endpoints, keyboard model, CSP notes); Upgrade notes.

## Testing
- PHP: screen tests assert the data hooks/templates/script tags exist.
- JS: no Node test runner exists today; add **Playwright-free** browser verification by agents with Chrome DevTools MCP (pointer drag via `drag`, keyboard via `press_key`), and a tiny dependency-free JS self-test page in the catalog is NOT required. If Node is available, optional `node --test` unit tests for pure functions (index math, class maps) under `tests/js/` without dependencies.
