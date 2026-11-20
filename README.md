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

**Component catalog:** every helper with live examples, their PHP code and the rendered HTML, plus a
demo dashboard built only with these helpers: <https://delamux.github.io/cakephp-basecoat-theme/>.

- Theme: layouts (`default` dashboard with inset sidebar, `auth`, `error`), sidebar/user menu elements and flash elements.
- 34 helpers: Accordion, Alert, Avatar, Badge, Breadcrumb, Button, ButtonGroup, Card, Chart,
  ChoiceCards, CommandMenu, Conversation, DateRangeField, Dialog, DropdownMenu, EmptyState, Form, Icon,
  Kbd, MemberList, MetricCard, NotificationList, PageHeader, Pagination, Progress, Sidebar, Skeleton, Table,
  TableToolbar, Tabs, Theme, Toast, Tooltip, WorkspaceSwitcher.
- Ready-made screens: `bin/cake bake screen users` bakes a controller and a template you own (see
  [Screens (bake)](#screens-bake)), plus Basecoat templates for the CRUD `bake all`.
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
| `brand` / `BasecoatTheme.brand` | `['name' => 'Fernway', 'url' => '/', 'icon' => 'circle']` |
| `sidebarMenu` / `BasecoatTheme.sidebar` | Menu for `SidebarHelper::render()` |
| `sidebarActions` / `BasecoatTheme.sidebarActions` | HTML under the brand (e.g. "Quick create") |
| `sidebarUser` | `['name', 'email', 'avatar', 'menu' => [...DropdownMenu items]]` |
| `lang` | `<html lang>` |
| `basecoatCharts` | Force loading Chart.js (auto-detected when `ChartHelper` rendered a chart) |
| `basecoatEmbed` | `true` renders the layout without the sidebar and its toggle (header with title and theme toggle only), for screens shown inside an iframe. Default `false` |

Blocks: `sidebar` (replace the whole sidebar), `header_actions`, `breadcrumb`, `meta`, `css`, `script`, `scriptBottom`.

```php
// config/app_local.php (or bootstrap)
'BasecoatTheme' => [
    'brand' => ['name' => 'Fernway', 'url' => '/', 'icon' => 'circle'],
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

`icon($icon, $label, $options)` renders an icon-only button (an `<a>` with a `url` option, `size`
defaults to `icon`). Its accessible name is `$label`, written **inside the button as visually hidden
text** (`<span class="sr-only">Toggle sidebar</span>`, always escaped) while the SVG stays
`aria-hidden="true"`, as the [Lucide accessibility guide](https://lucide.dev/how-to/accessibility)
recommends. It does not set `aria-label` any more; pass `'aria-label' => '…'` yourself if you need one
(it then takes precedence over the hidden text).
`collapse`: `sm`, `md`, `lg`, `xl` or `true` (= `sm`); ignored on buttons without icon. Classes per
breakpoint are configurable with `collapseClasses`.

Notification indicator:
```php
echo $this->Button->icon('bell', 'Notifications', ['indicator' => true]);             // dot, "New" for screen readers
echo $this->Button->icon('bell', 'Notifications', ['indicator' => $unread]);          // count badge, "3 new"
echo $this->Button->render('Inbox', ['indicator' => '9+', 'indicatorLabel' => 'Unread messages']);
```
`indicator`: `true` shows a dot, a number or string shows a count badge (the button gets `relative`).
`indicatorLabel` replaces the screen reader text (default "New" / "{0} new", pluralized; `''` for
none); on `icon()` buttons it joins the hidden name ("Notifications, 3 new"). `false`, `null`, `''`,
`0` and `'0'` render no indicator at all, so you can pass an unread count directly. Classes:
`classes.indicatorDot`, `classes.indicatorCount`.

### Badge
```php
echo $this->Badge->render('New');
echo $this->Badge->render('+12.5%', ['variant' => 'outline', 'icon' => 'trending-up']);
echo $this->Badge->render('Docs', ['url' => '/docs', 'variant' => 'link']);
```

### Alert
```php
echo $this->Alert->render('Export scheduled', 'Your workspace data will be ready in a few minutes.', ['icon' => 'info']);
echo $this->Alert->render('Sync failed', ['Renew the connector token', 'Check the warehouse permissions'], ['variant' => 'destructive']);
echo $this->Alert->success('Saved');   // also error(), warning(), info() — configurable via `types`
```

### Card
```php
echo $this->Card->render($this->element('stats'), [
    'title' => 'Quarterly bookings',
    'description' => 'Q3 2026, all plans',
    'action' => $this->Badge->render('+9.4%', ['variant' => 'outline']),
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
    ['caption' => 'Invoices', 'empty' => 'No results.', 'escape' => false, 'footer' => ['Total', '', '$5,936.00']],
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
**Form layout.** Basecoat 1.1 styles each `.field` (label, control, help text, error) but has no
form-level class: the space between fields is up to the form. `create()` therefore adds a stacked
layout, the `formLayout` config (default `grid gap-6 [&>.btn]:justify-self-start`: fields 24px apart
and full width, a button placed directly in the form keeps its size), with this rule:

- no `class`, or a `class` without a layout (`'class' => 'w-full max-w-md'`): the layout is added
  next to it;
- a `class` that sets its own layout (a display utility such as `flex`, `inline-flex`, `grid`,
  `block`, `hidden`, also with a variant or important flag like `md:grid`, `!flex`, or
  `space-x-*` / `space-y-*`): it is kept as is, nothing is added (toolbars, `TableToolbar`,
  `list_filters`, the CRUD bake `form grid gap-5`). A breakpoint-only layout such as `sm:flex` also
  counts, so below that breakpoint the form is a plain block: write `grid gap-6 sm:flex` if you want
  both. Modifiers without a display (`flex-col`, `grid-cols-2`, `gap-4`) do not count;
- `'layout' => false` adds nothing; `'layout' => 'grid gap-4 sm:grid-cols-2'` is added instead of
  `formLayout` (even next to a layout class). `'formLayout' => false` in the helper config turns it
  off everywhere;
- `postButton()` forms get no layout (they stay a single inline button).

Selects rendered by `control()` inside the `.field` container also get `w-full` (`fieldWidgetClasses`,
default `['select' => 'w-full']`), so inputs, selects and textareas fill their field; a bare `select()`
or a control whose `inputContainer` template has no `.field` (e.g. `'{{content}}'` to place it in a
toolbar row) keeps Basecoat's content width. Group several buttons in a row with `<div class="flex gap-2">` (what
`submit()` renders). With a compiled Tailwind build, make sure it scans the plugin's `src/` so the
default layout classes are generated.

Config: `widgetClasses` (class per widget), `button` (default `variant`/`size`), `skipClassWidgets`,
`formLayout`, `fieldWidgetClasses`,
`nestedCheckboxAndRadio` (`false` by default: Basecoat styles a label that wraps its input as a
bordered "choice card", so single checkboxes/radios render input and label as siblings).
Pass `'basecoat' => false` to a control to skip the class. The control type goes in `data-type` on the
`.field` wrapper (not as a class: `textarea` / `select` are component classes and would style the wrapper). Errors render as `<p role="alert">` and
the field gets `data-invalid="true"`. Template-only alternative for the core helper:
`'templates' => 'BasecoatTheme.form_templates'`.

### Tabs
```php
echo $this->Tabs->render(['Usage' => $this->element('usage'), 'Exports' => $this->element('exports')]);
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
], ['align' => 'end', 'trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis', 'label' => 'Actions for ' . $project->name]]);
```
`trigger.label` names an icon-only trigger with visually hidden text inside the button (escaped).
In a table, make it unique per row ("Actions for Harbor & Pine").

### Sidebar
```php
echo $this->Sidebar->render($menu, ['header' => '...', 'footer' => '...', 'side' => 'left', 'open' => true]);
echo $this->Sidebar->toggle();
```
Item types: `item` (`label`, `url`, `icon`, `badge`, `current`), `group`, `submenu`, `separator` (`'-'`).
The current page gets `aria-current="page"` automatically (`currentMatch`: `exact` or `prefix`).

### Dialog
```php
echo $this->Dialog->render('digest-settings', $this->element('digest_form'), [
    'title' => 'Weekly digest',
    'description' => 'Choose who receives the summary email.',
    'trigger' => 'Digest settings',
    'footer' => $this->Dialog->close('Cancel') . $this->Button->submit('Save'),
]);
echo $this->Dialog->trigger('digest-settings', 'Open');
```

### Toast
```php
echo $this->Toast->toaster();   // already in the default layout
echo $this->Toast->render('Report exported', ['category' => 'info', 'description' => 'Churn forecast, November', 'action' => ['label' => 'Undo', 'url' => '/undo']]);
```

### Chart
```php
echo $this->Chart->render($rows, [
    'web' => ['label' => 'Dashboard signups', 'color' => 'var(--chart-1)'],
    'api' => ['label' => 'API signups', 'color' => 'var(--chart-2)'],
], ['type' => 'bar', 'labelKey' => 'month', 'class' => 'h-[250px] w-full', 'chartOptions' => [...]]);
```
Chart.js and the Basecoat chart adapter are loaded by the layout only when a chart was rendered.

### Dashboard components
```php
// Page title with description and actions
echo $this->PageHeader->render('Overview', [
    'description' => 'How your workspace is doing this month.',
    'actions' => [$this->DateRangeField->render('period'), $this->Button->render('Download report', ['icon' => 'download'])],
]);

// KPI card: change vs. previous period, optional sparkline (`chart`) or link (`url`)
echo $this->MetricCard->render('Active users', '12,486', ['change' => '+8.2%', 'trend' => 'up', 'description' => 'vs. October', 'icon' => 'users']);

// From/to dates in a popover with presets; inputs are `period[from]` / `period[to]` (DateRange list filter)
echo $this->DateRangeField->render('period', ['presets' => ['This month' => ['2026-11-01', '2026-11-30']]]);

// Filter bar above a table: a ListFilter (or its controls() array) plus actions
echo $this->TableToolbar->render(['filter' => $listFilter, 'actions' => $this->Button->link('New order', ['action' => 'add'])]);
// `hidden` keeps page context (folder, layout…) when filtering and in the "Clear" link
echo $this->TableToolbar->render(['filter' => $controls, 'hidden' => ['path' => $path, 'view' => $view]]);
// A control's `attributes` go on its input / select (escaped; name, type, id, value stay the helper's),
// e.g. the client-side filter hooks of basecoat-screens.js
echo $this->TableToolbar->render(['filter' => [
    'q' => ['type' => 'search', 'label' => 'Search', 'attributes' => ['data-filter-input' => '#rows']],
    'status' => ['type' => 'select', 'label' => 'Status', 'options' => $statuses, 'attributes' => ['data-filter-facet' => '#rows', 'data-filter-key' => 'status']],
]]);

// Command palette: trigger with ⌘K hint + dialog (Ctrl/⌘ + K opens it; `hotkey` changes the key,
// pass the same value to trigger() so the hint and aria-keyshortcuts match)
echo $this->CommandMenu->trigger('command');
echo $this->CommandMenu->render(['Pages' => [['label' => 'Orders', 'url' => ['controller' => 'Orders'], 'icon' => 'list']]], ['id' => 'command']);

// Workspace / team dropdown (e.g. in the `sidebarActions` view var)
echo $this->WorkspaceSwitcher->render([['name' => 'Fernway', 'plan' => 'Growth plan', 'url' => '/w/fernway']], ['addUrl' => '/workspaces/add']);

// Radio group as selectable cards
echo $this->ChoiceCards->render('plan', ['starter' => ['title' => 'Starter', 'description' => '3 projects', 'icon' => 'circle']], ['legend' => 'Plan', 'value' => 'starter']);

// Chat thread with a composer (plain POST form, CSRF token included)
echo $this->Conversation->render($messages, ['contact' => ['name' => 'Marta Vidal'], 'composer' => ['url' => ['action' => 'reply', $ticket->id]]]);
// One-to-one chat: each message `author` becomes sr-only text ("You", "Marta Vidal")
echo $this->Conversation->render($messages, ['hideAuthors' => true, 'composer' => false]);

// Team members with a role select (or badges with `readonly`)
echo $this->MemberList->render($members, ['roles' => ['owner' => 'Owner', 'member' => 'Member']]);

// Inbox rows: type icon or avatar, unread dot, badge, time; Accept/Decline for access requests
echo $this->NotificationList->render($notifications, [
    'types' => ['task' => 'Task', 'message' => 'Message', 'team' => 'Team', 'access' => 'Access'],
    'urls' => ['accept' => '/requests/{id}/accept', 'decline' => '/requests/{id}/decline'], // optional: POST forms with CSRF; without it the buttons are inert
]);
```
Notification keys: `id`, `type`, `title`, `body`, `time`, `unread`, `actor` (`name`, `avatar`), `url`,
`actions` (your own HTML instead of Accept / Decline), `attributes` (extra `<li>` attributes). Options:
`types`, `icons`, `variants`, `urls` (strings or route arrays, `{id}` is replaced), `empty`, `escape`;
config `actionTypes` (default `['access']`). Data hooks without patching the HTML: `itemAttributes`
(array, or `fn(array $notification, int $index): array`; `class` is appended), `dotAttributes` (unread
dot), `actionAttributes` (`['accept' => [...], 'decline' => [...]]`, inert buttons only: POST buttons of
`urls` are never changed), `actionsAttributes` (wraps the inert buttons in a `<span>` with these
attributes) and `footer` (`fn(array $notification, int $index): string`, HTML appended to the row's
footer). The `notifications` bake screen uses them for its client-side behaviour. Unread rows get a visually hidden "Unread" and `data-unread="true"`, so the state is never
shown by colour alone.
Behaviour (command menu hotkey, date presets) lives in `webroot/js/basecoat-theme.js`, no inline JS.

### Other components
```php
echo $this->Avatar->render('Elena Sarto', ['src' => $user->avatar, 'size' => 'lg']);
echo $this->Avatar->group($people, ['max' => 3]);
echo $this->Avatar->render($user->name, ['decorative' => true]) . h($user->name); // name printed next to it
echo $this->Accordion->render(['Can I export raw events?' => 'Yes, daily.'], ['multiple' => true]);
echo $this->Breadcrumb->render(['Home' => '/', 'Projects' => ['controller' => 'Projects'], 'Churn forecast'], ['collapse' => 4]);
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

### Icons and accessibility

The helpers follow the [Lucide accessibility guide](https://lucide.dev/how-to/accessibility):

- Icons are decorative: every SVG has `aria-hidden="true"` and sits next to text (button labels, menu
  items, badges, alerts).
- Icon-only controls are named on the control with visually hidden text: `Button->icon()`, the
  `DropdownMenu` `trigger.label`, `Sidebar->toggle()`, `Theme->toggle()`, the dialog close button and the
  pagination first/last links ("First page", "Last page"). Previous / Next keep their text for screen
  readers on small screens.
- Avatars next to a visible name are decorative: `Avatar->render($name, ['decorative' => true])` hides the
  initials (`aria-hidden`) and gives the image an empty `alt`, so the name is read once ("Kofi Mensah",
  not "KM Kofi Mensah"). A status `badge` stays exposed, so give it `sr-only` text.
- Meaning never depends on an icon or a colour alone: indicators, unread rows, metric trends and statuses
  carry text (visible or `sr-only`).
- One icon per meaning across the plugin, screens and catalog: `ellipsis` menus of actions (row, card
  and "more" menus) and collapsed items (breadcrumb, pagination), `settings-2` settings, `download`
  download/export, `check` done/read, `save` save, `plus` add, `pencil` edit, `trash` delete, `search`
  search. (`ellipsis-vertical` is still in `IconHelper` for your own code, the plugin no longer uses it.)
- Target size: Basecoat's icon buttons are 36px (`icon`) and 32px (`icon-sm`), which meets WCAG 2.2 AA
  (24px minimum); the theme keeps those sizes so it looks like Basecoat. Where you want the 44px
  target the Lucide guide suggests (touch-first screens), enlarge the control, not the icon:
  `$this->Button->icon('bell', 'Notifications', ['variant' => 'ghost', 'class' => 'size-11'])`, or
  `'size' => 'icon-lg'` (40px).

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

## Screens (bake)

Ready-made pages you bake into your app and then own: a controller whose `index()` sets **sample
data**, and an `index` template built only with the theme helpers. Bake one, open it, then replace
the sample data with your queries.

```bash
composer require --dev cakephp/bake                    # once
bin/cake bake screen --list                            # available screens
bin/cake bake screen users                             # UsersController + templates/Users/index.php
bin/cake bake screen notifications Inbox               # same screen, InboxController + templates/Inbox/index.php
bin/cake bake screen users Members --plugin Admin      # into plugins/Admin (namespace Admin\Controller)
bin/cake bake screen users --force                     # overwrite existing files without asking
```

- `<screen>`: a slug from `--list`. Built-in screens (default controller in brackets):
  - `users` (`Users`): user list with search, filters and a paginated table (row menu, bulk-select
    checkboxes).
  - `notifications` (`Notifications`): notification inbox with unread markers, search and filters, and
    Accept / Decline for access requests (`NotificationList`).
  - `chats` (`Chats`): two-pane messenger, conversation list with unread counts and statuses, message
    thread (`Conversation`) and a composer.
  - `ai-chat` (`Assistant`): assistant chat with prompt suggestions, answers with code blocks and a
    composer with a model picker.
  - `mail` (`Mail`): three-pane mailbox: folders and labels, message list with search and
    All / Unread tabs, reading pane with a reply box.
  - `todo` (`Tasks`): tasks grouped by due date with filter tabs, search, priorities, tags and assignees.
  - `kanban` (`Board`): kanban columns with cards (assignees, progress, due date) and the same cards as a
    table in a List tab.
  - `notes` (`Notes`): notes workspace with labels, search, a grid / list toggle and text, checklist and
    image notes.
  - `bookings` (`Bookings`): day schedule of classes per room and coach (one grid row per 30 minutes),
    previous / next day links (`?date=`) and a list view.
  - `calendar` (`Calendar`): month calendar with categorised events, today marker, `?month=` navigation
    and an upcoming list.
  - `profile` (`Profile`): user profile with cover, details, skills, activity timeline, projects and a
    settings form, in tabs.
  - `onboarding` (`Onboarding`): five-step setup flow with a step indicator and no-JS navigation
    (`?step=`).
  - `file-manager` (`Files`): storage overview, folders with breadcrumb (`?path=`), recent files as a
    grid or list, search and type filter.
  - `workflow` (`Workflows`): automation editor built on
    [sequential-workflow-designer](https://github.com/nocode-js/sequential-workflow-designer) (see
    [Workflow screen](#workflow-screen)): drag-and-drop steps with if / else branches, a step editor and
    the run history.
- `[<Controller>]`: controller name, default the screen's (see the list above); `team_inbox` and
  `TeamInbox` both give `TeamInboxController`.
- `--list`: lists the screens with their description and exits.
- `--force` (`-f`): overwrite files without prompting (otherwise bake asks before replacing a file).
- `--plugin` (`-p`): bake into a plugin (`Vendor/Name` allowed): files go under the plugin root and the
  namespace is the plugin's.
- Bake's other common options (`--theme`, `--connection`) are accepted but not used: screen templates
  always come from this plugin and no database is read.

Generated files (paths relative to the app, or to the plugin with `--plugin`):

- `src/Controller/<Controller>Controller.php`: extends your `AppController`; `index()` calls
  `$this->set()` once per view variable with the sample data.
- `templates/<Controller>/index.php`: the page. Its docblock lists every view variable and its shape.

The generated templates use the Basecoat helpers (`$this->PageHeader`, `$this->TableToolbar`,
`$this->NotificationList`…), so load them in your `AppView` with `loadBasecoatHelpers()` (see
[Loading the helpers](#loading-the-helpers)). If your app loads helpers one by one instead, add the ones
the screen uses (all `BasecoatTheme.*`; `Form` is `BasecoatTheme.Form`):

| Screen | Default controller | Helpers |
| --- | --- | --- |
| `users` | `Users` | `PageHeader`, `TableToolbar`, `Table`, `Avatar`, `Badge`, `DropdownMenu`, `Button`, `Form` |
| `notifications` | `Notifications` | `PageHeader`, `TableToolbar`, `Badge`, `Button`, `EmptyState`, `NotificationList` |
| `chats` | `Chats` | `Conversation`, `Avatar`, `Badge`, `Button`, `EmptyState`, `Form`, `Icon` |
| `ai-chat` | `Assistant` | `Card`, `Avatar`, `Button`, `Form`, `Icon` |
| `mail` | `Mail` | `Avatar`, `Badge`, `Button`, `EmptyState`, `Form`, `Icon` |
| `todo` | `Tasks` | `PageHeader`, `TableToolbar`, `Avatar`, `Badge`, `DropdownMenu`, `Button`, `EmptyState`, `Form`, `Icon` |
| `kanban` | `Board` | `PageHeader`, `Tabs`, `Table`, `Progress`, `Avatar`, `Badge`, `Button`, `EmptyState`, `Icon` |
| `notes` | `Notes` | `PageHeader`, `Button`, `EmptyState`, `Form`, `Icon` |
| `bookings` | `Bookings` | `PageHeader`, `Table`, `Badge`, `DropdownMenu`, `Button`, `EmptyState` |
| `calendar` | `Calendar` | `PageHeader`, `Badge`, `Button` |
| `profile` | `Profile` | `Tabs`, `Card`, `MetricCard`, `Progress`, `Avatar`, `Badge`, `Button`, `EmptyState`, `Form`, `Icon` |
| `onboarding` | `Onboarding` | `Card`, `ChoiceCards`, `Button`, `EmptyState`, `Form`, `Icon` |
| `file-manager` | `Files` | `PageHeader`, `Breadcrumb`, `TableToolbar`, `Progress`, `Avatar`, `DropdownMenu`, `Button`, `EmptyState`, `Icon` |
| `workflow` | `Workflows` | `PageHeader`, `Table`, `Badge`, `Button`, `EmptyState` (plus `Html` for the assets) |

Their strings use `__()` / `__n()` (`__d('<plugin>', …)` / `__dn('<plugin>', …)` with `--plugin`),
like the CRUD bake templates.

The generated code does not set the theme: set `BasecoatTheme` once in your `AppController` (see
[Use it as a theme](#use-it-as-a-theme)). Links to actions that are not generated (view, edit, invite,
settings…) are `#` with a `// TODO`; the search and filter forms send GET requests to `index`; Accept /
Decline do nothing until you pass `urls` (POST forms with the CSRF token). Buttons that would change
data (send, reply, archive, toggle a task, add a card…) are inert, with a `// TODO` showing how to wire
them to a POST action.

**Replacing the sample data:** keep the variable names and shapes from the template docblock and set
them from your models, e.g. in `UsersController::index()`:

```php
$query = $this->fetchTable('Users')->find()->orderBy(['name' => 'ASC']);
$this->set('users', $this->paginate($query));   // instead of the literal array
```

Then adapt the template where you need the real paging (`$this->Pagination->render()` with
`PaginatorHelper`) or your own filters.

`bake screen` is added by the plugin whenever `cakephp/bake` is installed, **even if you never load the
Bake plugin** (`bin/cake plugin load Bake` is not needed for it). Without cakephp/bake the command does
not exist. Every screen has a page in the
[catalog](https://delamux.github.io/cakephp-basecoat-theme/screens/index.html) with a live preview and
the exact files the command writes.

### Workflow screen

`bin/cake bake screen workflow` uses an external library,
**[sequential-workflow-designer](https://github.com/nocode-js/sequential-workflow-designer)** 0.40.4
(MIT, © N4NO.com; free edition only, no Pro features), restyled with the Basecoat tokens:

- **Assets:** the template adds the library from jsDelivr, pinned
  (`sequential-workflow-designer@0.40.4`: `css/designer.css`, `css/designer-light.css`,
  `css/designer-dark.css`, `dist/index.umd.js`), plus `BasecoatTheme.workflow` (`webroot/css/workflow.css`,
  served at `/basecoat_theme/css/workflow.css`), through the view `css` / `script` blocks, so the default
  layout prints them in `<head>` (HtmlHelper adds the CSP nonce). The CDN files carry Subresource
  Integrity hashes (`integrity="sha384-…" crossorigin="anonymous"`): update them with the version. If your layout does not fetch these
  blocks, add `<?= $this->fetch('css') ?>` and `<?= $this->fetch('script') ?>`. Self-host the files by
  changing `$designerCdn` in the template.
- **Mounting:** `webroot/js/basecoat-theme.js` (`pluginJs`) mounts the designer on
  `[data-workflow-designer]`. It reads the definition and the configuration from two
  `<script type="application/json">` data blocks (never executed). It keeps
  `<input type="hidden" name="definition">` up to date with the JSON of the definition and re-creates
  the designer in its `light` / `dark` theme when `.dark` changes on `<html>`, which resets the zoom, the
  selection and the undo history (if the new one fails, the previous designer stays). Designers whose
  container is removed (also `htmx:beforeCleanupElement`) are destroyed. The step editor and the workflow editor are Basecoat fields, built as
  DOM with text set through `textContent`. `window.BasecoatTheme.initWorkflows(root)` mounts designers
  added later (htmx content is handled).
- **Data:** `definition` is the library's format (`properties`, `sequence`; steps with `id`,
  `componentType` `task` or `switch` + `branches`, `type`, `name`, `properties`). `toolbox` lists the
  step templates (groups Triggers / Actions / Logic in the sample). Labels of the step properties in the
  editor come from the `fields` map in the template. "Save" and "Run test" are inert: the TODOs show how
  to post the `definition` input to your own action.
- **Without JavaScript** (or when the library fails to load or throws), the steps render as an ordered
  list with a nested list per branch (labelled by the branch name). The canvas and the "Drag steps…" hint
  (`data-workflow-hint`) stay `hidden`.
- **Accessibility:** the canvas is a labelled region, but it is not keyboard-operable: adding or moving
  steps needs a pointer (drag and drop), and steps on the canvas cannot take focus (library limitation).
  The keyboard path is the editor panel: the workflow editor lists every step as a button that selects
  it and opens its fields, "Back to workflow" returns, and Delete / Backspace remove the selected step
  when the focus is not in a field or another control. `basecoat-theme.js` gives the library's toolbar,
  toolbox header and editor toggle `role="button"`, `tabindex="0"`, a name and Enter / Space
  activation. It also names the toolbox search field.
- **CSP:** the library injects no `<style>` element, uses no `eval` and loads no images (the step
  markers are CSS-coloured). It sets positions through CSSOM (`element.style`), which CSP allows. The one
  exception is the ghost image while dragging a step: it is an `<svg>` with a `style` attribute (scale
  transform). Under a `style-src` without `'unsafe-inline'` (or `'unsafe-hashes'`), the browser blocks
  that attribute and logs a CSP violation on each drag. The drop still works, and the ghost is only drawn
  unscaled and 10px off. The CDN host must be allowed in `script-src` / `style-src`
  (`https://cdn.jsdelivr.net`), or self-host the files.

## Bake templates

`bin/cake bake` can generate CRUD screens with the theme components (Card, Table with sortable headers,
Pagination, DropdownMenu row actions, Breadcrumb, Badge, Dialog):

```bash
bin/cake bake all Articles --theme BasecoatTheme
# or once for every bake: Configure::write('Bake.theme', 'BasecoatTheme');
```

- `index`: card with the table (secondary columns hidden on narrow containers, `@container/main`), row menu
  (view, edit, delete) and pagination with counter; empty state with a "New" action.
- `view`: details card, a card per long text and the related records; Edit / Delete in `header_actions`.
- `add` / `edit`: two-column form in a card, breadcrumb, Save + Cancel.
- Deleting always asks in a `Dialog` (opened with `data-dialog-open`): no inline JS, works under a strict CSP.
- `useDomain` (plugins) is honoured: strings use `__d('<plugin>', ...)`.
- Secret columns (password, token, secret, salt, hash, api_key) and the entity's hidden fields are never listed.

Tests and entities use bake's default templates. The table and controller templates are copies of bake's
(cakephp/bake 3.8) that add the list search below when it applies.

### List search, filters and CSV export

With [friendsofcake/search](https://github.com/FriendsOfCake/search) installed and loaded (`composer require
friendsofcake/search`, `bin/cake plugin load Search`), `bake all` also generates:

- **Table:** the `Search` behavior and filters guessed from the columns, a starting point to edit:

  ```php
  $this->getBehavior('Search')->searchManager()
      ->add('q', 'BasecoatTheme.Contains', ['fields' => ['title', 'body'], 'label' => __('Search')])
      ->add('category_id', 'BasecoatTheme.Equals', ['label' => __('Category')])
      ->add('published', 'Search.Boolean', ['label' => __('Published')])
      ->add('created', 'BasecoatTheme.DateRange', ['label' => __('Created')]);
  ```

- **Controller:** `find('search', search: $this->request->getQueryParams())`, a `ListFilter` with the export
  columns and `?export=csv`.
- **List:** a filter bar above the results (the `BasecoatTheme.list_filters` element), an *Export CSV* button
  and a "No results" state.

How it behaves:

- Filters live in the query string (`?q=world&published=1&created[from]=2026-01-01`): a filtered list can be
  bookmarked or shared, and sorting/pagination keep the filters. The bar is a plain GET form (Filter button
  without JavaScript); with htmx it refreshes only `#list-results` while typing (400 ms) or changing a filter,
  and updates the URL.
- Theme filters (usable in any table with the `Search` behavior):
  - `BasecoatTheme.Contains`: every word must appear in some field, ignoring case on any database; `%` and `_`
    are plain characters. At most 5 words, 100 characters.
  - `BasecoatTheme.Equals`: `field = value` only when the value fits the column type (`?category_id=abc` on an
    integer or uuid column is ignored, never a database error); `options` restricts and shows the choices,
    otherwise a belongsTo key shows the related records (up to 500, by display field).
  - `BasecoatTheme.DateRange`: `field[from]` / `field[to]`, both days included; invalid dates are ignored.
- The bar shows `Contains` (search box), `Search.Boolean` (Yes/No), `Equals` (select) and `DateRange` (two
  dates); other filters still work from the URL. Labels come from the `label` option.
- **CSV** (`BasecoatTheme\Listing\ListFilter::csv()`): the filtered rows in the list order, read 500 at a time
  and streamed (UTF-8 with BOM for Excel, readable dates, Yes/No, enum labels, related display fields). Text
  starting with `= + - @` is prefixed with `'` so spreadsheets never run it as a formula. Secret and hidden
  columns are left out of the generated export.
- Without the Search plugin the bake templates generate the plain list, as before.

## Interactivity

Two optional scripts make screens interactive in place: **`webroot/js/basecoat-dnd.js`** (drag and drop:
sortable lists, kanban columns, 2D slot grids, bound text) and **`webroot/js/basecoat-screens.js`**
(filter, view switch, select-all + bulk bar, toggles and counts, remove, master-detail, composer,
stepper, drop zone). Vanilla JS, no dependencies, no build step; each file's header is the full
reference of its data hooks, the tables below summarise them.

- **Progressive enhancement:** markup works without JS (links, GET forms, POST forms); the scripts read
  `data-*` hooks, prevent the default and update the DOM in place. The baked screens keep their GET
  links and forms, so a page without the scripts (or with JS off) behaves as a classic server-rendered
  page.
- **Loading:** per template, through the view blocks (HtmlHelper adds the CSP nonce):

  ```php
  $this->Html->script('BasecoatTheme.basecoat-dnd', ['block' => true, 'defer' => true]);
  $this->Html->script('BasecoatTheme.basecoat-screens', ['block' => true, 'defer' => true]);
  $this->Html->css('BasecoatTheme.interactivity', ['block' => true]); // drag states, optional
  ```

  Both auto-initialize on `DOMContentLoaded` and on htmx `htmx:load` / `htmx:afterSwap`, are idempotent
  (loaded twice = one instance) and drop state of content removed by htmx (`htmx:beforeCleanupElement`).
  Content added by other means: `window.BasecoatDnd.init(root)` / `window.BasecoatScreens.init(root)`.
  Other API: `BasecoatDnd.cancel()`, `.announce(text)`; `BasecoatScreens.refreshCounts()`,
  `.applyFilters(root)`, `.announce(text)`.

### Drag and drop hooks (`basecoat-dnd.js`)

| Hook | Where | Meaning |
| --- | --- | --- |
| `data-dnd-container` | list / column | Drop target of list mode. `data-dnd-group="x"`: move between containers of the group. `data-dnd-label` (announced name), `data-id` / `data-dnd-id` |
| `data-dnd-axis="y\|x\|both"` | container | List direction (default `y`). `both`: wrapping grid or CSS columns: the pointer drops next to the nearest item (x and y), every arrow walks the reading order |
| `data-dnd-var-<name>="…"` | container | Extra value for bound text (`{name}`), e.g. `data-dnd-var-short="Tue 24 Nov"` |
| `data-dnd-item` | element that moves | Draggable. `data-id` (or `data-dnd-id`, `id`), `data-dnd-label` (announced; default: its text) |
| `data-dnd-handle` | inside an item | Only the handle starts a drag and takes focus; touch on it drags at once |
| `data-dnd-touch="press"` | container, item or handle | Touch on the handle needs the 250ms press too, so a swipe still scrolls the page (calendar chips) |
| `data-dnd-grid` | CSS grid | 2D mode. `data-dnd-classes='{"row": [...], "col": [...], "span": {"1": "row-span-1", ...}}'` (class per 0-based index / span), `data-dnd-rows`, `data-dnd-cols`, `data-dnd-resize-axis="row\|col"`, `data-dnd-span-max` |
| `data-dnd-row-text='["06:00", …]'` / `data-dnd-col-text='["Rig", …]'` | grid | Text of each row boundary (rows + 1 entries) and of each column: bound text `{start}`, `{end}`, `{col}` |
| `data-dnd-slot` + `data-dnd-row` / `data-dnd-col` | empty grid cells | Pointer drop targets; `data-dnd-label` names the slot ("Monday 09:00, Room A") |
| `data-dnd-row` / `data-dnd-col` / `data-dnd-span` | grid item | Current placement (0-based) |
| `data-dnd-resize` | inside a grid item | Drag handle that changes the span (also Shift + arrows) |
| `data-dnd-mirror="css selector"` | item | Other elements showing the item (a List view row, an "Upcoming" entry): their bound text follows the moves |
| `data-dnd-text="key"` | inside the item or a mirror | Text replaced after every placement change (also on revert / cancel). Grid keys: `start`, `end`, `col`, `row`, `span`; list keys: `container` (label of the new container), `container-id`, `position`, `total`, every `data-dnd-var-*`; both: `label` |
| `data-dnd-aria="Yoga, {start}–{end}, {col}"` | item, mirror or inside | Template filled in as `aria-label` after every change |
| `data-dnd-sort="{start}\|{col}"` | mirror | Sort key template: the mirror gets `data-dnd-sort-key` (render it server-side too) and is moved among its siblings that have one, in natural order (the List view stays sorted) |
| `data-dnd-i18n-<key>` | container or ancestor | Translated messages (see Translations) |

Grid items move by swapping their placement classes (no inline styles, nothing floats under the pointer;
list items are the real nodes moved between targets). Overlapping grid items are allowed: they get
`data-dnd-collision` and the overlap is announced. State attributes for styling: `data-dnd-ready`
(initialised container / item), `data-dnd-dragging` (item), `data-dnd-over` (target container),
`data-dnd-busy` (POST pending), `data-dnd-collision`, `html[data-dnd-active]`.

### Screen behaviour hooks (`basecoat-screens.js`)

| Behaviour | Hooks |
| --- | --- |
| Filter | `input[data-filter-input="#root"]` (Enter filters at once and never submits its no-JS form); facets `[data-filter-facet="#root"][data-filter-value="done\|!done\|[css]"]` (button / link, or a `<select>`); `data-filter-key="type"` on a facet: one facet per key, items must match every key (Status **and** Type); `[data-filter-item]` (`data-filter-text`, tokens from `data-state` + `data-filter-tags`), `[data-filter-group]`, `[data-filter-empty]`. Case and accent insensitive; hidden with `hidden` |
| Active controls | Facet and view controls get `aria-current` (links) or `aria-pressed` (buttons) plus `data-active-class` / `data-inactive-class` and `data-active-variant` / `data-inactive-variant` (the button's `data-variant`; `""` removes it) |
| View switch | `[data-view-switch="grid"][data-view-target="#root"]`; root `data-view`; `[data-view-panel="grid"]`; `[data-view-classes='{"grid": "...", "list": "..."}']`; links replace the URL |
| Select-all + bulk bar | `input[data-select-all="#scope"]`, `input[data-select-item]` (value = id), `[data-bulk-bar="#scope"]` with `[data-bulk-count]`, `[data-bulk-clear]`; rows get `data-selected`; select-all only affects visible rows |
| Toggle | `[data-toggle="done"]` (checkbox or button) in `[data-toggle-item]` (`data-id`), or anywhere with `data-toggle-target="#row"` → item `data-state="done"` (`data-toggle-attr`, `data-toggle-off`, `data-toggle-class`). `data-toggle-unset="data-unread"` (removed while on), `data-toggle-also="read answered"` (chained: also switched on, through their own control or as `data-<name>="true"`), `data-announce-on` / `data-announce-off="{label} starred."`, `data-toggle-focus="selector"` (focus target when the control hides itself; default: first control the toggle revealed, else the item). `[data-toggle-for="done"]` with `data-text-on/off`, `data-toggle-hidden="on\|off"`. `[data-toggle-all="read"][data-toggle-scope="#list"]` (+ `data-announce-on`) |
| Remove | `[data-remove-item="archive"]` removes `data-remove-target="#row"` or its closest `[data-toggle-item]` / `[data-filter-item]` / `[data-remove-target]`; focus goes to the next visible row; message `removed` (`data-screens-i18n-removed="{label} archived."`) |
| Counts | `[data-count-for="css selector"]` (`data-count-visible`), `data-count-offset="3"` (e.g. unread rows on other pages), `data-count-hide-zero[=".badge"]` (hides itself or that ancestor at 0); refreshed after every interaction |
| Master-detail | `[data-master="#pane"]` + `[data-master-item="template-id"]` (`data-label`): pane filled from the `<template>`, `aria-current`, `[data-master-clear]` removed, "opened" announced; `data-master-focus` moves focus to the pane; `data-master-empty="template-id"` is shown when the open row is removed. Pane controls with `data-toggle-target` show the row's current state |
| Composer | `form[data-composer="#log"][data-composer-template="tpl"]` (`[data-composer-text]`, `[data-composer-time]` in the template), `data-composer-reply="tpl"` (canned answer), `[data-composer-fill="text"]` suggestions (`data-composer-target`, `data-composer-send`); Enter sends, Shift+Enter new line |
| Stepper | `[data-stepper]` (`data-stepper-start`) with `[data-step]` panels (`data-step-title`), `[data-step-next]`, `[data-step-prev]`, `[data-step-go="n"]`, `[data-step-finish]`, `[data-step-indicator]`, `[data-step-current]`, `[data-step-total]`, `[data-step-progress]`; Next validates the panel first; a field with `aria-errormessage="id"` shows its message there (announced, `aria-invalid`, `aria-describedby`) instead of the browser bubble; `[data-bind="field other"]` (`data-bind-empty`, `data-bind-map='{"value": "Text"}'`) mirrors field values (a Review step) |
| Drop zone | `[data-dropzone]` with `input[type=file][data-dropzone-input]`, `[data-dropzone-list]` + `<template data-dropzone-item>` (`[data-dropzone-name]`, `[data-dropzone-size]`, `[data-dropzone-remove]` with `data-dropzone-remove-label="Remove {name}"`), `[data-dropzone-actions]` (`[data-dropzone-upload]`, `[data-dropzone-clear]`); zone `data-over` while dragging files. Files are only listed: Upload fires `basecoat:upload` |

### Events

Bubbling `CustomEvent`s; the cancelable ones revert the change when you call `preventDefault()`:

| Event | On | `detail` |
| --- | --- | --- |
| `basecoat:move` (cancelable) | item | list: `{mode: 'list', item, id, from, fromId, to, toId, index, fromIndex}`; grid: `{mode: 'grid', item, id, grid, row, col, span, from: {row, col, span}, collisions}` |
| `basecoat:resize` (cancelable) | grid item | same as a grid move |
| `basecoat:move-error` | item | move detail + `status`, `reason` (`'response'` or `'origin'`: POST failed or refused, change reverted) |
| `basecoat:filter` | filter root | `{query, facet, facets, visible, total}` (`facets`: key => value) |
| `basecoat:view` | view root | `{view}` |
| `basecoat:select` | scope | `{selected: [values], count}` |
| `basecoat:toggle` (cancelable) | item | `{item, id, name, on}` (chained toggles fire their own) |
| `basecoat:toggle-error` | item | toggle detail + `status`, `reason` (reverted) |
| `basecoat:remove` (cancelable) | item | `{item, id, action}` |
| `basecoat:remove-error` | item | remove detail + `status`, `reason` (item put back) |
| `basecoat:open` (cancelable) | master item | `{item, id, detail}` |
| `basecoat:send` (cancelable) | composer form | `{text, log, message}` |
| `basecoat:send-error` | composer form | send detail + `status`, `reason` (bubble gets `data-state="failed"`) |
| `basecoat:step` (cancelable) | stepper | `{from, to, total}` |
| `basecoat:upload` (cancelable) | drop zone | `{files: File[]}` — send them yourself (`FormData` + CSRF token) |

### Keyboard model

- **Drag and drop:** focus an item (or its handle; items without one get `tabindex="0"` and an
  `aria-describedby` hint) → **Space** or **Enter** picks it up (Space only when the item is a link) →
  **arrows** move it (lists: along the axis reorder, across it go to the previous / next container of the
  group; `data-dnd-axis="both"`: every arrow walks the reading order; **Home** / **End**) → **Space** /
  **Enter** drops, **Escape** (or Tab away) cancels. Grids: arrows move by one slot; **Shift + arrows**
  along the resize axis change the span, also without picking up. Focus stays on the moved item; Enter
  never follows a link item while dragging.
- **Screens:** every control is a native link, button, checkbox, select or input, so Tab / Enter / Space
  work as usual. Enter in a filter input filters instead of submitting. A control hidden by its own toggle
  ("+3 more", Accept, Mark as read) hands focus to what it revealed, else to its row. Removing a row moves
  focus to the next visible row (else the list). Opening a master item can move focus to the pane
  (`data-master-focus`); the stepper focuses the new step's heading, Next focuses the first invalid field.
- **Announcements:** one shared `aria-live="polite"` region (`[data-basecoat-announcer]`) for both
  scripts: drag steps, drops, cancels, overlaps and resizes; filter results; selection count; toggles with
  `data-announce-*`; removals; "opened"; step changes; inline errors; drop zone changes; save failures.
- `prefers-reduced-motion` turns off smooth scrolling, transitions and the composer reply delay.

### Touch model

Pointer Events for mouse, pen and touch. Mouse and pen drag after 4px. Touch on a `[data-dnd-handle]` or
`[data-dnd-resize]` drags after 4px (`touch-action: none` from `interactivity.css`); touch anywhere else on
an item, or on a handle under `data-dnd-touch="press"`, needs a **250ms press** without moving (moving
more than 8px first scrolls the page instead). During a drag the page does not scroll (non-passive
`touchmove`), the long-press callout / context menu is suppressed, and the item holds the pointer capture.
Near (40px) the edge of a scrollable ancestor or of the window the view auto-scrolls. **Escape**, a
`pointercancel` or leaving the window cancels a pointer drag (the item goes back, the click is swallowed).
Give touch screens a visible, named handle where an item has no natural one.

**Ids:** the `id` in events and POSTs is `data-id`, else `data-dnd-id`, else the element `id` — the same
lookup in both scripts, so one `data-id` serves a card that is both draggable and toggleable.

**Translations:** messages come from `data-dnd-i18n-<key>` / `data-screens-i18n-<key>` attributes on the
element or an ancestor, or from a JSON block
`<script type="application/json" data-dnd-i18n>{"dropped": "Soltado {label}…"}</script>`
(`data-screens-i18n` for the screens script). Keys and placeholders are listed in `MESSAGES` at the top of
each file — dnd: `instructions`, `grid-instructions`, `picked-up`, `moved`, `dropped`, `cancelled`,
`failed`, `grid-picked-up`, `grid-moved`, `grid-dropped`, `grid-cancelled`, `resized`, `collision`,
`slot`, `list`; screens: `results`, `step`, `removed`, `failed`, `send-failed`, `selected`, `opened`,
`files-added`, `file-removed`, `files-cleared`. Pass them through `__()` in your template.

### Endpoints and CSRF

Without `data-endpoint` everything is client-only (the baked screens and the catalog previews). Add
`data-endpoint="<?= $this->Url->build(['action' => 'move']) ?>"` to a container, item, form or an
ancestor to also POST each change as JSON — `{action: 'move', id, from, to, index, fromIndex}` (lists),
`{action: 'move'|'resize', id, grid, row, col, span}` (grids), `{action: 'toggle', id, name, on}`,
`{action: 'archive', id}` (the `data-remove-item` value), `{action: 'send', text}` — with `X-CSRF-Token`
taken from `data-csrf-token` on an ancestor, `<meta name="csrfToken">` or the page's
`input[name=_csrfToken]` (CakePHP's `CsrfProtectionMiddleware` reads the header).

**Origin rule:** the endpoint is resolved against `document.baseURI` and must be a **same-origin**
`http(s)` URL: anything else (another host, `//host`, `javascript:`, `data:`) is refused with a
`console.error`, so the CSRF token never leaves the site. A refused endpoint (`reason: 'origin'`), a
non-2xx answer or a network error (`reason: 'response'`) reverts the change, announces it and fires the
matching `*-error` event. While a toggle is being saved its item has `data-busy` / `aria-busy` and ignores
further toggles and removals. Chained toggles (`data-toggle-also`) are posted one by one. Uploads are never
sent by the script (`basecoat:upload`). In the controller:
`$this->request->allowMethod('post'); $data = $this->request->getData();` (JSON bodies are parsed by
`BodyParserMiddleware`).

### CSP

Both scripts are files (no inline code, no `eval`, no `innerHTML`: text through `textContent`, markup
cloned from `<template>`) and set no `style` attribute: drag feedback is attributes styled by
`interactivity.css`, grid moves swap classes, visibility uses `hidden`. They work under
`script-src 'self'` / `style-src 'self'` plus the nonce for your other inline blocks; the JSON message
blocks (`<script type="application/json">`) are data and need no nonce. Classes listed in
`data-dnd-classes` / `data-view-classes` / `data-active-class` must exist in your CSS (with the Tailwind
browser build they are generated on the fly; with a compiled build make sure your templates are scanned).

## Content Security Policy

Nothing the theme renders needs `'unsafe-inline'`, so it works under a strict CSP (CakePHP `CspMiddleware`):

- Interactivity uses data attributes handled by `webroot/js/basecoat-theme.js` (loaded by `Theme->head()` through the
  `pluginJs` option) with event delegation: `data-theme-toggle`, `data-sidebar-toggle`, `data-dialog-open`,
  `data-dialog-close`, `data-dialog-backdrop-close`, `data-alert-dismiss`, `data-history-back`, progress bars
  (`data-progress-value`, width set through CSSOM instead of a style attribute) and flash toasts as
  `<template data-basecoat-toast>`. Keep `pluginJs` enabled.
- The dark-mode script and the Tailwind browser `@theme` style carry the request's nonce (`cspScriptNonce` /
  `cspStyleNonce`); HtmlHelper adds it to the scripts and stylesheets.
- Charts, toasts and progress bars are initialized again in content swapped by htmx (`htmx:load`).
- The `workflow` bake screen loads sequential-workflow-designer from jsDelivr; while a step is dragged the
  library sets a `style` attribute on the drag ghost, blocked (and reported) under a strict `style-src`
  (see [Workflow screen](#workflow-screen)).
- The interactivity scripts (`basecoat-dnd.js`, `basecoat-screens.js`) need no inline script or style
  attribute (see [CSP](#csp) under Interactivity).
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

`templates/element/examples/dashboard.php` is a dashboard built with the helpers (stat cards, area
chart, tabs with a data table and row menus), with invented data from the catalog's fictional *Fernway*:

```php
<?= $this->element('BasecoatTheme.examples/dashboard') ?>
```

## Component catalog

The catalog (<https://delamux.github.io/cakephp-basecoat-theme/>) is a static site generated from
`docs/catalog/examples/<helper>/*.php` with the real helpers, and deployed to GitHub Pages on every push
to `main`. Besides one page per helper and a demo dashboard, its **Screens** section
(`screens/index.html`) has a page per bake screen with the `bin/cake bake screen …` command, a live
preview (`screens/<screen>/preview.html`, also "Open full page") and the exact baked controller and
template. Build and preview it with the commands in [Scripts and options](#scripts-and-options).

## Translations

UI strings use the `basecoat_theme` domain. Spanish is included (`resources/locales/es`).

## Scripts and options

### Tests: `composer test`

```bash
composer install
composer test                                   # = phpunit --colors=always (phpunit.xml.dist)
composer test -- --filter ButtonHelperTest      # one test class (or a method: --filter testIconButton)
vendor/bin/phpunit tests/TestCase/Bake          # one folder
```

Arguments after `--` go to PHPUnit. The bake screen tests write only to a temporary folder under `TMP`,
never into the test app.

### JS unit tests: `composer test-js`

```bash
composer test-js                                # = node --test "tests/js/*.test.js" (Node 22+)
```

Dependency-free `node:test` tests for the pure helpers of `basecoat-dnd.js` and `basecoat-screens.js`
(index math, class maps, collisions, filtering, step states). The scripts export them with
`module.exports` when loaded by Node and do nothing else outside a browser.

### Catalog: `composer docs` / `php bin/build-gh-pages`

```bash
composer docs                                   # = php bin/build-gh-pages, output in build/gh-pages
composer docs -- --strict                       # what CI and the Pages deploy run
composer docs -- --out=build/preview            # another folder (same as php bin/build-gh-pages --out=build/preview)
```

- `--out=<dir>`: output folder, default `build/gh-pages`. Relative paths start at the repository root;
  absolute paths are allowed. It **must be a folder strictly inside `build/`** (`build/` itself, paths
  outside it and symlinked folders are refused) because it is **emptied before every build**.
- `--strict`: also fail (exit 1) on warnings: a helper of `docs/catalog/helpers.php` without examples or
  whose class is missing, a registered screen without `template.twig` / `data.php`. Without it the
  warnings are printed and the build succeeds.
- An example, a screen or the home page that throws always fails the build (exit 1), strict or not.

All URLs in the site are relative, so it works from any sub-path. To preview it as GitHub Pages
serves it (under `/cakephp-basecoat-theme/`), copy it into a folder with that name and serve the parent:

```bash
composer docs
mkdir -p /tmp/catalog
cp -R build/gh-pages /tmp/catalog/cakephp-basecoat-theme   # use an empty /tmp/catalog
php -S localhost:8080 -t /tmp/catalog
# open http://localhost:8080/cakephp-basecoat-theme/
```

For a quick look, `php -S localhost:8080 -t build/gh-pages` serves it at the root.

### Screens: `bin/cake bake screen`

`bin/cake bake screen <screen> [<Controller>] [--list] [--force] [--plugin <Plugin>]`, run in your app;
see [Screens (bake)](#screens-bake).

### CRUD bake: `bin/cake bake … --theme BasecoatTheme`

```bash
bin/cake bake all Articles --theme BasecoatTheme          # model, controller and templates
bin/cake bake template Articles --theme BasecoatTheme     # only the templates
```

Or set the theme once for every bake (e.g. in `config/bootstrap.php`):
`Configure::write('Bake.theme', 'BasecoatTheme');`. With friendsofcake/search installed the list also
gets filters and an *Export CSV* link: see
[List search, filters and CSV export](#list-search-filters-and-csv-export).

## Upgrade notes

Changes in this version that can affect existing apps:

- **New optional scripts and stylesheet.** `webroot/js/basecoat-dnd.js`, `webroot/js/basecoat-screens.js`
  and `webroot/css/interactivity.css` (see [Interactivity](#interactivity)); nothing loads them unless a
  template asks. The catalog build now copies every `webroot/js/*.js` to `assets/` and to
  `basecoat_theme/js/` (their plugin asset URL).
- **Interactive bake screens.** Re-baked screens load those scripts and carry `data-*` hooks (no endpoint
  by default; TODO comments show where to set `data-endpoint`). Changes to watch when you re-bake: the
  `kanban` List view column and the `calendar` "Upcoming" entries follow drags through `data-dnd-mirror`
  (no `basecoat-kanban.js` / `basecoat-dropzone.js`: those intermediate files are gone, the drop zone is
  part of `basecoat-screens.js` and its messages are `data-screens-i18n-files-added` / `-file-removed` /
  `-files-cleared`); the `notifications` screen filters Status **and** Type client-side, its Accept /
  Decline POST URLs move to a `$urls` variable at the top of the template, and its badge counts unread
  rows of other pages through `data-count-offset`; the `onboarding` review lists the invited emails
  instead of a count; `bookings` List rows carry `data-dnd-sort` keys; `mail` rows get `id="mail-row-N"`
  and the reading pane gets working Star / Archive / Delete buttons.
- **`TableToolbar` control `attributes`** and **`NotificationList` `itemAttributes`, `dotAttributes`,
  `actionAttributes`, `actionsAttributes`, `footer`** (and a per-row `attributes` key): new, optional;
  output is unchanged without them. A `NotificationList` row given `actionsAttributes` renders its inert
  Accept / Decline inside a `<span>` (template `actionGroup`).

- **Forms get a stacked layout by default.** `Form->create()` adds `grid gap-6 [&>.btn]:justify-self-start`
  (config `formLayout`) when the `class` option sets no layout of its own, so fields are evenly spaced
  and full width instead of touching each other. Forms whose `class` already has `flex`, `grid`,
  `inline-flex`, `space-y-*`… are unchanged. To keep the old output pass `'layout' => false` to
  `create()`, or set `'formLayout' => false` on the helper. A form placed in a flex row (an inline GET
  search box without a class) now becomes a grid: give it its own layout class or `'layout' => false`.
  `postButton()` forms are not affected.
- **Selects in a `.field` are full width.** `control()` renders selects with `select w-full`
  (`fieldWidgetClasses`); set `'fieldWidgetClasses' => ['select' => '']` for the content width.

- **Icon-only controls are named with visually hidden text instead of `aria-label`.** `Button->icon()`,
  `Sidebar->toggle()`, `Theme->toggle()` and the dialog close button (`Dialog` `closeButton`) now render
  the label inside the control as `<span class="sr-only">…</span>` (icon `aria-hidden`) and no longer
  add `aria-label`. An explicit `aria-label` option passed to `Button->icon()`, `Sidebar->toggle()` or
  `Theme->toggle()` is still rendered and wins as the accessible name. Tests or CSS that looked for
  `aria-label` on these controls need updating. `Button->icon()` uses the new `iconLabel` template
  (`<span class="sr-only">{{label}}</span>`); with an `indicator`, its text joins the same span
  ("Notifications, 3 new").
- **`Theme.templates.toggleContent`:** the default is now
  `<span class="hidden dark:inline">{{sun}}</span><span class="dark:hidden">{{moon}}</span><span class="sr-only">{{label}}</span>`.
  If you override this template, add the `{{label}}` placeholder (in an `sr-only` element), otherwise
  the toggle has no accessible name.
- **`DropdownMenu` `trigger.label`:** new option that names an icon-only trigger with visually hidden
  text (escaped), e.g. `'trigger' => ['icon' => 'ellipsis', 'label' => 'Actions for ' . $name]`.
  It replaces passing `aria-label` in `trigger`. The CRUD bake `index` template now uses it
  (`'Actions for {0}'` with the record's display field).
- **Avatars next to a name are hidden from screen readers.** New `Avatar` option `decorative` (default
  `false`, output unchanged without it). `Conversation` (header), `MemberList`, `WorkspaceSwitcher`, the
  sidebar user menu and `NotificationList` (when the title contains the actor's name) now render their
  avatar with it: the initials get `aria-hidden="true"` and an avatar image gets `alt=""`. Tests that
  matched `<span>KM</span>` in these helpers need updating.
- **Menu triggers use `ellipsis`** instead of `ellipsis-vertical`: the CRUD bake `index` template, the
  `users` screen, the sidebar user menu and the catalog examples. Re-bake or change the icon name if you
  want the new look; `ellipsis-vertical` is still available.
- **New `workflow` bake screen** (sequential-workflow-designer, MIT, loaded from jsDelivr by the baked
  template only): `basecoat-theme.js` now mounts `[data-workflow-designer]` elements when the library is
  on the page (`window.BasecoatTheme.initWorkflows`), and watches the `class` of `<html>` to switch the
  designer theme. Pages without the screen are unaffected. New plugin stylesheet `webroot/css/workflow.css`
  (`BasecoatTheme.workflow`) and the `workflow` and `play` icons. The catalog build copies the plugin
  stylesheets to `basecoat_theme/css/`.
- **`Button` `indicatorLabel`:** `''` renders no screen reader text for the indicator.
- **`Pagination`:** the first/last icon links are named "First page" / "Last page"; Previous / Next
  text is `sr-only sm:not-sr-only` (was `hidden sm:block`), so screen readers always get it.
- **New:** the `NotificationList` helper (in `BasecoatHelpers::MAP`, loaded by `loadBasecoatHelpers()`;
  apps that load helpers one by one add `BasecoatTheme.NotificationList`), the `Conversation`
  `hideAuthors` option, the `app-window`, `save`, `shield-alert` and `file-pen` icons, and the
  [`bin/cake bake screen`](#screens-bake) command (registered when cakephp/bake is installed).

## License

MIT. Basecoat (MIT) and Lucide icons (ISC) belong to their authors. The `workflow` bake screen loads
sequential-workflow-designer (MIT, © N4NO.com) from jsDelivr; it is not bundled with this plugin.
