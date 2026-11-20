# Workflow automation screen — design

Date: 2026-11-20 · Status: approved by standing instruction; all calls ours. User note: "puede que sea esta librería (sequential-workflow-designer), de todas formas habría que adaptarla al diseño".

## Intent
A `bake screen workflow` (default controller `Workflows`) to view/edit an automation as a sequence of steps, built on **sequential-workflow-designer** (MIT, 0 deps, free edition only — no Pro features), restyled to match the Basecoat theme (tokens, radius, fonts, light/dark).

## Design
- Layout: page header (workflow name, status badge Active/Draft as text, "Save" (inert TODO) and "Run test" (inert TODO)); left: designer canvas (toolbox with groups Triggers / Actions / Logic; control bar; step editor panel on the right rendered as Basecoat form fields — `rootEditorProvider`/`stepEditorProvider` build DOM with Basecoat classes, no innerHTML from data, text via textContent); below/side: run history table (invented runs: time, trigger, status text badge, duration).
- Sample workflow (invented, Fernway): "New customer onboarding": trigger "Customer signed up" → action "Send welcome email" → if/else "Plan is Scale?" (yes: "Assign success manager", no: "Add to nurture list") → "Wait 3 days" → "Create follow-up task".
- Library loading: pinned version from jsDelivr (`sequential-workflow-designer@0.40.4`: `css/designer.css`, `css/designer-light.css`, `css/designer-dark.css`, `dist/index.umd.js`), added through the view `script`/`css` blocks (with the CSP nonce support the theme already has; check ThemeHelper/README "CSP"), never inline.
- Init: generic, CSP-safe code in `webroot/js/basecoat-theme.js`: elements `[data-workflow-designer]` carrying the definition as JSON in a `<script type="application/json">` child (allowed: data block, not executed) → `sequentialWorkflowDesigner.Designer.create(...)` with theme `light`/`dark` following `.dark` on `<html>` (re-create or swap on theme toggle), toolbox/editors as above, `onDefinitionChanged` writes the JSON into a hidden `<input name="definition">` of the (inert) save form. If the library is missing, nothing breaks.
- No-JS / fallback: the same steps rendered server-side as an accessible ordered list (nested lists for branches) shown when JS is off or the designer fails; hidden once the designer mounts.
- Restyle: a small stylesheet `webroot/css/workflow.css` (loaded by the screen) mapping the designer's CSS custom properties/classes to Basecoat tokens (`--background`, `--foreground`, `--muted`, `--border`, `--primary`, `--radius`), both themes.
- Accessibility: the canvas is not fully keyboard-operable (library limitation, verify) → the fallback list + step editor form remain the accessible path; label the canvas region; document the limitation in README.
- Catalog: preview works on GitHub Pages (CDN assets, relative URLs), screen listed with the other 13; README "Screens (bake)" row + helpers + note on the external library and its MIT license.
- Tests: screen test (renders fallback list incl. branches, JSON data block valid and equal to data, assets referenced with pinned version, no inline JS, translatable strings, empty definition), ScreenTranslationsTest coverage, catalog strict build.
