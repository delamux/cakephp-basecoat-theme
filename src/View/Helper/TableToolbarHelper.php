<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use BasecoatTheme\Listing\ListFilter;

/**
 * Toolbar above a table: the controls of a `ListFilter` (search box, selects, date range) in a
 * GET form, plus an area for actions (buttons) on the right.
 *
 * ```
 * echo $this->TableToolbar->render([
 *     'filter' => $list,                       // ListFilter or its controls() array
 *     'actions' => $this->Button->link('New order', ['action' => 'add']),
 *     'htmx' => ['target' => 'list-results'],  // optional: refresh only the results
 * ]);
 * ```
 *
 * Renders the same controls and `hx-*` attributes as the `BasecoatTheme.list_filters` element, with
 * `dateRange` controls shown as a `DateRangeField` popover (same `field[from]` / `field[to]`).
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\FormHelper $Form
 * @property \BasecoatTheme\View\Helper\DateRangeFieldHelper $DateRangeField
 */
class TableToolbarHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Form', 'BasecoatTheme.DateRangeField'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => 'flex flex-wrap items-end gap-3',
        'attributes' => [],
        'classes' => [
            'search' => 'w-full sm:w-64',
            'control' => 'w-full sm:w-auto',
            'buttons' => 'flex gap-2',
            'actions' => 'ml-auto flex flex-wrap items-center gap-2',
        ],
    ];

    /**
     * Renders the toolbar; an empty string when there is nothing to show.
     *
     * ### Options
     *
     * - `filter`: `ListFilter` or a `controls()`-shaped array. A control may have `attributes`: extra HTML
     *   attributes of its input / select (escaped; `class` is appended; `name`, `type`, `id` and `value`
     *   stay the helper's), e.g. the data hooks of `basecoat-screens.js`:
     *   `'q' => ['type' => 'search', 'label' => 'Search', 'attributes' => ['data-filter-input' => '#rows']]`.
     *   `dateRange` controls ignore it.
     * - `actions`: HTML shown on the right.
     * - `url`: Form action (default current path).
     * - `submit`: Submit button text (default `Filter`).
     * - `htmx`: `false` or `['target' => 'results-id']`.
     * - `hidden`: Extra hidden fields submitted with the filters (name => value), e.g. the current folder
     *   or layout of the page; empty values are skipped. The "Clear" link keeps them too.
     * - `clear`: "Clear" link that resets the filters (keeping `sort` / `direction`). Default: shown for a
     *   `ListFilter` that is filtered, off for a controls array. `true` shows it (current URL), a string or
     *   array is the URL to link to (an existing query string is kept and merged), `false` hides it.
     *
     * Control labels, select options and the submit text are always escaped (there is no `escape`
     * option); `actions` is HTML and is output as is.
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $options = []): string
    {
        $filter = $options['filter'] ?? [];
        $controls = $filter instanceof ListFilter ? $filter->controls() : (array)$filter;
        $actions = (string)($options['actions'] ?? '');
        if ($controls === [] && $actions === '') {
            return '';
        }

        $request = $this->getView()->getRequest();
        $query = $request->getQueryParams();
        $url = (string)($options['url'] ?? $request->getPath());

        $formOptions = [
            'type' => 'get',
            'url' => $url,
            'valueSources' => ['query'],
            'role' => 'search',
        ] + $this->rootAttributes([], $options, ['filter', 'actions', 'url', 'submit', 'htmx', 'clear', 'hidden']);
        $hidden = array_filter(
            array_map(fn(mixed $value): string => is_scalar($value) ? (string)$value : '', (array)($options['hidden'] ?? [])),
            fn(string $value): bool => $value !== '',
        );
        $htmx = $options['htmx'] ?? false;
        if (is_array($htmx) && !empty($htmx['target'])) {
            $target = '#' . ltrim((string)$htmx['target'], '#');
            $formOptions += [
                'hx-get' => $url,
                'hx-target' => $target,
                'hx-select' => $target,
                'hx-swap' => 'outerHTML',
                'hx-push-url' => 'true',
                'hx-trigger' => 'input changed delay:400ms from:find input[type=search], change, submit',
            ];
        }

        $out = $this->Form->create(null, $formOptions);
        foreach (['sort', 'direction'] as $param) {
            if (isset($query[$param]) && is_string($query[$param])) {
                $out .= $this->Form->hidden($param, ['value' => $query[$param]]);
            }
        }
        foreach ($hidden as $name => $value) {
            $out .= $this->Form->hidden((string)$name, ['value' => $value]);
        }

        foreach ($controls as $name => $control) {
            $name = (string)$name;
            $type = (string)($control['type'] ?? 'text');
            $label = (string)($control['label'] ?? $name);
            $wrapper = $type === 'search' ? 'search' : 'control';
            $extra = $this->controlAttributes($control);
            $out .= '<div class="' . h((string)$this->getConfig('classes.' . $wrapper)) . '">';
            if ($type === 'search') {
                $out .= $this->Form->control($name, $extra + [
                    'type' => 'search',
                    'label' => ['text' => $label, 'class' => 'sr-only'],
                    'placeholder' => __d('basecoat_theme', 'Search…'),
                    'autocomplete' => 'off',
                    'maxlength' => 100,
                    'required' => false,
                ]);
            } elseif ($type === 'dateRange') {
                $out .= $this->DateRangeField->render($name, ['label' => $label]);
            } elseif ($type === 'select') {
                $out .= $this->Form->control($name, $extra + [
                    'type' => 'select',
                    'label' => $label,
                    'options' => $control['options'] ?? [],
                    'empty' => __d('basecoat_theme', 'All'),
                    'required' => false,
                ]);
            } else {
                $out .= $this->Form->control($name, $extra + ['type' => 'text', 'label' => $label, 'required' => false]);
            }
            $out .= '</div>';
        }

        if ($controls !== []) {
            $clear = $options['clear'] ?? ($filter instanceof ListFilter ? $filter->isFiltered() : false);
            $link = '';
            if ($clear !== false && $clear !== null) {
                $link = '<a href="' . h($this->clearUrl($clear, $url, $query, $hidden)) . '" class="btn" data-variant="ghost">'
                    . h(__d('basecoat_theme', 'Clear')) . '</a>';
            }
            $out .= '<div class="' . h((string)$this->getConfig('classes.buttons')) . '">'
                . $this->Form->button(
                    (string)($options['submit'] ?? __d('basecoat_theme', 'Filter')),
                    ['type' => 'submit', 'variant' => 'secondary'],
                )
                . $link
                . '</div>';
        }
        if ($actions !== '') {
            $out .= '<div class="' . h((string)$this->getConfig('classes.actions')) . '">' . $actions . '</div>';
        }

        return $out . $this->Form->end();
    }

    /**
     * The `attributes` of a control, without the keys the helper owns.
     *
     * @param array<string, mixed> $control Control.
     * @return array<string, mixed>
     */
    private function controlAttributes(array $control): array
    {
        $attributes = (array)($control['attributes'] ?? []);
        foreach (['name', 'type', 'id', 'value', 'label', 'options', 'empty', 'templates', 'templateVars', 'escape', 'val', 'default'] as $owned) {
            unset($attributes[$owned]);
        }

        return $attributes;
    }

    /**
     * @param mixed $clear The `clear` option (`true`, URL string or URL array).
     * @param string $url Form action.
     * @param array<string, mixed> $query Current query string.
     * @param array<string, string> $hidden The `hidden` fields (kept).
     * @return string URL without the filters; `sort` / `direction` and the hidden fields are kept.
     */
    private function clearUrl(mixed $clear, string $url, array $query, array $hidden = []): string
    {
        $keep = array_intersect_key($query, array_flip(['sort', 'direction'])) + $hidden;
        if (is_array($clear)) {
            $clear['?'] = $keep + (array)($clear['?'] ?? []);

            return $this->Url->build($clear, ['escape' => false]);
        }
        $base = is_string($clear) ? $clear : $url;
        if ($keep === []) {
            return $this->Url->build($base, ['escape' => false]);
        }

        $fragment = '';
        $hash = strpos($base, '#');
        if ($hash !== false) {
            $fragment = substr($base, $hash);
            $base = substr($base, 0, $hash);
        }
        [$path, $existing] = explode('?', $base, 2) + [1 => ''];
        parse_str($existing, $params);

        return $this->Url->build($path, ['escape' => false]) . '?' . http_build_query($keep + $params) . $fragment;
    }
}
