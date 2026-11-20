<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Table (`.table` inside an optional `.table-container`).
 *
 * ```
 * echo $this->Table->render(
 *     ['customer' => 'Customer', 'status' => 'Status', 'amount' => ['label' => 'Amount', 'class' => 'text-right']],
 *     [
 *         ['customer' => 'Quillon Studio', 'status' => $this->Badge->render('Paid'), 'amount' => '$96.00'],
 *     ],
 *     ['caption' => 'Recent invoices', 'escape' => false],
 * );
 *
 * // Sortable headers (uses the core PaginatorHelper). They show a sort indicator
 * // (neutral / ascending / descending) and the active column gets `aria-sort`.
 * echo $this->Table->render(['customer' => ['label' => 'Customer', 'sort' => true]], $rows);
 * ```
 *
 * @property \Cake\View\Helper\PaginatorHelper $Paginator
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class TableHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Paginator', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'container' => true,
        'empty' => null,
        // Icons of sortable headers per state, or false to render plain sort links.
        'sortIcons' => [
            'none' => 'chevrons-up-down',
            'asc' => 'arrow-up',
            'desc' => 'arrow-down',
        ],
        'classes' => [
            'sortLabel' => 'inline-flex items-center gap-1',
            'sortIcon' => 'size-3.5 shrink-0 text-muted-foreground',
            'sortIconActive' => 'size-3.5 shrink-0',
            'container' => null,
            'caption' => null,
            'head' => null,
            'body' => null,
            'foot' => null,
            'row' => null,
            'th' => null,
            'td' => null,
            'empty' => 'text-center text-muted-foreground',
        ],
        'templates' => [
            'container' => '<div{{attrs}}>{{content}}</div>',
            'table' => '<table{{attrs}}>{{caption}}{{head}}{{body}}{{foot}}</table>',
            'caption' => '<caption{{attrs}}>{{content}}</caption>',
            'head' => '<thead{{attrs}}>{{content}}</thead>',
            'body' => '<tbody{{attrs}}>{{content}}</tbody>',
            'foot' => '<tfoot{{attrs}}>{{content}}</tfoot>',
            'row' => '<tr{{attrs}}>{{content}}</tr>',
            'th' => '<th{{attrs}}>{{content}}</th>',
            'td' => '<td{{attrs}}>{{content}}</td>',
        ],
    ];

    /**
     * Renders a table.
     *
     * `$columns` is a map `key => label` or `key => [options]`. Column options:
     * - `label`: Header text.
     * - `sort`: `true` or a field name: renders `PaginatorHelper::sort()` with a sort
     *   indicator (config `sortIcons`, `false` to disable) and `aria-sort` on the active column.
     * - `class`: Applied to the `<th>` and every `<td>` of the column.
     * - `headerOptions` / `cellOptions`: Attributes of the `<th>` / `<td>`.
     * - `escape`: Escape the cells of this column (defaults to the table `escape`).
     *
     * Each row is an array keyed by column key (or a list in column order). A cell can be
     * a scalar or `['content' => ..., 'class' => ..., 'colspan' => ...]`. The special key
     * `_row` holds attributes of the `<tr>`.
     *
     * ### Options
     *
     * - `caption`: Table caption.
     * - `footer`: List of footer cells (rendered in `<tfoot>`).
     * - `empty`: Message shown when `$rows` is empty.
     * - `container`: Wrap in `.table-container` (default config `container`, true).
     * - `escape`: Escape cell contents (default true).
     * - `containerOptions`, `captionOptions`, `headOptions`, `bodyOptions`, `footOptions`.
     * - `class` and any attribute for the `<table>`.
     *
     * @param array<string|int, string|array<string, mixed>> $columns Columns.
     * @param iterable<array<string|int, mixed>> $rows Rows.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $columns, iterable $rows, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $columns = $this->normalizeColumns($columns, $escape);

        $head = '';
        if ($columns) {
            $cells = '';
            foreach ($columns as $key => $column) {
                $cells .= $this->headerCell((string)$key, $column);
            }
            $head = $this->renderTemplate(
                'head',
                $this->partAttributes('head', (array)($options['headOptions'] ?? [])),
                ['content' => $this->row($cells)],
            );
        }

        $body = '';
        $count = 0;
        foreach ($rows as $row) {
            $count++;
            $body .= $this->bodyRow((array)$row, $columns, $escape);
        }
        $empty = $options['empty'] ?? $this->getConfig('empty');
        if ($count === 0 && $empty) {
            $body = $this->row($this->renderTemplate(
                'td',
                $this->partAttributes('empty', ['colspan' => max(1, count($columns))]),
                ['content' => $this->text((string)$empty, $escape)],
            ));
        }
        $body = $this->renderTemplate(
            'body',
            $this->partAttributes('body', (array)($options['bodyOptions'] ?? [])),
            ['content' => $body],
        );

        $foot = '';
        if (!empty($options['footer'])) {
            $cells = '';
            foreach ((array)$options['footer'] as $cell) {
                $cells .= $this->cell($cell, [], $escape);
            }
            $foot = $this->renderTemplate(
                'foot',
                $this->partAttributes('foot', (array)($options['footOptions'] ?? [])),
                ['content' => $this->row($cells)],
            );
        }

        $caption = '';
        if (!empty($options['caption'])) {
            $caption = $this->renderTemplate(
                'caption',
                $this->partAttributes('caption', (array)($options['captionOptions'] ?? [])),
                ['content' => $this->text((string)$options['caption'], $escape)],
            );
        }

        $container = $options['container'] ?? $this->getConfig('container');
        $containerOptions = (array)($options['containerOptions'] ?? []);
        $attributes = $this->rootAttributes('table', $options, [
            'caption', 'footer', 'empty', 'container', 'containerOptions', 'captionOptions',
            'headOptions', 'bodyOptions', 'footOptions',
        ]);
        $table = $this->renderTemplate('table', $attributes, [
            'caption' => $caption,
            'head' => $head,
            'body' => $body,
            'foot' => $foot,
        ], $options['templateVars'] ?? []);

        if (!$container) {
            return $table;
        }

        return $this->renderTemplate(
            'container',
            $this->partAttributes('container', $containerOptions, 'table-container'),
            ['content' => $table],
        );
    }

    /**
     * Normalizes the column definitions.
     *
     * @param array<string|int, mixed> $columns Columns.
     * @param bool $escape Default escape.
     * @return array<string|int, array<string, mixed>>
     */
    protected function normalizeColumns(array $columns, bool $escape): array
    {
        $normalized = [];
        foreach ($columns as $key => $column) {
            if (!is_array($column)) {
                $column = ['label' => (string)$column];
            }
            $column += ['label' => is_string($key) ? $key : '', 'escape' => $escape];
            $normalized[$key] = $column;
        }

        return $normalized;
    }

    /**
     * Renders a header cell.
     *
     * @param string $key Column key.
     * @param array<string, mixed> $column Column definition.
     * @return string
     */
    protected function headerCell(string $key, array $column): string
    {
        $label = (string)$column['label'];
        $attributes = (array)($column['headerOptions'] ?? []);
        if (!empty($column['sort'])) {
            $field = is_string($column['sort']) ? $column['sort'] : $key;
            $direction = $this->activeSortDirection($field);
            if ($direction !== null) {
                $attributes['aria-sort'] = $direction === 'desc' ? 'descending' : 'ascending';
            }
            $content = $this->sortLink($field, $label, (bool)$column['escape'], $direction);
        } else {
            $content = $this->text($label, (bool)$column['escape']);
        }
        $attributes['class'] = $this->mergeClasses($column['class'] ?? null, $attributes['class'] ?? null);

        return $this->renderTemplate('th', $this->partAttributes('th', $attributes), ['content' => $content]);
    }

    /**
     * Renders the sort link of a header, with its state icon.
     *
     * @param string $field Sort field.
     * @param string $label Header label.
     * @param bool $escape Escape the label.
     * @param string|null $direction Active direction (`asc`/`desc`) or null when not sorted by this field.
     * @return string
     */
    protected function sortLink(string $field, string $label, bool $escape, ?string $direction): string
    {
        $icons = $this->getConfig('sortIcons');
        if (!$icons) {
            return $this->Paginator->sort($field, $label, ['escape' => $escape]);
        }

        $state = $direction ?? 'none';
        $icon = $this->Icon->render((string)($icons[$state] ?? $icons['none']), [
            'class' => $this->getConfig($direction === null ? 'classes.sortIcon' : 'classes.sortIconActive'),
            'replaceClass' => true,
            'data-sort-icon' => $state,
        ]);
        $title = sprintf(
            '<span class="%s">%s%s</span>',
            h((string)$this->getConfig('classes.sortLabel')),
            $this->text($label, $escape),
            $icon,
        );

        return $this->Paginator->sort($field, $title, ['escape' => false]);
    }

    /**
     * Current sort direction when the paginated results are sorted by `$field`.
     *
     * The paging `sort` param may be aliased (`Articles.title`) while the column uses the
     * bare field (`title`), or the other way around: both are considered the same field.
     *
     * @param string $field Sort field of the column.
     * @return string|null `asc`, `desc` or null when not sorted by this field.
     */
    protected function activeSortDirection(string $field): ?string
    {
        $sort = $this->Paginator->param('sort');
        if (!is_string($sort) || $sort === '') {
            return null;
        }
        $bare = fn(string $name): string => str_contains($name, '.') ? substr($name, strrpos($name, '.') + 1) : $name;
        if ($sort !== $field && $bare($sort) !== $bare($field)) {
            return null;
        }

        return strtolower((string)$this->Paginator->param('direction')) === 'desc' ? 'desc' : 'asc';
    }

    /**
     * Renders a body row.
     *
     * @param array<string|int, mixed> $row Row data.
     * @param array<string|int, array<string, mixed>> $columns Columns.
     * @param bool $escape Default escape.
     * @return string
     */
    protected function bodyRow(array $row, array $columns, bool $escape): string
    {
        $rowAttributes = (array)($row['_row'] ?? []);
        unset($row['_row']);

        $cells = '';
        if ($columns && !array_is_list($row)) {
            foreach ($columns as $key => $column) {
                $cells .= $this->cell($row[$key] ?? '', $column, (bool)$column['escape']);
            }
        } else {
            $keys = array_keys($columns);
            foreach (array_values($row) as $i => $value) {
                $column = isset($keys[$i]) ? $columns[$keys[$i]] : [];
                $cells .= $this->cell($value, $column, (bool)($column['escape'] ?? $escape));
            }
        }

        return $this->row($cells, $rowAttributes);
    }

    /**
     * Renders a body cell.
     *
     * @param mixed $value Cell value or options array.
     * @param array<string, mixed> $column Column definition.
     * @param bool $escape Escape the content.
     * @return string
     */
    protected function cell(mixed $value, array $column, bool $escape): string
    {
        $attributes = (array)($column['cellOptions'] ?? []);
        $attributes['class'] = $this->mergeClasses($column['class'] ?? null, $attributes['class'] ?? null);
        if (is_array($value)) {
            $content = $value['content'] ?? '';
            $escape = (bool)($value['escape'] ?? $escape);
            unset($value['content'], $value['escape']);
            $attributes['class'] = $this->mergeClasses($attributes['class'], $value['class'] ?? null);
            unset($value['class']);
            $attributes = $value + $attributes;
        } else {
            $content = $value;
        }

        return $this->renderTemplate(
            'td',
            $this->partAttributes('td', $attributes),
            ['content' => $this->text((string)$content, $escape)],
        );
    }

    /**
     * Renders a row.
     *
     * @param string $cells Cells HTML.
     * @param array<string, mixed> $attributes Row attributes.
     * @return string
     */
    protected function row(string $cells, array $attributes = []): string
    {
        return $this->renderTemplate('row', $this->partAttributes('row', $attributes), ['content' => $cells]);
    }
}
