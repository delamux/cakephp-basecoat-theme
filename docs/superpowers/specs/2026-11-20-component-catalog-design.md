# Component catalog (GitHub Pages) + dashboard components — Phase 1 design

Date: 2026-11-20
Status: approved

## Intent

Developers who use (or are evaluating) `delamux/cakephp-basecoat-theme` need one place where they can
find a component, copy the PHP call to its helper, and see exactly what that call renders. Today the
only documentation is the README, with no rendered output.

Phase 1 delivers:

1. A **static component catalog** published on GitHub Pages
   (`https://delamux.github.io/cakephp-basecoat-theme/`), generated from the **real helpers**: each
   example shows its PHP source and the live HTML it produced.
2. A set of **new dashboard-oriented components** (helpers) so a full admin dashboard can be built with
   the plugin, plus a demo dashboard as the catalog's home page.

Later phases (separate designs, same catalog): Mail (phase 2), Todo list (phase 3), Calendar (phase 4).

## Goals

- Every helper (the 24 existing + the new ones) has a catalog page with at least one example.
  *(Our call: the catalog covers all helpers, not only the new ones; once the generator exists each
  extra example is one small file.)*
- The code shown is byte-for-byte the code that ran to produce the preview: they can never drift.
- One command builds the site locally: `composer docs` → `build/gh-pages/`.
- Pages is deployed by GitHub Actions on every push to `main`; nothing generated is committed.
- CI fails if any example throws or emits a PHP warning.
- New components follow the existing helper conventions (`AbstractComponentHelper`, config/classes/
  templates, `__d('basecoat_theme', ...)`), with PHPUnit tests.

## Non-goals

- No PHP on GitHub Pages; no search, versioned docs or custom domain (can come later).
- No screenshots/images (could be added later for the README).
- Mail, Todo list and Calendar screens (phases 2–4).
- No build step for CSS: the catalog uses the same CDN assets as the theme (Basecoat CSS, Tailwind v4
  browser build, Basecoat JS, Chart.js).

## Anti-goals (hard rules)

- **Nothing copied from paid UI kits.** Components are inspired by common dashboard patterns only.
  Implementation is based on open sources (Basecoat UI markup, MIT; shadcn/ui ideas, MIT) and the
  plugin's existing helpers. Do not fetch, read or reproduce any paid kit's markup, CSS, JS, assets,
  block titles, sample people, emails, amounts or copy.
- **No reference to any third-party template site** in code, comments, docs, the catalog, commit
  messages or PR text.
- All component names, demo copy and demo data are ours (invented people, companies, figures).
- The demo dashboard must have its own composition (different blocks and order), not a replica of any
  existing page.

## Constraints

- PHP 8.2+, CakePHP 5.x, Basecoat 1.1.0 markup (`data-variant` / `data-size`).
- The repo is a plugin: there is no `bin/cake`. The generator bootstraps CakePHP like
  `tests/bootstrap.php` does.
- The generator is a repo dev tool, **not shipped in `src/`** (users of the plugin never get it).
- Site is served under `/cakephp-basecoat-theme/`: all internal links and asset paths are relative.
- CSP-friendly output as today (no inline JS in helper output; see `CspCompatibilityTest`).

## Approaches considered

| Approach | Trade-off | Decision |
| --- | --- | --- |
| **A. PHP build script + Pages Action** | Source shown = code executed; no server in CI; needs a small generator; catalog pages don't use the theme layout as-is | **Chosen** |
| B. Gallery route in `tests/test_app`, run `bin/cake server`, `wget --mirror` | Real theme layout, but fragile CI (server, routes, assets) and code must be shown separately → drift | Rejected |
| C. Playwright screenshots | Not inspectable, double images for dark mode, heavy | Rejected (maybe later for README) |

Generator form: user chose a **dev script `bin/build-gh-pages`** (run via `composer docs`) over a CakePHP
Command in `src/Command` (which would ship to every plugin user for no benefit).

## Design

### Repository layout

```
bin/build-gh-pages                 # entry point (php), executable
docs/catalog/
  bootstrap.php                    # CakePHP bootstrap for the generator (mirrors tests/bootstrap.php)
  src/                             # generator classes, namespace BasecoatTheme\Docs\ (autoload-dev)
  templates/                       # catalog page shell (layout, nav, example block)
  examples/<helper-slug>/<NN>-<example-slug>.php
  pages/index.php                  # home: demo dashboard built with the helpers
build/gh-pages/                    # output (gitignored)
.github/workflows/pages.yml        # build + deploy
```

`composer.json`: `"docs": "php bin/build-gh-pages"`, and `autoload-dev` gets
`"BasecoatTheme\\Docs\\": "docs/catalog/src/"`.

### Example file contract

Each example is a PHP file executed inside a CakePHP `View` (so `$this->Button`, `$this->Card`… work),
with a docblock header the generator parses and strips from the displayed code:

```php
<?php
/**
 * @title Metric card with trend
 * @description A KPI value, its change versus the previous period and a sparkline.
 */
echo $this->MetricCard->render('Active users', '1,284', [
    'change' => '+12%',
    'trend' => 'up',
]);
```

- Displayed code = file contents minus the opening `<?php` + header docblock.
- Files are ordered by their `NN-` prefix; the folder name is the helper slug (`metric-card`).
- An example may render several components (composition examples).
- Demo data lives inline in the example (no shared fixtures), so each file stands alone.
- *(Our call)* Examples that need request/ORM context (`Pagination` paging params, `Form` entity,
  `Table` sort links, `Sidebar` active item) put that setup **in the file itself**, above a
  `// ---` marker line. The generator executes the whole file; the PHP tab shows the part after the
  marker, with the setup available in a collapsed "Setup" disclosure. Still byte-for-byte what ran,
  no generator-provided context.

### Generator

1. Bootstrap CakePHP (constants, autoload, `Configure`, `Router` base path), load the `BasecoatTheme`
   plugin, create an `AppView`-like `View` and load all plugin helpers
   (same set as `templates/element/layout/helpers.php`).
2. For each example: render it in an isolated output buffer, with an error handler that turns any
   warning/notice/deprecation into an exception reporting the example path → the build fails.
3. Write one HTML page per helper (`<helper-slug>.html`) and `index.html` (demo dashboard).
4. Copy plugin web assets needed at runtime (`webroot/js/basecoat-theme.js`) into the output.

### Catalog page

Built with the plugin's own helpers (dogfooding): `Sidebar` for navigation (Overview + one entry per
helper, grouped: Layout, Forms, Data display, Feedback, Overlays, Dashboard), `Theme` for the light/dark
toggle and CDN assets.

Per example block:

```
┌ Metric card with trend ─────────────────────────────┐
│ A KPI value, its change versus the previous period… │
│ ┌ Preview ┬ PHP ┬ HTML ┐                            │
│ │  [rendered output, live, theme-aware]           │ │
│ └─────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

- Preview: the raw helper output inline in the page (so Basecoat JS — dropdowns, dialogs, tabs, charts —
  works).
- PHP tab: the example source with syntax highlighting.
- HTML tab: the generated HTML, pretty-printed, with a copy button.
- Each helper page starts with the helper's one-paragraph purpose and how to load it.

### GitHub Actions

- `.github/workflows/pages.yml`: on push to `main` + `workflow_dispatch`; PHP 8.3; `composer install`;
  `composer docs`; `actions/upload-pages-artifact` (path `build/gh-pages`); `actions/deploy-pages`.
  Permissions `pages: write`, `id-token: write`. Repo setting: Pages → Source: GitHub Actions (manual,
  one time).
- `ci.yml`: add a step running `composer docs` (one matrix entry) so broken examples fail PRs.

### New components (all names ours)

| Helper / addition | What it renders | Notes |
| --- | --- | --- |
| `CommandMenu` | Search trigger with keyboard hint that opens a dialog with a filterable list of grouped actions/links | Uses Basecoat `command` markup + `Dialog`, `Kbd`; shortcut handled in `basecoat-theme.js` |
| `WorkspaceSwitcher` | Header/sidebar dropdown to switch between workspaces (logo/avatar, name, plan) + "add" action | Built on `DropdownMenu` + `Avatar` |
| `Button` option `indicator` | Icon button with a dot or count (e.g. notifications) | Change in existing `ButtonHelper` |
| `PageHeader` | Page title, optional description, actions slot (buttons, date range) | Layout-agnostic |
| `DateRangeField` | Input showing a from–to range with a popover of two native date inputs + presets | No JS calendar lib; native `<input type="date">` |
| `MemberList` | List of people: avatar, name, email, and a per-row role select or badge | Role select via `Form` / Basecoat select |
| `MetricCard` | Card with label, value, change (up/down/flat colored), optional sparkline (`Chart`) and footer text | Built on `Card` + `Chart` |
| `Conversation` | Message thread: header (avatar, name, status), sent/received bubbles with optional time, composer (input + send) | Composer is a plain form; no realtime |
| `ChoiceCards` | Radio group rendered as selectable cards with icon, title, optional description | Accessible native radios; works in `Form` |
| `TableToolbar` | Bar above a table: filter input, optional selects, actions (e.g. "View all", column toggle) | Composes with `Table` + list filters |

**Relation to existing list filters (user decision: compatible, no migration).** The plugin already has
`templates/element/list_filters.php` + `BasecoatTheme\Listing\ListFilter` (incl. a `dateRange` control
and the `BasecoatTheme.DateRange` filter with `field[from]` / `field[to]` query params, htmx refresh).
- `TableToolbar` accepts either explicit controls or a `ListFilter` (or its `controls()` output) and
  renders those controls, so it works on baked lists.
- `DateRangeField` emits `name[from]` / `name[to]` inputs, directly compatible with the `DateRange` filter.
- `list_filters.php` and the bake templates are **not** changed in this phase (possible later migration).

Each new helper: `src/View/Helper/<Name>Helper.php`, `tests/TestCase/View/Helper/<Name>HelperTest.php`,
examples in `docs/catalog/examples/<slug>/`, strings via `__d('basecoat_theme', …)`.

### Demo dashboard (catalog home)

Our own fictional SaaS product with our own composition: workspace switcher and command menu in the
header; page header "Overview" with date range and "Download report"; row of 4 metric cards (active
users, revenue, churn, NPS); traffic chart card; orders table with toolbar, status badges and row
menus; support conversation; team member list; plan choice cards. All copy and data invented.

*(Our call)* The demo is a **standalone full page** (`index.html`) rendered with the theme's own
`default` layout (its sidebar + header), dogfooding the theme; it is preview only (no tabs) and links
to "Browse components" (the catalog, `components/…`) and to its source file on GitHub.
`pages/index.php` is a view template, not an example file.

### Parallel work (no collisions)

Work is split so each agent owns disjoint files:

- **Agent "generator"**: `bin/`, `docs/catalog/bootstrap.php`, `docs/catalog/src/`, `docs/catalog/templates/`,
  `composer.json` scripts/autoload, `.gitignore`, workflows.
- **One agent per new component** (or two small ones per agent): only its helper, its test and its
  examples folder.
- **Agent "existing examples"**: `docs/catalog/examples/<existing-helper>/` folders only.
- **Shared files are edited only by the integrator (main session) at the end**:
  `templates/element/layout/helpers.php`, `ButtonHelper.php` (indicator), `webroot/js/basecoat-theme.js`,
  locale `.pot`/`.po`, `README.md`, `docs/catalog/pages/index.php` (demo dashboard).
  Component agents report the strings, JS and helper registrations they need instead of editing them.

## Open items left to the builder

- Syntax highlighting library (CDN highlight.js/Prism vs. server-side) and HTML pretty-printer.
- Exact helper method signatures, following existing helpers' style (`render()` + options arrays).
- Grouping of helpers in the catalog nav.

## Prerequisites

- `feat/list-filters` is merged (PR #2). Phase 1 starts on `feat/component-catalog` from current
  `main`; this spec is committed there.
