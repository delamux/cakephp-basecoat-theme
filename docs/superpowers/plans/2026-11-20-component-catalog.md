# Component Catalog + Dashboard Components Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A static GitHub Pages catalog generated from the real helpers (code + live output for every helper), plus 9 new dashboard helpers and a `Button` indicator option.

**Architecture:** A repo-only dev script (`bin/build-gh-pages`, `composer docs`) bootstraps CakePHP, executes example files inside a `View` and writes static HTML to `build/gh-pages/`; a GitHub Action deploys it. New helpers extend `AbstractComponentHelper`. Work runs in parallel agents with **disjoint file ownership**; shared files are touched only in the final integration task.

**Tech Stack:** PHP 8.2+, CakePHP 5, PHPUnit 10/11, Basecoat CSS 1.1.0 + Tailwind v4 browser build (CDN), Chart.js, GitHub Actions (`actions/upload-pages-artifact`, `actions/deploy-pages`).

**Spec:** `docs/superpowers/specs/2026-11-20-component-catalog-design.md`

## Global Constraints

- **Nothing copied from paid UI kits.** Do not fetch/read any paid template site. Base markup on Basecoat UI (MIT) and shadcn/ui ideas (MIT) only.
- **No reference to any third-party template site** in code, comments, docs, catalog, commit messages.
- All demo copy and data invented by us (people, companies, emails `@example.com`, amounts, metrics).
- PHP `>=8.2`, CakePHP `^5.0`, `declare(strict_types=1);`, Basecoat 1.1.0 markup (`data-variant` / `data-size`).
- New helpers extend `BasecoatTheme\View\Helper\AbstractComponentHelper`, expose `class`, `attributes`, `classes.<part>`, `templates` config like existing helpers, escape text by default (`escape` option), translatable strings via `__d('basecoat_theme', ...)`.
- No inline JS / inline event handlers in helper output (CSP, see `tests/TestCase/View/CspCompatibilityTest.php`). Behaviour goes in `webroot/js/basecoat-theme.js` via `data-*` hooks.
- Generator code is **not** in `src/`; namespace `BasecoatTheme\Docs\` → `docs/catalog/src/` (autoload-dev).
- Site under `/cakephp-basecoat-theme/`: every link/asset in generated HTML is relative.
- Tests: `vendor/bin/phpunit`; helper tests extend `BasecoatTheme\Test\TestCase\View\Helper\HelperTestCase` (gives `$this->View`, `squash()`).

## Parallel execution & file ownership

Tasks 1–7 run in parallel, each in its own git worktree, each committing on its own branch; run `composer install` in the worktree first (do not symlink `vendor/`: Composer's autoloader resolves to the real path). **An agent edits only the files listed in its task.** If it needs a change in any other file (helper registration, JS, translations, README), it lists it in its final report under "Integration requests" instead. Task 8 (integration, main session) merges the branches into `feat/component-catalog` and applies those requests.

Shared files reserved for Task 8: `src/View/BasecoatHelpers.php`, `webroot/js/basecoat-theme.js`, `resources/locales/**`, `README.md`, `docs/catalog/pages/index.php` (final version), `tests/TestCase/View/CspCompatibilityTest.php`.

## Review Focus

1. User data containing HTML (`<b>`, quotes, `&`) in names, messages, labels → escaped in every new helper. *(test `testEscapes` in each helper task)*
2. Two instances of the same component on one page (two `DateRangeField`, two `CommandMenu`, two `ChoiceCards`) → unique, deterministic ids; labels still point to their own inputs. *(test `testUniqueIds` in Tasks 4, 5, 6)*
3. Empty input (no messages, no members, no workspaces, no controls) → renders a sensible empty element or `''`, never a warning. *(test `testEmpty` in each helper task)*
4. Generated site opened under a sub-path → no absolute `/...` links or assets. *(test `testOutputUsesRelativeUrls` in Task 1)*
5. Example throwing or emitting a notice, or UTF-8 content (`é`, `€`, `—`) → build fails naming the file / text renders intact. *(tests `testWarningFailsBuild`, `testUtf8Preserved` in Task 1)*

---

### Task 1: Catalog generator + Pages workflow  *(agent "generator")*

**Files:**
- Create: `bin/build-gh-pages`, `docs/catalog/bootstrap.php`, `docs/catalog/helpers.php`, `docs/catalog/src/ExampleFile.php`, `docs/catalog/src/CatalogBuilder.php`, `docs/catalog/src/BuildReport.php`, `docs/catalog/src/HtmlFormatter.php`, `docs/catalog/templates/layout.php`, `docs/catalog/templates/helper_page.php`, `docs/catalog/templates/example.php`, `docs/catalog/assets/catalog.js`, `docs/catalog/assets/catalog.css`, `docs/catalog/pages/index.php` (placeholder: "Overview" heading + link to components; Task 8 replaces it)
- Create: `docs/catalog/examples/badge/01-variants.php` (one real example to prove the pipeline)
- Create: `.github/workflows/pages.yml`
- Modify: `.github/workflows/ci.yml` (add docs step), `composer.json` (scripts + autoload-dev), `.gitignore` (`/build/`)
- Test: `tests/TestCase/Docs/ExampleFileTest.php`, `tests/TestCase/Docs/CatalogBuilderTest.php`, fixtures under `tests/TestCase/Docs/fixtures/`

**Interfaces:**
- Produces `docs/catalog/helpers.php` returning the catalog registry (all 24 existing + 9 new helpers; slug = kebab-case of alias):
  `array<string slug, array{alias: string, title: string, group: string, intro: string}>`.
  Groups: `Layout` (Sidebar, Breadcrumb, Card, Tabs, Accordion, PageHeader, WorkspaceSwitcher, CommandMenu), `Forms` (Form, Button, ButtonGroup, ChoiceCards, DateRangeField), `Data display` (Table, TableToolbar, Pagination, Badge, Avatar, MemberList, Kbd, Chart, MetricCard, Icon), `Feedback` (Alert, Toast, Progress, Skeleton, EmptyState, Conversation), `Overlays` (Dialog, DropdownMenu, Tooltip), `Theme` (Theme).
- Produces `final class ExampleFile` — `public static function fromPath(string $path): self`; readonly props `string $path, string $slug, int $order, string $title, string $description, string $setup, string $code`.
- Produces `final class CatalogBuilder` — `__construct(string $examplesDir, string $pagesDir, array $registry)`, `build(string $outDir, bool $strict = false): BuildReport` (`BuildReport`: readonly `array $pages`, `array $warnings`).
- CLI: `php bin/build-gh-pages [--out=build/gh-pages] [--strict]`, exit 1 on any failure. `--strict` also fails when a registry helper has no example files or its alias is not loadable. Non-strict skips (with a warning) helpers whose class does not exist yet, so the build works before Task 8.
- Example contract (from spec): header docblock with `@title`, `@description`; optional setup above a line that is exactly `// ---`; folder = helper slug; files ordered by `NN-` prefix.

- [ ] **Step 1: Write failing `ExampleFileTest`**
  - `testParsesHeaderAndCode`: fixture `badge/02-sizes.php` (`@title Sizes`, `@description Small and default.`, body `echo $this->Badge->render('A');`) → `title === 'Sizes'`, `order === 2`, `slug === 'badge'`, `setup === ''`, `code === "echo \$this->Badge->render('A');"` (trimmed, no `<?php`, no docblock).
  - `testSplitsSetup`: body `$rows = [1];\n// ---\necho count($rows);` → `setup === '$rows = [1];'`, `code === 'echo count($rows);'`.
  - `testMissingTitleThrows`: no `@title` → `InvalidArgumentException` whose message contains the file path.
- [ ] **Step 2: Run** `vendor/bin/phpunit tests/TestCase/Docs/ExampleFileTest.php` → FAIL (class not found).
- [ ] **Step 3: Implement `ExampleFile::fromPath()`** — regex on the first docblock; split on the first line equal to `// ---`.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Write failing `CatalogBuilderTest`** (temp out dir, fixture examples, registry with `badge`):
  - `testBuildsHelperPageWithCodeAndOutput`: `components/badge.html` contains the example title, the escaped PHP (`$this-&gt;Badge-&gt;render`) and the live `<span class="badge">A</span>` in the preview.
  - `testWarningFailsBuild`: fixture `broken/01-warn.php` with `echo $undefined;` → `RuntimeException` containing `broken/01-warn.php`.
  - `testUtf8Preserved`: example `Badge->render('Café — 12 €')` → output contains `Café — 12 €`.
  - `testOutputUsesRelativeUrls`: in every generated `.html`, `/(?:href|src)="\/(?!\/)/` does not match.
  - `testStrictFailsOnHelperWithoutExamples`: registry has `kbd` without fixtures → strict throws; non-strict lists it in `warnings`.
  - `testIndexPageRendered`: `index.html` exists and contains `<aside class="sidebar"` (theme `default` layout).
- [ ] **Step 6: Run** → FAIL.
- [ ] **Step 7: Implement.** `docs/catalog/bootstrap.php` mirrors `tests/bootstrap.php` (TMP under `sys_get_temp_dir()`, base `''`, plugin `BasecoatTheme` loaded). `CatalogBuilder`: fresh `View` per example; load helpers from `BasecoatHelpers::MAP` with `className` (as `templates/element/layout/helpers.php`, but **including** `Form` → `BasecoatTheme.Form`); `set_error_handler` → `ErrorException` wrapped with the example path; `ob_start` per example. `HtmlFormatter::pretty(string $html): string` indents by tag nesting (no external lib). `index.html` rendered from `pages/index.php` with `setTheme('BasecoatTheme')`, layout `default`. Output: `index.html`, `components/index.html` (groups grid), `components/<slug>.html`, `assets/` (copy `docs/catalog/assets/*` + `webroot/js/basecoat-theme.js`). Highlighting: highlight.js `11.9.0` from cdnjs (php + xml), light/dark styles. Preview/PHP/HTML tabs via the plugin's `Tabs` helper; collapsed "Setup" via `<details>`; copy button via `data-copy` in `catalog.js`.
- [ ] **Step 8: Run** `vendor/bin/phpunit tests/TestCase/Docs` → PASS; `composer docs` → exit 0, prints page count; open `build/gh-pages/components/badge.html` → theme toggle and tabs work.
- [ ] **Step 9: Workflows.** `pages.yml`: `on: push: branches: [main]` + `workflow_dispatch`; `permissions: {contents: read, pages: write, id-token: write}`; `concurrency: {group: pages, cancel-in-progress: true}`; job `build`: checkout, `shivammathur/setup-php@v2` (php 8.3, ext `mbstring, intl`), `composer install --no-interaction --no-progress`, `composer docs`, `actions/configure-pages@v5`, `actions/upload-pages-artifact@v3` (path `build/gh-pages`); job `deploy`: `needs: build`, `environment: github-pages`, `actions/deploy-pages@v4`. `ci.yml`: step `Build catalog`, `if: matrix.php == '8.3'`, `run: composer docs`.
- [ ] **Step 10: Commit** `feat(docs): static component catalog generator and Pages workflow`.

---

### Task 2: `MetricCard`, `PageHeader`, `Button` indicator  *(agent "metrics")*

**Files:**
- Create: `src/View/Helper/MetricCardHelper.php`, `src/View/Helper/PageHeaderHelper.php`, `tests/TestCase/View/Helper/MetricCardHelperTest.php`, `tests/TestCase/View/Helper/PageHeaderHelperTest.php`
- Modify: `src/View/Helper/ButtonHelper.php`, `tests/TestCase/View/Helper/ButtonHelperTest.php`
- Create examples: `docs/catalog/examples/metric-card/01-basic.php`, `02-with-chart.php`; `docs/catalog/examples/page-header/01-basic.php`, `02-with-actions.php`; `docs/catalog/examples/button/05-indicator.php`

**Interfaces:**
- Consumes: `CardHelper`, `ChartHelper::render(iterable $data, array $series = [], array $options = [])`, `IconHelper`, `ButtonHelper::icon(string $icon, string $label, array $options = [])`.
- Produces:
  - `MetricCardHelper::render(string $label, string $value, array $options = []): string`. Options: `change` (e.g. `+12.4%`), `trend` (`up`|`down`|`flat`, default `flat`), `description` (footer, e.g. "vs. last month"), `icon`, `chart` (`array{data: iterable, series: array, type?: string, labelKey?: string}` → sparkline via `Chart->render`, class `classes.chart` default `h-16 w-full`), `url` (whole card links). Root `.card` with `data-trend="<trend>"`. Config `classes.up` = `text-emerald-600 dark:text-emerald-400`, `classes.down` = `text-destructive`, `classes.flat` = `text-muted-foreground`. Sr-only prefix: `__d('basecoat_theme', 'Increase')` / `'Decrease'` / `'No change'`.
  - `PageHeaderHelper::render(string $title, array $options = []): string`. Options: `description`, `actions` (HTML string or `string[]`, not escaped), `level` (default 1), `breadcrumb` (HTML). Root `<header class="flex flex-wrap items-center justify-between gap-4">`.
  - `ButtonHelper` option `indicator` on `render()`, `link()`, `icon()`: `true` → dot `<span … aria-hidden="true">`; int/string → count badge; `indicatorLabel` (sr-only; default `__d('basecoat_theme', '{0} new', $count)`, or `__d('basecoat_theme', 'New')` for a dot). Root gets class `relative` only when set.

- [ ] **Step 1: Failing tests.** MetricCard: `testRender` (label, value, `data-trend="up"`, change wrapped in an element with the `up` classes, sr-only "Increase"), `testChartSparkline` (`<canvas`, `h-16 w-full`), `testEscapes` (`A <b>` → `A &lt;b&gt;`), `testEmpty` (`render('Users', '0')` → no change element, no canvas). PageHeader: `testRender` (`<h1`, title, description), `testActionsArrayJoined`, `testLevel` (`<h2`), `testEscapes`. Button: `testIndicatorDot` (`icon('bell', 'Notifications', ['indicator' => true])` → dot span `aria-hidden="true"`, class `relative`), `testIndicatorCount` (`indicator => 3` → `>3<` + sr-only `3 new`), `testNoIndicatorUnchanged` (`render('Save')` output identical to current).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (add `indicator`, `indicatorLabel` to Button's excluded option keys).
- [ ] **Step 4: Run** `vendor/bin/phpunit` (full suite) → PASS.
- [ ] **Step 5: Examples** (invented: "Active users 1,284 +12.4%", "Monthly revenue €8,420", 7-point sparkline).
- [ ] **Step 6: Commit** `feat: MetricCard and PageHeader helpers, Button indicator`. Report integration requests (MAP `MetricCard`, `PageHeader`; strings).

---

### Task 3: `Conversation`, `MemberList`  *(agent "people")*

**Files:**
- Create: `src/View/Helper/ConversationHelper.php`, `src/View/Helper/MemberListHelper.php`, `tests/TestCase/View/Helper/ConversationHelperTest.php`, `tests/TestCase/View/Helper/MemberListHelperTest.php`
- Create examples: `docs/catalog/examples/conversation/01-basic.php`, `02-without-composer.php`; `docs/catalog/examples/member-list/01-roles.php`, `02-readonly.php`

**Interfaces:**
- Consumes: `AvatarHelper::render(string $name, array $options = [])`, `CardHelper`, `ButtonHelper::icon()`, `BadgeHelper::render()`, `IconHelper`.
- Produces:
  - `ConversationHelper::render(array $messages, array $options = []): string`. Message `array{text: string, from: 'me'|'them', time?: string, author?: string}`. Options: `contact` (`array{name: string, subtitle?: string, avatar?: string}` → header), `composer` (`false` | `array{url?: string|array, name?: string, placeholder?: string}`; default on, name `message`, placeholder `__d('basecoat_theme', 'Type a message…')`, send button `__d('basecoat_theme', 'Send')`), `headerActions` (HTML). Thread `role="log" aria-live="polite"`. `classes.sent` default `ml-auto bg-primary text-primary-foreground`, `classes.received` default `bg-muted`. Composer: POST `<form>` with `action` only when `url` given.
  - `MemberListHelper::render(array $members, array $options = []): string`. Member `array{name: string, email?: string, avatar?: string, role?: string, id?: string|int}`. Options: `roles` (`array<string, string>` → `<select class="select">` per row named `{field}[{id ?? index}]`, `aria-label` = `__d('basecoat_theme', 'Role for {0}', $name)`), `field` (default `roles`), `readonly` (role as `Badge`), `empty` (default `__d('basecoat_theme', 'No members yet.')`). Root `<ul role="list">`.

- [ ] **Step 1: Failing tests.** Conversation: `testRender` (one bubble with sent classes, one with received, contact name in header), `testComposerDefault` (`name="message"`, placeholder, "Send"), `testComposerDisabled`, `testEscapes` (`<script>x</script>` escaped), `testEmpty` (thread present, no bubbles). MemberList: `testRolesSelect` (`name="roles[7]"`, option `editor` selected), `testReadonlyBadge`, `testEscapes`, `testEmpty` (empty text).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS.
- [ ] **Step 5: Examples** (invented support dialogue about a duplicated invoice; team of 3 invented people `@example.com`, Owner/Editor/Viewer).
- [ ] **Step 6: Commit** `feat: Conversation and MemberList helpers`. Report integration requests.

---

### Task 4: `CommandMenu`, `WorkspaceSwitcher`  *(agent "navigation")*

**Files:**
- Create: `src/View/Helper/CommandMenuHelper.php`, `src/View/Helper/WorkspaceSwitcherHelper.php`, `tests/TestCase/View/Helper/CommandMenuHelperTest.php`, `tests/TestCase/View/Helper/WorkspaceSwitcherHelperTest.php`
- Create examples: `docs/catalog/examples/command-menu/01-basic.php`; `docs/catalog/examples/workspace-switcher/01-basic.php`

**Interfaces:**
- Consumes: `DialogHelper`, `KbdHelper::render(array|string $keys, array $options = [])`, `DropdownMenuHelper::render(string $trigger, array $items, array $options = [])`, `AvatarHelper`, `IconHelper`, `UrlHelper`. Check Basecoat 1.1.0 `command` and `dropdown-menu` markup with Context7 — markup must match Basecoat's JS.
- Produces:
  - `CommandMenuHelper::render(array $groups, array $options = []): string` — `<dialog class="command-dialog">` with Basecoat `.command` (search input + list). Groups `array<string label, list<array{label: string, url?: string|array, icon?: string, shortcut?: string|array, keywords?: string}>>`. Options: `id` (default `uniqueId('command-menu')`), `placeholder` (`__d('basecoat_theme', 'Type a command or search…')`), `empty` (`__d('basecoat_theme', 'No results found.')`), `hotkey` (default `k`, emitted as `data-command-hotkey`).
  - `CommandMenuHelper::trigger(string $menuId, array $options = []): string` — search-looking button: icon + `label` (`__d('basecoat_theme', 'Search…')`) + `Kbd` `⌘K`; `data-command-open="<menuId>"`.
  - `WorkspaceSwitcherHelper::render(array $workspaces, array $options = []): string` — dropdown; trigger shows current workspace (logo/avatar, name, plan). Workspace `array{name: string, plan?: string, logo?: string, icon?: string, url?: string|array, current?: bool}` (first is current if none flagged). Options: `addUrl`, `addLabel` (`__d('basecoat_theme', 'Add workspace')`), `label` (`__d('basecoat_theme', 'Workspaces')`).
  - Integration request (JS): open `dialog#<id>` on click of `[data-command-open]` and on Ctrl/⌘ + hotkey. Snippet in report.

- [ ] **Step 1: Failing tests.** CommandMenu: `testRender` (dialog id, placeholder, group labels, item links), `testTrigger` (`data-command-open="cm"`, `<kbd`), `testUniqueIds` (`command-menu-1`, `command-menu-2`), `testEscapes`, `testEmpty` (dialog with empty message). WorkspaceSwitcher: `testRenderCurrent`, `testFirstIsCurrentByDefault`, `testAddAction`, `testEscapes`, `testEmpty` (`''`).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS.
- [ ] **Step 5: Examples** (workspaces "Northwind Labs" / "Bluebird Studio"; commands "New project", "Invite people", "Toggle theme", "Settings").
- [ ] **Step 6: Commit** `feat: CommandMenu and WorkspaceSwitcher helpers`. Report integration requests.

---

### Task 5: `DateRangeField`, `TableToolbar`  *(agent "filters")*

**Files:**
- Create: `src/View/Helper/DateRangeFieldHelper.php`, `src/View/Helper/TableToolbarHelper.php`, `tests/TestCase/View/Helper/DateRangeFieldHelperTest.php`, `tests/TestCase/View/Helper/TableToolbarHelperTest.php`
- Create examples: `docs/catalog/examples/date-range-field/01-basic.php`, `02-presets.php`; `docs/catalog/examples/table-toolbar/01-controls.php`, `02-with-table.php` (setup builds rows and request query)

**Interfaces:**
- Consumes: `BasecoatTheme\Listing\ListFilter::controls(): array<string, array{type: string, label: string, options?: array}>` (types `search`, `select`, `text`, `dateRange`), `BasecoatTheme.Form`, `ButtonHelper`, `IconHelper`, request query. Basecoat `popover` markup via Context7.
- Produces:
  - `DateRangeFieldHelper::render(string $name, array $options = []): string` — popover trigger showing `"{from} – {to}"` (or `placeholder`, `__d('basecoat_theme', 'Pick a date range')`), panel with `<input type="date" name="{name}[from]">` / `name="{name}[to]"`. Options: `label`, `from`, `to` (`Y-m-d`; default request query `{name}.from` / `{name}.to`), `presets` (`array<string label, array{0: string, 1: string}>` → buttons with `data-range-from` / `data-range-to`), `min`, `max`, `id`. Compatible with `BasecoatTheme.DateRange` filter. Inputs always in the form (works without JS).
  - `TableToolbarHelper::render(array $options = []): string` — GET `<form role="search">`. Options: `filter` (`ListFilter` or `controls()`-shaped array), `actions` (HTML, right side), `url` (default current path), `submit` (`__d('basecoat_theme', 'Filter')`), `htmx` (`false` | `array{target: string}` → same `hx-*` attributes as `templates/element/list_filters.php`). Controls rendered like `list_filters.php`, except `dateRange` → `DateRangeField`. Keeps `sort` / `direction` as hidden inputs.
  - Integration request (JS): preset buttons fill both inputs and update trigger text. Snippet in report.

- [ ] **Step 1: Failing tests.** DateRangeField: `testRender` (inputs `created[from]`/`created[to]`, trigger `2026-11-01 – 2026-11-30`), `testReadsRequestQuery` (`?created[from]=2026-01-01`), `testPresets` (`data-range-from`), `testUniqueIds` (distinct popover ids; each `<label for>` matches its input), `testEscapes`. TableToolbar: `testControlsArray` (search + select + dateRange → `type="search"`, `<select`, `[from]` inputs), `testListFilterInstance` (real `ListFilter` built like `tests/TestCase/Listing/ListFilterTest.php`), `testKeepsSort` (`?sort=name&direction=asc` → hidden inputs), `testHtmxAttributes`, `testEmpty` (`''`).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS.
- [ ] **Step 5: Examples** (invented "Orders": customer, total, status badges Paid/Pending/Failed, row `DropdownMenu`).
- [ ] **Step 6: Commit** `feat: DateRangeField and TableToolbar helpers (ListFilter compatible)`. Report integration requests.

---

### Task 6: `ChoiceCards`  *(agent "choices")*

**Files:**
- Create: `src/View/Helper/ChoiceCardsHelper.php`, `tests/TestCase/View/Helper/ChoiceCardsHelperTest.php`
- Create examples: `docs/catalog/examples/choice-cards/01-plans.php`, `02-with-icons.php`

**Interfaces:**
- Consumes: `IconHelper`, request data.
- Produces: `ChoiceCardsHelper::render(string $name, array $choices, array $options = []): string` — `<fieldset role="radiogroup">` + `legend`; each choice a `<label>` card wrapping a native `<input type="radio" class="sr-only peer">`, icon, title, description. Choices `array<string|int value, string|array{title: string, description?: string, icon?: string, disabled?: bool}>`. Options: `value` (default request data `$name`), `legend`, `columns` (default 3 → `grid gap-3 sm:grid-cols-3`), `required`, `id`. `classes.card` default includes `has-[:checked]:border-primary has-[:focus-visible]:ring-2`.

- [ ] **Step 1: Failing tests.** `testRender` (3 radios `name="plan"`, legend), `testSelected` (`value => 'pro'` → `checked="checked"`), `testDisabled`, `testUniqueIds`, `testEscapes`, `testEmpty` (`''`).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS.
- [ ] **Step 5: Examples** (plans Starter / Team / Enterprise; payment type cards with `credit-card`, `landmark`, `wallet`).
- [ ] **Step 6: Commit** `feat: ChoiceCards helper`. Report integration requests.

---

### Task 7: Examples for the existing helpers  *(agent "examples")*

**Files:**
- Create only `docs/catalog/examples/<slug>/NN-*.php` for: `accordion, alert, avatar, breadcrumb, button-group, card, chart, dialog, dropdown-menu, empty-state, form, icon, kbd, pagination, progress, sidebar, skeleton, table, tabs, theme, toast, tooltip`; `button/01..04` (Task 2 owns `button/05`); `badge/02+` (Task 1 owns `badge/01`).

**Interfaces:**
- Consumes: each helper's public API (class docblocks); example contract from Task 1.
- Produces: ≥1 example per helper, 2–4 for Table, Form, DropdownMenu, Dialog, Sidebar, Chart. Context-dependent helpers (`Pagination`, `Form` entity, `Table` sort, `Sidebar` current item) put setup above `// ---`.

- [ ] **Step 1:** Write the examples (invented data only).
- [ ] **Step 2: Verify** each file runs clean before Task 1 lands: `php -l` on all, then a throwaway scratchpad script that requires `tests/bootstrap.php`, creates a `View`, loads `BasecoatHelpers::MAP` helpers (incl. `Form`), and `include`s each example inside `ob_start()` with an error handler that throws. Expected: no exception for any file.
- [ ] **Step 3: Commit** `docs: catalog examples for existing helpers`.

---

### Task 8: Integration  *(main session, after Tasks 1–7)*

**Files:**
- Modify: `src/View/BasecoatHelpers.php` (add `ChoiceCards`, `CommandMenu`, `Conversation`, `DateRangeField`, `MemberList`, `MetricCard`, `PageHeader`, `TableToolbar`, `WorkspaceSwitcher`), `webroot/js/basecoat-theme.js` (snippets from Tasks 4, 5), `resources/locales/basecoat_theme.pot`, `resources/locales/es/basecoat_theme.po` (new strings + Spanish), `README.md` (new helpers, helper count, catalog link), `.github/workflows/ci.yml` (`composer docs -- --strict`)
- Replace: `docs/catalog/pages/index.php` — demo dashboard of a fictional SaaS: header with `WorkspaceSwitcher`, `CommandMenu` trigger, bell `Button` with indicator; `PageHeader` "Overview" + `DateRangeField` + "Download report"; 4 `MetricCard`s (active users, revenue, churn, NPS); traffic `Chart` card; orders `Table` with `TableToolbar`, status `Badge`s, row `DropdownMenu`; support `Conversation`; team `MemberList`; plan `ChoiceCards`; links "Browse components" → `components/index.html` and to this file on GitHub. All copy invented.
- Test: `tests/TestCase/View/CspCompatibilityTest.php` (cover new helpers if it enumerates them)

- [ ] **Step 1:** Merge the 7 branches into `feat/component-catalog`. Expected: no conflicts (a conflict = ownership violation; resolve and note it).
- [ ] **Step 2:** Apply integration requests (MAP, JS, strings + `es`).
- [ ] **Step 3: Run** `vendor/bin/phpunit` → all PASS.
- [ ] **Step 4:** Write the demo dashboard. **Run** `composer docs -- --strict` → exit 0, a page for every registry helper.
- [ ] **Step 5: Browser check** under a sub-path (`php -S localhost:8000 -t build`, open `/gh-pages/`): home dashboard, theme toggle, ⌘K, date presets, Preview/PHP/HTML tabs, copy, dropdowns, charts; no console errors.
- [ ] **Step 6:** Grep the branch diff for third-party template site names or copied sample data → none.
- [ ] **Step 7: Commit** `feat: register dashboard helpers, catalog home and translations`.
- [ ] **Step 8:** After merge to `main`: user sets Settings → Pages → Source "GitHub Actions" once; confirm `https://delamux.github.io/cakephp-basecoat-theme/`.
