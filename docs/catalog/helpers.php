<?php
declare(strict_types=1);

/**
 * Catalog registry: one entry per helper, keyed by slug (kebab-case of the alias).
 *
 * The slug is the examples folder (`docs/catalog/examples/<slug>/`) and the page name
 * (`components/<slug>.html`). Groups are listed in navigation order.
 *
 * @return array<string, array{alias: string, title: string, group: string, intro: string}>
 */
$groups = [
    'Layout' => [
        'Sidebar' => ['Sidebar', 'Collapsible app navigation with groups, submenus, a header and a footer slot. Items matching the current request are marked as current.'],
        'Breadcrumb' => ['Breadcrumb', 'Shows where the current page sits in the navigation hierarchy, with optional collapsed middle items.'],
        'Card' => ['Card', 'Container with an optional header (title, description, action), body and footer. Also works in capture mode for long bodies.'],
        'Tabs' => ['Tabs', 'Switches between panels of related content in place, with keyboard navigation from Basecoat JS.'],
        'Accordion' => ['Accordion', 'Stacked sections that expand and collapse, built on native details elements.'],
        'PageHeader' => ['Page header', 'Page title with an optional description, breadcrumb and a slot for actions such as buttons or a date range.'],
        'WorkspaceSwitcher' => ['Workspace switcher', 'Dropdown to switch between workspaces or teams, showing the logo, name and plan, with an action to add a new one.'],
        'CommandMenu' => ['Command menu', 'Search trigger with a keyboard hint that opens a dialog with a filterable list of grouped actions and links.'],
    ],
    'Forms' => [
        'Form' => ['Form', 'Drop-in replacement of the core FormHelper that renders Basecoat fields, labels, help text and errors.'],
        'Button' => ['Button', 'Buttons and button-styled links with variants, sizes, icons and icon-only buttons with an accessible label.'],
        'ButtonGroup' => ['Button group', 'Joins related buttons into a single control.'],
        'ChoiceCards' => ['Choice cards', 'Radio group rendered as selectable cards with an icon, a title and an optional description.'],
        'DateRangeField' => ['Date range field', 'From and to dates in a popover with native date inputs and quick presets, compatible with the DateRange list filter.'],
        'Flow' => ['Flow editor', 'Steps linked by connections on a canvas (React Flow, bundled with the plugin): drag, connect, add from a palette and edit in a side panel; a list without JavaScript.'],
    ],
    'Data display' => [
        'Table' => ['Table', 'Tables from a column list and rows, with captions and sortable headers driven by the core PaginatorHelper.'],
        'TableToolbar' => ['Table toolbar', 'Bar above a table with a filter input, optional selects and actions. Accepts the controls of a ListFilter.'],
        'Pagination' => ['Pagination', 'Page links for paginated lists, with first, last, previous and next buttons, a counter and a previous/next only mode.'],
        'Badge' => ['Badge', 'Small label for statuses, counts and tags, with variants, icons and link badges.'],
        'Avatar' => ['Avatar', 'User picture with initials as fallback, in several sizes and as a stacked group.'],
        'MemberList' => ['Member list', 'List of people with avatar, name and email, and a role select or badge per row.'],
        'NotificationList' => ['Notification list', 'Inbox rows with a type icon or avatar, unread dot, body, type badge and time, plus inline Accept and Decline for requests.'],
        'Kbd' => ['Kbd', 'Keyboard keys and shortcuts.'],
        'Chart' => ['Chart', 'Chart.js charts (bar, line and more) themed with the Basecoat colors, built from plain arrays.'],
        'MetricCard' => ['Metric card', 'KPI card with a label, a value, the change versus the previous period and an optional sparkline.'],
        'Icon' => ['Icon', 'Inline SVG icons from a built-in set, extendable with your own.'],
    ],
    'Feedback' => [
        'Alert' => ['Alert', 'Callout for important messages, with variants, an icon and an optional dismiss button.'],
        'Toast' => ['Toast', 'Short notifications stacked in a toaster, rendered server-side or from flash messages.'],
        'Progress' => ['Progress', 'Progress bar for a known completion value.'],
        'Skeleton' => ['Skeleton', 'Placeholder blocks shown while content loads.'],
        'EmptyState' => ['Empty state', 'Message shown when there is no data yet, with an icon, a description and actions.'],
        'Conversation' => ['Conversation', 'Message thread with a header, sent and received bubbles and a composer form.'],
    ],
    'Overlays' => [
        'Dialog' => ['Dialog', 'Modal dialog on the native dialog element, with a trigger, header, body and footer.'],
        'DropdownMenu' => ['Dropdown menu', 'Menu of actions or links opened from a button, with groups, separators and shortcuts.'],
        'Tooltip' => ['Tooltip', 'CSS-only hint shown on hover or focus of an element.'],
    ],
    'Theme' => [
        'Theme' => ['Theme', 'Loads the Basecoat CSS and JS from the CDN, applies dark mode before first paint and renders the light/dark toggle.'],
    ],
];

$registry = [];
foreach ($groups as $group => $helpers) {
    foreach ($helpers as $alias => [$title, $intro]) {
        $slug = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '-$0', $alias));
        $registry[$slug] = ['alias' => $alias, 'title' => $title, 'group' => $group, 'intro' => $intro];
    }
}

return $registry;
