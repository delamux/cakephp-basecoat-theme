# Bake Screens Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `bin/cake bake screen <screen> [<Controller>]` generates full application screens (controller with sample data + template), and the GitHub Pages catalog shows each screen exactly as bake generates it; first screens: Users list and Notifications.

**Architecture:** A `ScreenRegistry` describes screens; each screen has `templates/bake/Screen/<slug>/template.twig` + `data.php`, and one shared `templates/bake/Screen/controller.twig`. `BakeScreenCommand` exposes a pure `renderFiles()` used by both the CLI and the catalog's `ScreenBuilder`, which renders the generated template with the same data inside the theme layout.

**Tech Stack:** PHP 8.2+, CakePHP 5, cakephp/bake ^3.0 (Twig), PHPUnit, existing catalog generator (`docs/catalog/src`).

**Spec:** `docs/superpowers/specs/2026-11-20-bake-screens-design.md`

## Global Constraints

- Paid-template screenshots in `docs/roadmap/` are inspiration only: never copy their markup, copy, names, people, ids, data or layout literally; never fetch the paid site.
- No reference to any third-party template site anywhere (code, docs, catalog, commits).
- All sample data invented (reuse the "Fernway" fictional world: `docs/catalog/pages/index.php`). No real brands, no "Acme"/"Jane Doe" placeholders, no data copied from any library's docs.
- No inline JS; CSP-safe output; behaviour via `webroot/js/basecoat-theme.js` `data-*` hooks.
- PHP `>=8.2`, CakePHP `^5.0`, `cakephp/bake: ^3.0` in `require-dev` and `suggest`; `declare(strict_types=1);`.
- `bake screen` is registered only when `Bake\Command\BakeCommand` exists; apps without bake are unaffected.
- Generated code: idiomatic app code (`App\Controller\AppController` subclass, or the plugin's `AppController` with `--plugin`), does not set the theme, uses helpers exactly like `templates/bake/Template/*.twig`, links to non-generated actions are `#` with a `// TODO` comment, search/filter forms GET to `index`, no faked request attributes.
- Catalog rules from Phase 1: relative URLs, strict build fails on warnings, unique ids per page, every new `__d('basecoat_theme', …)` string in `.pot` + `es/.po`.

## Review Focus

1. Sample data with HTML/quotes/UTF-8 (`<b>`, `"`, `é`, `€`) → generated controller literal stays valid PHP and round-trips; screen escapes it. *(T1 `testControllerLiteralRoundTrips`)*
2. Unknown slug / missing `<screen>` without `--list` → clear error listing slugs, exit 1, no files written. *(T1 `testUnknownScreen`, `testMissingScreenArgument`)*
3. Existing controller file without `--force` → not overwritten silently. *(T1 `testDoesNotOverwriteWithoutForce`)*
4. Controller name in odd case (`inbox`, `Inbox`) → `InboxController`, `templates/Inbox/`. *(T1 `testControllerNameIsCamelized`)*
5. Empty data arrays or a screen rendered on a catalog page → no warnings, no duplicate ids. *(T4 `testScreenPageHasNoDuplicateIds`; T2/T3 `testRendersWithEmptyData`)*

---

### Task 1: `bake screen` command + registry + shared controller template

**Files:**
- Create: `src/Bake/ScreenRegistry.php`, `src/Bake/PhpLiteral.php`, `src/Command/BakeScreenCommand.php`, `templates/bake/Screen/controller.twig`
- Create (test fixture screen `demo`): `tests/test_app/templates/bake/Screen/demo/template.twig`, `tests/test_app/templates/bake/Screen/demo/data.php`
- Modify: `src/BasecoatThemePlugin.php` (console registration), `composer.json` (`require-dev` + `suggest` `cakephp/bake: ^3.0`), test bootstrap / test app as needed to load Bake
- Test: `tests/TestCase/Command/BakeScreenCommandTest.php`, `tests/TestCase/Bake/ScreenRegistryTest.php`, `tests/TestCase/Bake/PhpLiteralTest.php`

**Interfaces:**
- Produces:
  - `final class ScreenRegistry` — `public static function all(): array<string, array{title: string, description: string, controller: string, path: string}>` (built-ins `users` → title "Users", controller `Users`; `notifications` → title "Notifications", controller `Notifications`; `path` = dir with `template.twig` + `data.php`); `public static function get(string $slug): array` (throws `InvalidArgumentException` listing valid slugs); `public static function data(string $slug): array<string, mixed>`; `public static function register(string $slug, array $screen): void`; `public static function reset(): void` (tests).
  - `final class PhpLiteral` — `public static function export(mixed $value, int $indent = 0): string`: short arrays, 4-space indent, one element per line, lists without keys, strings single-quoted with `\\` and `\'` escaped, `null`/`true`/`false` lowercase, ints/floats via `var_export`.
  - `BakeScreenCommand` (name `bake screen`): args `screen` (optional), `controller` (optional, default registry `controller`, camelized); options `--list`, `--force`, `--plugin`. `public function renderFiles(string $slug, string $controller, ?string $plugin = null): array<string, string>` returning exactly `src/Controller/<Controller>Controller.php` and `templates/<Controller>/index.php` (relative to app or plugin root). `execute()` writes them with bake's usual file writing (overwrite prompt unless `--force`).
  - `controller.twig` receives `namespace`, `name`, `vars` (`array<string, string>` var name → `PhpLiteral` export) and writes `public function index(): void` that `$this->set('<var>', <literal>);` each var, with a doc comment saying the data is sample data to replace.
- Tasks 2 and 3 add the built-in screens' `template.twig` + `data.php`; tests here use the `demo` fixture.

- [ ] **Step 1: Failing tests** (`ConsoleIntegrationTestTrait`, output into a temp dir under `TMP`):
  - `testBakesControllerAndTemplate`: `bake screen demo Inbox` → `src/Controller/InboxController.php` (contains `class InboxController extends AppController`, `public function index(): void`) and `templates/Inbox/index.php`; exit 0.
  - `testControllerLiteralRoundTrips`: fixture data `['items' => [['name' => 'Café <b>"x"</b> \'q\' \\', 'total' => '€12', 'ok' => true, 'n' => null]]]` → `eval('return ' . PhpLiteral::export($data['items']) . ';') === $data['items']`, and the generated controller contains that literal.
  - `testControllerNameIsCamelized`: `inbox` and `Inbox` → `InboxController`, `templates/Inbox/index.php`.
  - `testUnknownScreen`: `bake screen nope` → exit 1, error lists `users` and `notifications`, no files.
  - `testMissingScreenArgument`: `bake screen` → exit 1 with a hint about `--list`.
  - `testList`: `bake screen --list` → exit 0, lists every slug + title.
  - `testDoesNotOverwriteWithoutForce` / `testForceOverwrites`.
  - `testPluginOption`: `--plugin <fixture plugin>` → files under that plugin, namespace `<Plugin>\Controller`.
  - `ScreenRegistryTest`: `testAllHasBuiltIns`, `testGetUnknownThrows`. `PhpLiteralTest`: nested list/assoc, scalars, quotes/backslashes, UTF-8 → `eval` round-trip.
- [ ] **Step 2: Run** `vendor/bin/phpunit tests/TestCase/Command tests/TestCase/Bake` → FAIL.
- [ ] **Step 3: Implement** (`renderFiles()` renders through Bake's view/Twig: `BasecoatTheme.Screen/controller` and the screen's `template.twig`; console registration in `BasecoatThemePlugin::console()` guarded by `class_exists(\Bake\Command\BakeCommand::class)`; `composer require --dev cakephp/bake:^3.0`, `suggest` entry).
- [ ] **Step 4: Run** focused tests → PASS; full suite → PASS; `composer docs -- --strict` still exit 0.
- [ ] **Step 5: Commit** `feat(bake): bake screen command, screen registry and shared controller template`.

---

### Task 2: Users list screen

**Files:**
- Create: `templates/bake/Screen/users/template.twig`, `templates/bake/Screen/users/data.php`
- Test: `tests/TestCase/Bake/UsersScreenTest.php`

**Interfaces:**
- Consumes: `BakeScreenCommand::renderFiles()`, `ScreenRegistry::data()` (Task 1); helpers `PageHeader`, `TableToolbar`, `Table`, `Avatar`, `Badge`, `DropdownMenu`, `Button`, `Icon`, `Pagination` (only if it renders from explicit numbers — else `?page=` Previous/Next links).
- Produces: slug `users`; view variables `users` (list of `array{id: int, name: string, email: string, role: string, plan: string, location: string, status: 'active'|'invited'|'suspended'}`), `statuses` (value => label), `total`, `page`, `perPage` (int).

- [ ] **Step 1: Failing test** `UsersScreenTest` — `renderFiles('users', 'Users')`, write the template to a temp file and render it in a `View` (helpers loaded as in `HelperTestCase`) with `ScreenRegistry::data('users')`: `testRendersAllUsers`, `testStatusBadges` (three statuses present), `testToolbarSearchGetsToIndex` (`method="get"`), `testNoLinksToMissingActions` (no `/users/add|edit|delete` hrefs; "Add user" `href="#"`), `testRendersWithEmptyData` (`users => []` → empty state, no warnings), `testEscapes` (`<b>Ana</b>` escaped).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (~12 invented people in the Fernway world). **Step 4: Run** → PASS (+ full suite).
- [ ] **Step 5: Commit** `feat(bake): users list screen`.

---

### Task 3: Notifications screen

**Files:**
- Create: `templates/bake/Screen/notifications/template.twig`, `templates/bake/Screen/notifications/data.php`
- Optional (builder decides): `src/View/Helper/NotificationListHelper.php` + test + `docs/catalog/examples/notification-list/` + entry in `docs/catalog/helpers.php`; then report MAP / i18n / README integration requests.
- Test: `tests/TestCase/Bake/NotificationsScreenTest.php`

**Interfaces:**
- Consumes: Task 1; helpers as Task 2.
- Produces: slug `notifications`; view variables `notifications` (list of `array{id: int, type: 'task'|'message'|'team'|'access', title: string, body: string, time: string, unread: bool, actor?: array{name: string}}`), `unreadCount`, `types` (value => label), `total`, `page`, `perPage`.

- [ ] **Step 1: Failing test** `NotificationsScreenTest`: `testUnreadCountBadge`, `testUnreadRowsHighlighted` (marker attr on unread rows only), `testAccessRequestHasAcceptDecline` (only `type=access`), `testTypeBadges`, `testRendersWithEmptyData`, `testEscapes`, `testNoLinksToMissingActions`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (~10 invented notifications about the Fernway workspace). **Step 4: Run** → PASS (+ full suite).
- [ ] **Step 5: Commit** `feat(bake): notifications screen`.

---

### Task 4: Catalog Screens section

**Files:**
- Create: `docs/catalog/src/ScreenBuilder.php`, `docs/catalog/templates/screen_page.php`, `docs/catalog/templates/screens_index.php`
- Modify: `docs/catalog/src/CatalogBuilder.php` (call ScreenBuilder; nav group "Screens" first; strict handling), `docs/catalog/pages/index.php` (link "Browse screens"), `docs/catalog/assets/catalog.js|css` if needed (iframe sizing, CSP-safe)
- Test: `tests/TestCase/Docs/ScreenBuilderTest.php`

**Interfaces:**
- Consumes: `ScreenRegistry::all()`, `ScreenRegistry::data()`, `BakeScreenCommand::renderFiles()` (Task 1). Works with any registered screen (tests use `demo`).
- Produces: `screens/index.html`, `screens/<slug>.html`, `screens/<slug>/preview.html`. Registered screens whose `template.twig` doesn't exist yet → warning (non-strict) / failure (strict), like helpers without examples.

- [ ] **Step 1: Failing tests** (`demo` fixture): `testBuildsScreenPages`, `testPreviewUsesThemeLayout` (regex `<aside[^>]*class="[^"]*\bsidebar\b`), `testPageShowsExactBakeOutput` (Controller/Template tabs contain the `h()`-escaped `renderFiles()` strings byte-for-byte), `testPageShowsCommand` (`bin/cake bake screen demo`), `testScreenPageHasNoDuplicateIds`, `testRelativeUrls`, `testStrictFailsOnBrokenScreen` (fixture emitting a notice → throws naming the slug).
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (reuse CatalogBuilder's per-render request isolation, error handler and id-counter carry-over; scratch files under `build/`). **Step 4: Run** focused + full suite; `composer docs -- --strict`.
- [ ] **Step 5: Commit** `feat(docs): Screens section in the catalog`.

---

### Task 5: Integration, README and verification

**Files:**
- Modify: `README.md`, `resources/locales/basecoat_theme.pot`, `resources/locales/es/basecoat_theme.po`, `src/View/BasecoatHelpers.php` (only if Task 3 added a helper)

- [ ] **Step 1:** Merge Tasks 2–4; apply integration requests.
- [ ] **Step 2: README** — "Screens (bake)" section: purpose, `bin/cake bake screen <screen> [<Controller>]`, options `--list`, `--force`, `--plugin` with examples, generated files, how to replace sample data, link to catalog Screens. "Scripts and options" section documenting every script: `composer test` (+ filtering), `composer docs` / `php bin/build-gh-pages` (`--out=<dir under build/>`, `--strict`, output location, local preview under a sub-path), `bin/cake bake screen …`, CRUD bake (`bin/cake bake all <Model> --theme BasecoatTheme`, `Configure::write('Bake.theme', …)`, link to list filters/CSV section). Verify every documented option against the code.
- [ ] **Step 3: Run** `vendor/bin/phpunit`, `composer docs -- --strict`, browser check (Chrome DevTools MCP) of `screens/index.html`, `screens/users.html`, `screens/notifications.html` and previews under `/cakephp-basecoat-theme/` (no console errors/404s, tabs, copy, theme toggle).
- [ ] **Step 4:** `git grep -i` for third-party template site names → none; data invented.
- [ ] **Step 5: Commit** `docs: README for bake screens and every script option`.
