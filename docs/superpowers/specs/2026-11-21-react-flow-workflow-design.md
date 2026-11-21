# Workflow editor on React Flow — design

Date: 2026-11-21
Status: approved (diagram reviewed with the user; "si, además necesito que lo documentes")
Supersedes: the sequential-workflow-designer parts of `2026-11-20-workflow-screen-design.md`.

## Intent

Replace sequential-workflow-designer in the `workflow` bake screen with React Flow (`@xyflow/react`,
MIT): a free node canvas with keyboard support, minimap and zoom controls. Apps that use the plugin
must not write or build any React: they pass the flow as PHP arrays and get a working editor.
Everything must be documented (README, catalog, upgrade notes).

## Decisions taken with the user

- Library: `@xyflow/react` (React Flow), not Svelte Flow nor `@xyflow/system` alone.
- The plugin ships a **precompiled bundle** (React + React Flow + our glue) in `webroot/`; npm is only
  needed by plugin developers. No CDN, no import map.
- Node positions come **in the data** (`position: {x, y}`); no auto-layout library.
- Breaking change accepted for the `workflow` screen (re-bake), explained in the upgrade notes.
- Documentation: README section, scripts reference, catalog page, upgrade notes, third-party notices.

## Goals

1. `FlowHelper` (new, full helper treatment: tests, catalog example, README, translations):
   `$this->Flow->editor(array $flow, array $options = [])` renders the editor container:
   - `<div data-flow-editor>` with a `script[type=application/json][data-flow-data]` (nodes, edges),
     a `script[type=application/json][data-flow-config]` (palette, field labels, i18n), a `<template
     data-flow-icons>` with the IconHelper SVG of every icon used (the bundle clones these DOM nodes;
     no `innerHTML`), an optional hidden input (`name` option) kept in sync, a canvas placeholder and a
     **no-JS fallback**: the steps as an ordered list, each with its outgoing connections.
   - Options: `name` (hidden input name, default `flow`, `false` for none), `palette`, `fields`,
     `readonly`, `label` (accessible name), `class`, `attributes`, `templates`.
   - JSON with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
     JSON_INVALID_UTF8_SUBSTITUTE`.
   - Validation: unknown node `type` (not trigger|action|condition), duplicate ids, edges pointing to
     unknown nodes or outputs, or a missing `position` → `InvalidArgumentException` with a clear message.
2. Data format (PHP arrays):
   ```php
   ['nodes' => [
       ['id' => 'signed-up', 'type' => 'trigger', 'label' => 'Customer signed up', 'icon' => 'user-plus',
        'position' => ['x' => 0, 'y' => 0], 'properties' => ['event' => 'customer.created']],
       ['id' => 'plan', 'type' => 'condition', 'label' => 'Plan is Scale?', 'icon' => 'split',
        'position' => ['x' => 0, 'y' => 240], 'properties' => ['condition' => 'plan = Scale'],
        'outputs' => ['yes' => 'Yes', 'no' => 'No']],
   ],
    'edges' => [
       ['source' => 'signed-up', 'target' => 'plan'],
       ['source' => 'plan', 'output' => 'yes', 'target' => 'manager'],
   ]]
   ```
   Node types: `trigger` (output only), `action` (input + output), `condition` (input + one output
   per `outputs` entry, default `yes`/`no`). Edge `id` optional (derived). Properties are
   `key => string|int`, edited as text fields.
3. Bundle `webroot/js/basecoat-flow.js` (IIFE, minified, legal comments kept) and
   `webroot/css/basecoat-flow.css` (React Flow base styles + our Basecoat-token styles, light/dark).
   Source in `resources/flow/` (`package.json` with exact versions, `package-lock.json`, `build.mjs`,
   `src/`). `npm run build` also writes `webroot/js/basecoat-flow.LICENSES.txt` with the license of
   every package inside the bundle (from the esbuild metafile). CI rebuilds and fails if the committed
   files differ.
4. Editor behaviour (bundle):
   - Mounts every `[data-flow-editor]` on DOMContentLoaded, `htmx:load` and
     `window.BasecoatFlow.init(root)` (not on `window.BasecoatTheme`: basecoat-theme.js skips its setup when that object already exists); unmounts removed containers. Mount failure leaves the
     fallback visible.
   - Custom nodes styled with plugin CSS classes (no runtime Tailwind classes): icon, label, type;
     handles per type; condition outputs labelled.
   - Drag nodes, connect handles, delete nodes/edges (Delete/Backspace), zoom/pan, `Controls`,
     `MiniMap`, `Background`. `colorMode` follows `html.dark` live.
   - **Palette**: a button per template (Enter adds the step below the selected node and connects it);
     also draggable onto the canvas (dropped at the pointer).
   - **Side panel** (Basecoat fields): nothing selected → list of steps (buttons that select and focus a
     node: full keyboard path); node selected → label, properties, a **"Next step" select per output**
     (keyboard way to connect/disconnect) and Delete.
   - Every change updates the hidden input (JSON in the PHP format) and dispatches
     `basecoat:flow-change` (`detail: {flow, action}`); an `aria-live` region announces add / connect /
     delete.
   - `readonly`: no drag, connect, delete, palette or editing.
   - CSP: no inline scripts or `style` attributes in server HTML; React sets styles through the CSSOM.
5. `workflow` bake screen rebuilt on `$this->Flow->editor()`: `data.php` gets `flow` + `palette`
   (replacing `definition` + `toolbox`), same Fernway sample, positions top-down with the branches side
   by side; the "Save" TODO explains wrapping it in a form that posts the `flow` input.
6. Removal: sequential-workflow-designer (CDN links, SRI), `webroot/css/workflow.css`, the workflow
   code of `basecoat-theme.js` (`initWorkflows`), its README sections and notices row.
7. Docs: README "Flow editor" section (helper, data format, options, events, keyboard, styling, CSP,
   htmx, rebuilding the bundle), "Scripts and options" (`npm --prefix resources/flow ci`, `run build`,
   `run check`), upgrade notes, THIRD-PARTY-NOTICES, catalog example, translations (.pot / es .po).
8. Tests: `FlowHelperTest`, `WorkflowScreenTest` rewritten, pure model functions
   (`resources/flow/src/model.js`) with `node --test`, strict catalog build, browser check (light/dark,
   390px and 1280px, keyboard path, no console/CSP errors).

## Non-goals

Auto-layout, undo/redo, sub-flows, autosave endpoint, React anywhere else in the plugin.

## Anti-goals

Apps needing npm or React; copies of paid templates or React Flow Pro examples (sample data stays
invented); runtime-generated Tailwind classes.

## Approaches considered

| Approach | Trade-off | Decision |
| --- | --- | --- |
| React Flow, precompiled bundle in the plugin | ~390 KB minified / ~120 KB gzipped, on pages with the editor only; ~300 lines of JSX to maintain | **Chosen** |
| React Flow via CDN + import map | Inline import map conflicts with strict CSP | Rejected |
| Svelte Flow compiled | Smaller, less mature | Rejected |
| `@xyflow/system` only | Not meant for standalone use; we would rewrite the editor | Rejected |

## Versions (released ≥ 2 weeks ago)

`@xyflow/react` 12.12.1, `react` / `react-dom` 19.2.9, `esbuild` 0.28.2 (exact, lockfile committed).
