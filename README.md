# BasecoatTheme for CakePHP 5

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/delamux/cakephp-basecoat-theme/main/docs/screenshots/dashboard-dark.png">
  <img alt="BasecoatTheme dashboard layout" src="https://raw.githubusercontent.com/delamux/cakephp-basecoat-theme/main/docs/screenshots/dashboard-light.png">
</picture>

| Light | Dark |
| --- | --- |
| ![Dashboard, light mode](https://raw.githubusercontent.com/delamux/cakephp-basecoat-theme/main/docs/screenshots/dashboard-light.png) | ![Dashboard, dark mode](https://raw.githubusercontent.com/delamux/cakephp-basecoat-theme/main/docs/screenshots/dashboard-dark.png) |

A CakePHP 5 **theme plugin** for [Basecoat UI](https://basecoatui.com) (the shadcn/ui look, no React).
Every Basecoat component has its **own View Helper**, and every helper can be parametrized with
classes, variants, sizes, attributes and string templates.

- Theme: layouts (`default` dashboard with inset sidebar, `auth`, `error`), sidebar/user menu elements and flash elements.
- 24 helpers: Accordion, Alert, Avatar, Badge, Breadcrumb, Button, ButtonGroup, Card, Chart, Dialog,
  DropdownMenu, EmptyState, Form, Icon, Kbd, Pagination, Progress, Sidebar, Skeleton, Table, Tabs,
  Theme, Toast, Tooltip.
- Core helper integration: `FormHelper` (Basecoat classes on every widget), `PaginatorHelper`
  templates, `FlashHelper` elements, `HtmlHelper`/`UrlHelper` for every URL and asset.
- Zero build step by default (Basecoat CSS + Tailwind v4 browser build from jsDelivr), or plug in your own compiled CSS.

Requirements: PHP 8.2+, CakePHP 5.x. Basecoat 1.1.0 markup (`data-variant` / `data-size` API).

## Installation

```bash
composer require delamux/cakephp-basecoat-theme
```

```php
// src/Application.php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('BasecoatTheme');
}
```

In production, symlink the plugin assets (`bin/cake plugin assets symlink`).

## Use it as a theme

```php
// src/Controller/AppController.php
public function beforeRender(\Cake\Event\EventInterface $event): void
{
    parent::beforeRender($event);
    $this->viewBuilder()->setTheme('BasecoatTheme');
}
```

With the theme set, CakePHP looks for layouts and elements in the plugin first and falls back to your
app `templates/`. The default layout renders the sidebar, the sticky header (sidebar toggle, title,
theme toggle), flash messages and the toaster.

Use the auth layout for login pages: `$this->viewBuilder()->setLayout('auth');`

### Layout data

| Source (view var / `Configure`) | Purpose |
| --- | --- |
| `title` view var or `title` block | `<title>` and header heading |
| `brand` / `BasecoatTheme.brand` | `['name' => 'Acme Inc.', 'url' => '/', 'icon' => 'circle']` |
| `sidebarMenu` / `BasecoatTheme.sidebar` | Menu for `SidebarHelper::render()` |
| `sidebarActions` / `BasecoatTheme.sidebarActions` | HTML under the brand (e.g. "Quick create") |
| `sidebarUser` | `['name', 'email', 'avatar', 'menu' => [...DropdownMenu items]]` |
| `lang` | `<html lang>` |
| `basecoatCharts` | Force loading Chart.js (auto-detected when `ChartHelper` rendered a chart) |

Blocks: `sidebar` (replace the whole sidebar), `header_actions`, `breadcrumb`, `meta`, `css`, `script`, `scriptBottom`.

```php
// config/app_local.php (or bootstrap)
'BasecoatTheme' => [
    'brand' => ['name' => 'Acme Inc.', 'url' => '/', 'icon' => 'circle'],
    'sidebar' => [
        ['label' => 'Dashboard', 'url' => ['controller' => 'Dashboard', 'action' => 'index'], 'icon' => 'layout-dashboard'],
        ['type' => 'group', 'label' => 'Documents', 'items' => [
            ['label' => 'Reports', 'url' => ['controller' => 'Reports', 'action' => 'index'], 'icon' => 'file-text'],
            ['label' => 'Projects', 'url' => ['controller' => 'Projects', 'action' => 'index'], 'icon' => 'folder'],
        ]],
        ['type' => 'group', 'attributes' => ['class' => 'mt-auto'], 'items' => [
            ['label' => 'Settings', 'url' => '/settings', 'icon' => 'settings-2'],
        ]],
    ],
],
```

To override any theme template from your app, copy it to `templates/plugin/BasecoatTheme/...`
(e.g. `templates/plugin/BasecoatTheme/element/layout/sidebar.php`).

## Loading the helpers

Load them all in `AppView`:

```php
namespace App\View;

use BasecoatTheme\View\BasecoatHelpersTrait;
use Cake\View\View;

class AppView extends View
{
    use BasecoatHelpersTrait;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadBasecoatHelpers([
            'Button' => ['size' => 'sm'],          // per-helper configuration
            'Table' => ['class' => 'text-sm'],
            // 'Form' => false,                    // keep the core FormHelper
        ]);
    }
}
```

or one by one: `$this->addHelper('BasecoatTheme.Button', ['variant' => 'outline']);`.

## Parametrizing helpers

Every component helper extends `AbstractComponentHelper` and is configurable at three levels
(lowest to highest priority):

1. **Defaults** of the helper (`$_defaultConfig`).
2. **Helper config** when loading it:

   ```php
   $this->addHelper('BasecoatTheme.Button', [
       'variant' => 'outline',                 // default data-variant
       'size' => 'sm',                         // default data-size
       'class' => 'rounded-full',              // extra classes on the root element
       'attributes' => ['data-turbo' => 'false'], // extra attributes on the root element
       'classes' => ['icon' => 'size-3'],      // extra classes on inner parts (per helper)
       'templates' => ['button' => '<button{{attrs}}>{{content}}</button>'],
   ]);
   ```

3. **Per call options**: `class` (appended), `variant`, `size`, `escape`, `templateVars`, and **any
   other key is rendered as an HTML attribute**. Inner parts take `<part>Options` arrays
   (`headerOptions`, `bodyOptions`, `footerOptions`, `triggerOptions`...).

Templates are CakePHP `StringTemplate`s, so you can also call `setTemplates()` at runtime or load
them from a config file (`'templates' => 'App.basecoat_card'`).

Text arguments (titles, labels) are escaped by default (`'escape' => false` to disable); content
arguments (card body, tab panels, dialog body, footers) are HTML and never escaped.

## Helpers reference

### Button
```php
echo $this->Button->render('Save', ['type' => 'submit']);
echo $this->Button->render('Delete', ['variant' => 'destructive', 'icon' => 'trash']);
echo $this->Button->render('Next', ['icon' => 'chevron-right', 'iconPosition' => 'end', 'variant' => 'outline']);
echo $this->Button->link('View', ['action' => 'view', $project->id], ['variant' => 'link']);
echo $this->Button->icon('panel-left', 'Toggle sidebar', ['variant' => 'ghost', 'size' => 'icon-sm']);
echo $this->Button->submit('Send');

// Icon-only below `sm` (label kept for screen readers and as `title`): ideal for `header_actions`
echo $this->Button->link('New project', ['action' => 'add'], ['icon' => 'plus', 'size' => 'sm', 'collapse' => 'sm']);
```
Variants: `primary` (default), `secondary`, `outline`, `ghost`, `link`, `destructive`.
Sizes: `xs`, `sm`, `default`, `lg`, `icon`, `icon-xs`, `icon-sm`, `icon-lg`.
`collapse`: `sm`, `md`, `lg`, `xl` or `true` (= `sm`); ignored on buttons without icon. Classes per
breakpoint are configurable with `collapseClasses`.

### Badge
```php
echo $this->Badge->render('New');
echo $this->Badge->render('+12.5%', ['variant' => 'outline', 'icon' => 'trending-up']);
echo $this->Badge->render('Docs', ['url' => '/docs', 'variant' => 'link']);
```

### Alert
```php
echo $this->Alert->render('Heads up!', 'You can add components with the CLI.', ['icon' => 'info']);
echo $this->Alert->render('Unable to process payment', ['Check your card', 'Verify the address'], ['variant' => 'destructive']);
echo $this->Alert->success('Saved');   // also error(), warning(), info() — configurable via `types`
```

### Card
```php
echo $this->Card->render($this->element('stats'), [
    'title' => 'Total Revenue',
    'description' => 'Last 6 months',
    'action' => $this->Badge->render('+12%', ['variant' => 'outline']),
    'footer' => $this->Button->render('Details'),
]);

echo $this->Card->start(['title' => 'Team']); ?>
    ...template code...
<?= $this->Card->end(['footer' => '...']);
```

### Table
```php
echo $this->Table->render(
    [
        'title' => ['label' => 'Title', 'sort' => true],          // PaginatorHelper::sort()
        'status' => 'Status',
        'amount' => ['label' => 'Amount', 'class' => 'text-right'],
    ],
    $rows,                                                         // arrays keyed by column, `_row` = <tr> attributes
    ['caption' => 'Invoices', 'empty' => 'No results.', 'escape' => false, 'footer' => ['Total', '', '$250']],
);
```
Sortable headers show a sort indicator (`chevrons-up-down`, `arrow-up`, `arrow-down`) and the active
column gets `aria-sort="ascending|descending"`. Change the icons with the `sortIcons` config
(`['none' => ..., 'asc' => ..., 'desc' => ...]`) or pass `'sortIcons' => false` for plain links;
classes: `classes.sortLabel`, `classes.sortIcon`, `classes.sortIconActive`.

### Pagination
```php
echo $this->Pagination->render();                                      // prev, numbers, next
echo $this->Pagination->render(['first' => true, 'last' => true, 'counter' => true, 'modulus' => 3]);
echo $this->Pagination->simple();                                      // prev / next only
```
It uses the core `PaginatorHelper` (templates are pushed/popped, so `$this->Paginator` keeps its own).
To style the core helper globally: `$this->addHelper('Paginator', ['templates' => 'BasecoatTheme.paginator_templates']);`

### Form
```php
$this->addHelper('Form', ['className' => 'BasecoatTheme.Form']);

echo $this->Form->create($project);
echo $this->Form->control('name');                       // <div class="field" role="group"><label class="label">… <input class="input">
echo $this->Form->control('notes', ['type' => 'textarea']);
echo $this->Form->control('team_id');                    // select.select
echo $this->Form->control('favorite', ['type' => 'checkbox']);  // horizontal field: input + label
echo $this->Form->control('plan', ['type' => 'checkbox', 'nestedInput' => true]); // "choice card" (label wraps input)
echo $this->Form->button('Delete', ['variant' => 'destructive']);
echo $this->Form->submit('Save');
echo $this->Form->end();
```
Config: `widgetClasses` (class per widget), `button` (default `variant`/`size`), `skipClassWidgets`,
`nestedCheckboxAndRadio` (`false` by default: Basecoat styles a label that wraps its input as a
bordered "choice card", so single checkboxes/radios render input and label as siblings).
Pass `'basecoat' => false` to a control to skip the class. Errors render as `<p role="alert">` and
the field gets `data-invalid="true"`. Template-only alternative for the core helper:
`'templates' => 'BasecoatTheme.form_templates'`.

### Tabs
```php
echo $this->Tabs->render(['Account' => $this->element('account'), 'Password' => $this->element('password')]);
echo $this->Tabs->render([
    ['tab' => 'Outline', 'id' => 'outline', 'panel' => $table],
    ['tab' => 'Past ' . $this->Badge->render('3'), 'id' => 'past', 'panel' => '...', 'escape' => false],
], ['active' => 'past', 'variant' => 'line', 'orientation' => 'vertical']);
```

### DropdownMenu
```php
echo $this->DropdownMenu->render('', [
    ['label' => 'Edit', 'url' => ['action' => 'edit', $id], 'icon' => 'pencil', 'shortcut' => '⌘E'],
    ['type' => 'group', 'label' => 'Columns', 'items' => [
        ['type' => 'checkbox', 'label' => 'Status', 'checked' => true],
    ]],
    '-',
    ['label' => 'Delete', 'variant' => 'destructive'],
], ['align' => 'end', 'trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis-vertical', 'aria-label' => 'Actions']]);
```

### Sidebar
```php
echo $this->Sidebar->render($menu, ['header' => '...', 'footer' => '...', 'side' => 'left', 'open' => true]);
echo $this->Sidebar->toggle();
```
Item types: `item` (`label`, `url`, `icon`, `badge`, `current`), `group`, `submenu`, `separator` (`'-'`).
The current page gets `aria-current="page"` automatically (`currentMatch`: `exact` or `prefix`).

### Dialog
```php
echo $this->Dialog->render('edit-profile', $this->element('profile_form'), [
    'title' => 'Edit profile',
    'description' => 'Make changes to your profile here.',
    'trigger' => 'Edit profile',
    'footer' => $this->Dialog->close('Cancel') . $this->Button->submit('Save'),
]);
echo $this->Dialog->trigger('edit-profile', 'Open');
```

### Toast
```php
echo $this->Toast->toaster();   // already in the default layout
echo $this->Toast->render('Event created', ['category' => 'info', 'description' => 'Sunday at 9:00', 'action' => ['label' => 'Undo', 'url' => '/undo']]);
```

### Chart
```php
echo $this->Chart->render($rows, [
    'desktop' => ['label' => 'Desktop', 'color' => 'var(--chart-1)'],
    'mobile' => ['label' => 'Mobile', 'color' => 'var(--chart-2)'],
], ['type' => 'bar', 'labelKey' => 'month', 'class' => 'h-[250px] w-full', 'chartOptions' => [...]]);
```
Chart.js and the Basecoat chart adapter are loaded by the layout only when a chart was rendered.

### Other components
```php
echo $this->Avatar->render('Jane Doe', ['src' => $user->avatar, 'size' => 'lg']);
echo $this->Avatar->group($people, ['max' => 3]);
echo $this->Accordion->render(['Is it accessible?' => 'Yes.'], ['multiple' => true]);
echo $this->Breadcrumb->render(['Home' => '/', 'Projects' => ['controller' => 'Projects'], 'Acme Website'], ['collapse' => 4]);
echo $this->ButtonGroup->render([['text' => 'Day'], ['text' => 'Week'], ['text' => 'Month']], ['aria-label' => 'Range']);
echo $this->EmptyState->render('No projects yet', ['description' => 'Create your first project.', 'icon' => 'inbox', 'actions' => $this->Button->link('New project', ['action' => 'add'])]);
echo $this->Progress->render(60);
echo $this->Kbd->render(['Ctrl', 'K']);
echo $this->Skeleton->lines(3);
echo $this->Tooltip->render($this->Button->render('?'), 'Help');
echo $this->Button->render('Save', $this->Tooltip->attributes('Save changes', ['side' => 'bottom']));
echo $this->Icon->render('circle-check', ['class' => 'text-green-500']);
```
`IconHelper` ships a Lucide subset; register more with `['icons' => ['rocket' => '<path d="…"/>']]`
or `$this->Icon->add([...])`. Any `icon` option also accepts raw SVG.

### Theme (assets)
```php
echo $this->Theme->head();     // dark-mode script + CSS + JS (used by the layouts)
echo $this->Theme->toggle();   // light / dark button
```
Config: `version` (basecoat-css, default `1.1.0`), `style` (`vega`, `nova`, `luma`, `lyra`, `maia`,
`mira`, `rhea`, `sera`), `css` (your own stylesheet), `tailwindBrowser` (URL or `false`), `font`,
`js`, `chartJs`, `defaultMode` (`system`, `light`, `dark`).

For production, compile Tailwind yourself with `basecoat-css` and disable the browser build:

```php
$this->addHelper('BasecoatTheme.Theme', ['css' => ['app'], 'tailwindBrowser' => false]);
```

## Content Security Policy

Nothing the theme renders needs `'unsafe-inline'`, so it works under a strict CSP (CakePHP `CspMiddleware`):

- Interactivity uses data attributes handled by `webroot/js/basecoat-theme.js` (loaded by `Theme->head()` through the
  `pluginJs` option) with event delegation: `data-theme-toggle`, `data-sidebar-toggle`, `data-dialog-open`,
  `data-dialog-close`, `data-dialog-backdrop-close`, `data-alert-dismiss`, and flash toasts as
  `<template data-basecoat-toast>`. Keep `pluginJs` enabled.
- The dark-mode script and the Tailwind browser `@theme` style carry the request's nonce (`cspScriptNonce` /
  `cspStyleNonce`); HtmlHelper adds it to the scripts and stylesheets.
- Charts and toasts are initialized again in content swapped by htmx (`htmx:load`).
- Toast `action`/`cancel` buttons with an `onclick` key are inline JS: under a strict CSP use `url`, or `attributes`
  with data-* handled by your own script.

Without CSP everything renders and behaves the same (no nonce attributes).

## Flash messages

With the theme set, `$this->Flash->success()`, `error()`, `warning()`, `info()` and `set()` are
rendered as Basecoat alerts (`templates/element/flash/*.php`). Params: `title`, `description`,
`icon`, `escape`, `dismissible`, `class`:

```php
$this->Flash->success('Your project was created.', ['params' => ['title' => 'Saved', 'dismissible' => true]]);
// As a toast, from any theme:
$this->Flash->set('Copied!', ['element' => 'toast', 'plugin' => 'BasecoatTheme', 'params' => ['category' => 'info']]);
```

Without the theme, point to the plugin elements: `$this->Flash->success('Saved', ['plugin' => 'BasecoatTheme']);`

## Example

`templates/element/examples/dashboard.php` rebuilds the shadcn *dashboard-01* page (stat cards,
area chart, tabs with a data table and row menus) only with these helpers:

```php
<?= $this->element('BasecoatTheme.examples/dashboard') ?>
```

## Translations

UI strings use the `basecoat_theme` domain. Spanish is included (`resources/locales/es`).

## Development

```bash
composer install
composer test
```

## License

MIT. Basecoat (MIT) and Lucide icons (ISC) belong to their authors.
