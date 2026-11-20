# Bake screens — Phase 2 design

Date: 2026-11-20
Status: approved

## Intent

Users of the plugin want complete application screens (notifications, kanban, chats, mail…), not only
components. They must be able to **generate** a screen into their own app with `bin/cake bake`, and
**see** it first in the GitHub Pages catalog, exactly as it will be generated.

The roadmap has 14 screens. This phase builds the shared machinery and the first two screens, so the
whole chain (bake → app, bake → catalog) is proven before the other 12 are built in later phases.

## Decisions taken with the user

- Screens are generated with bake **and** shown in the catalog (GitHub Pages), from a single source.
- Approach **C**: every screen is first generated with **sample data** that the developer replaces;
  later, list-like screens (Users, Notifications, Tasks, Kanban) may accept `--table <Table>` to bind
  to a real model. Model binding is **not** in this phase.
- First screens: **Users list** and **Notifications**. *(Our call; correctable.)*

## Goals

- `bin/cake bake screen <screen> [<Controller>]` writes into the app:
  - `src/Controller/<Controller>Controller.php` with an `index()` action that sets the screen's sample
    data as view variables (data as PHP array literals);
  - `templates/<Controller>/index.php`, the full screen built with the plugin helpers, reading only
    those view variables.
  Default `<Controller>` per screen (`Users`, `Notifications`). `--force` overwrites, otherwise bake's
  usual overwrite prompt. `bin/cake bake screen --list` lists the available screens.
- The catalog gets a **Screens** section: `screens/index.html` (grid of screens with a short description)
  and for each screen `screens/<screen>.html`, a page with:
  - the bake command to generate it;
  - a **live full-page preview** of the generated template rendered in the theme's `default` layout
    (its own page `screens/<screen>/preview.html`, embedded in an `<iframe>` with an "Open full page" link);
  - tabs with the generated **controller** and **template** source (highlighted, copy button).
- Single source of truth: the catalog obtains the controller and template by running the same bake
  code path and templates bake uses, then renders the generated template with the sample data.
  What you see is byte-for-byte what bake writes.
- **README (user request):** document `bake screen` and everything above, and document **every script
  and its options**: `composer test`, `composer docs` / `bin/build-gh-pages` (`--out`, `--strict`),
  `bin/cake bake screen` (`<screen>`, `[<Controller>]`, `--list`, `--force`, `--plugin`), and the
  existing CRUD bake usage (`--theme BasecoatTheme`, `Bake.theme`), with what each option does.
- CI: `composer docs -- --strict` also builds the screens; a screen that fails to bake or render fails
  the build. Unit tests bake each screen into a temp app and assert the output.

## Non-goals

- Model binding (`--table`), real persistence, actions other than `index` (accept/decline, mark as read
  are buttons/forms without server behaviour in this phase).
- The other 12 roadmap screens.
- Changes to the existing CRUD bake templates.

## Anti-goals (hard rules, carried from Phase 1)

- Paid-template screenshots in `docs/roadmap/` are inspiration only: never copy their markup, copy,
  names, people, ids, data or layout literally. Own composition, own wording. Never fetch the paid site.
- No reference to any third-party template site anywhere (code, docs, catalog, commits).
- All sample data invented (the "Fernway" fictional world of the catalog home can be reused). No real
  brands, no generic placeholders (Acme, Jane Doe), no data copied from any library's docs.
- No inline JS; CSP-safe output, behaviour via `webroot/js/basecoat-theme.js` `data-*` hooks.

## Constraints

- PHP 8.2+, CakePHP 5, cakephp/bake 3.x (the version the existing bake templates target).
- The plugin today has `consoleEnabled = false`; the command must only be registered when
  cakephp/bake is installed (`class_exists(\Bake\Command\BakeCommand::class)`), so apps without bake
  are unaffected. cakephp/bake goes to `require-dev` (tests, catalog) and `suggest`.
- Generated code must be idiomatic CakePHP app code (`App\Controller\AppController` subclass), clean
  enough to commit, and use only the plugin's public helpers (loaded by the theme layout).
- Generated templates must not depend on catalog-only code.

## Approaches considered

| Approach | Trade-off | Decision |
| --- | --- | --- |
| A. `bake screen` with sample data | Same machinery for all 14; dev wires real data by hand | Base of C |
| B. Screens bound to models | Real data out of the box; per-screen field conventions ×14; meaningless for AI chat/onboarding/workflow | Only later, per screen |
| **C. A now, B where it adds value** | Fast, single source; binding added later to list-like screens | **Chosen by the user** |

Single-source mechanism (our call): the catalog renders the bake output, rather than keeping catalog
example files next to the bake templates (two sources that drift).

## Design

### Files

```
src/Command/BakeScreenCommand.php        # `bake screen`, extends Bake's BakeCommand
src/Bake/ScreenRegistry.php              # slug => title, description, default controller
templates/bake/Screen/<slug>/controller.twig
templates/bake/Screen/<slug>/template.twig
templates/bake/Screen/<slug>/data.php    # returns array<string var, mixed> sample data
docs/catalog/src/ScreenBuilder.php       # bakes each screen into build/ and renders it for the catalog
docs/catalog/templates/screen_page.php, screens_index.php
tests/TestCase/Command/BakeScreenCommandTest.php
```

`data.php` is the one place the sample data lives; `controller.twig` receives it and writes it out as
PHP short-array literals, pretty-printed, so the generated controller and the catalog render use the
same values.

### Command behaviour

- `bin/cake bake screen users` → `src/Controller/UsersController.php` + `templates/Users/index.php`.
- `bin/cake bake screen notifications Inbox` → `InboxController` + `templates/Inbox/index.php`.
- `--plugin`, `--force` work as in other bake commands; `--list` prints slugs + titles.
- Unknown slug → error listing valid slugs, exit code 1.

### Screens in this phase (own composition; inspiration only)

**Users list** (`users`): page header with title, short description and "Add user" action; toolbar
with search, a status filter and a filters/columns affordance; table with row checkboxes, avatar +
name, role, plan, email, location, status badge (active / invited / suspended), row actions menu;
footer with result count, rows-per-page select and pagination. ~12 invented people.

**Notifications** (`notifications`): page header with unread-count badge, "Mark all as read" and a
settings icon button; toolbar with search and status/type filters; list rows with type icon or avatar,
title, unread dot, body, type badge, relative time, inline Accept / Decline for access requests;
unread rows highlighted; footer with count and pagination. If a reusable row component emerges
(e.g. `NotificationList`), it becomes a helper with full Phase 1 treatment (tests, catalog page,
README, translations). ~10 invented notifications about the fictional workspace.

### Catalog integration

- `ScreenBuilder` (generator-only) bakes every registered screen into `build/` scratch using the real
  `BakeScreenCommand` code path, then renders the generated template with the `data.php` variables
  inside the theme's `default` layout → `screens/<slug>/preview.html`.
- `screens/<slug>.html`: title, description, the exact command, iframe preview + "Open full page",
  tabs Controller / Template.
- Nav: a **Screens** group at the top of the catalog sidebar; the home dashboard links to it.
- Same rules as Phase 1: relative URLs, strict mode fails on any warning, unique ids per page.

### Technical decisions (our calls, after the builder check)

1. **What the bake templates emit.** `template.twig` is the verbatim PHP/HTML view; Twig is used only
   for a few variables (controller name, plugin). No sample data is interpolated into the view — it
   only reads view variables. `controller.twig` writes the `data.php` values as pretty-printed PHP
   short-array literals.
2. **How the catalog gets the output.** cakephp/bake is a `require-dev` dependency. `ScreenBuilder`
   calls the command's generation step (render through Bake's view + write files) into a scratch dir
   under `build/`; it does not boot a full app or run the CLI. It then renders the generated
   `index.php` in a catalog `View` with `set(<data.php>)`. A test asserts the generated controller's
   literals round-trip to exactly the `data.php` array (so controller and catalog never disagree).
3. **Registration.** Override `BasecoatThemePlugin::console()` (and enable console) to add
   `bake screen` only when `Bake\Command\BakeCommand` exists; nothing else changes for apps without bake.
   The CLI-only bake listener from Phase 1 stays as is. Constraint `cakephp/bake: ^3.0` (Cake 5 line),
   CI matrix unchanged (PHP 8.2–8.5, latest deps).
4. **Theme and helpers.** The generated controller does not set the theme; the app configures it as the
   README already explains. Generated templates use helpers exactly the way the existing CRUD bake
   templates (`templates/bake/Template/*.twig`) do.
5. **Links and controls.** Search/filter forms submit GET to the generated `index` (ignored by the
   sample controller, documented in a code comment). Links to actions that are not generated
   ("Add user", row actions, accept/decline) point to `#` with a `// TODO` comment, never to
   non-existent routes. Pagination must not fake request attributes in generated code: use the
   `Pagination` helper only if it can render from explicit numbers, otherwise simple Previous/Next
   `?page=` links — builder's call.
6. **Tests.** Bake each screen into a temp dir via `ConsoleIntegrationTestTrait` with the test app
   (load Bake there), assert files, key markup, `--force`, unknown slug, `--list`.
7. **`--list`.** The `<screen>` argument is optional when `--list` is given.
8. **`--plugin`.** Supported the way bake's other commands support it (files under the plugin).

## Left to the builder

- Exact helper composition per screen, column set and sample data (invented).
- Whether Notifications warrants a new helper.
- iframe height strategy (fixed vs. CSP-safe auto-resize).

## Prerequisites

- Branch `feat/bake-screens` from `main` (011e60e) — created.
