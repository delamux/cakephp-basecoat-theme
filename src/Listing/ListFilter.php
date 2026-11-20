<?php
declare(strict_types=1);

namespace BasecoatTheme\Listing;

use BackedEnum;
use BasecoatTheme\Model\Filter\Contains;
use BasecoatTheme\Model\Filter\DateRange;
use BasecoatTheme\Model\Filter\Equals;
use Cake\Chronos\ChronosDate;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Http\CallbackStream;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use DateTimeInterface;
use JsonSerializable;
use Search\Model\Behavior\SearchBehavior;
use Search\Model\Filter\Boolean;
use Stringable;
use UnitEnum;

/**
 * Filter bar and CSV export of a list whose table uses friendsofcake/search.
 *
 * The filters are the table's search filters (`Search` behavior); this class turns them into the
 * controls of the `BasecoatTheme.list_filters` element and streams the filtered rows as CSV:
 *
 * ```
 * $query = $this->Articles->find('search', search: $this->request->getQueryParams())->contain(['Categories']);
 * $list = new ListFilter($this->request, $this->Articles, [
 *     'export' => ['title' => __('Title'), 'category.name' => __('Category')],
 * ]);
 * if ($list->isExport()) {
 *     return $list->csv($query, $this->response, 'articles');
 * }
 * ```
 *
 * Controls: `BasecoatTheme.Contains` → search box, `Search.Boolean` → Yes/No, `BasecoatTheme.Equals`
 * → its `options` or the related records of a belongsTo key, `BasecoatTheme.DateRange` → two dates.
 * Other filters still filter (from the URL) but have no control. Labels: the filter `label` option.
 */
class ListFilter
{
    public const EXPORT = 'export';

    private const OPTIONS_LIMIT = 500;

    /**
     * @var array<string, string>
     */
    private array $export;

    private int $chunkSize;

    /**
     * @param \Cake\Http\ServerRequest $request Request (query string).
     * @param \Cake\ORM\Table $table Listed table (with the `Search` behavior).
     * @param array<string, mixed> $config `export` (path => column title), `chunkSize` (rows per
     *   read when exporting, default 500).
     */
    public function __construct(private ServerRequest $request, private Table $table, array $config = [])
    {
        $this->export = array_map('strval', (array)($config['export'] ?? []));
        $this->chunkSize = max(1, (int)($config['chunkSize'] ?? 500));
    }

    /**
     * Controls of the filter bar, in the order of the search filters.
     *
     * @return array<string, array{type: string, label: string, options?: array<string|int, string>}>
     */
    public function controls(): array
    {
        $search = $this->search();
        if ($search === null) {
            return [];
        }
        $controls = [];
        foreach ($search->searchManager()->getFilters() as $name => $filter) {
            $label = (string)($filter->getConfig('label')
                ?? Inflector::humanize(preg_replace('/_id$/', '', (string)$name) ?? ''));
            if ($filter instanceof Contains) {
                $controls[$name] = ['type' => 'search', 'label' => $label];
            } elseif ($filter instanceof Boolean) {
                $controls[$name] = ['type' => 'select', 'label' => $label, 'options' => [
                    '1' => __d('basecoat_theme', 'Yes'),
                    '0' => __d('basecoat_theme', 'No'),
                ]];
            } elseif ($filter instanceof Equals) {
                $options = $filter->getConfig('options') ?? $this->relatedOptions((string)$name);
                $controls[$name] = $options === null
                    ? ['type' => 'text', 'label' => $label]
                    : ['type' => 'select', 'label' => $label, 'options' => (array)$options];
            } elseif ($filter instanceof DateRange) {
                $controls[$name] = ['type' => 'dateRange', 'label' => $label];
            }
        }

        return $controls;
    }

    /**
     * @return bool Whether the last `search` find applied a filter (valid values only).
     */
    public function isFiltered(): bool
    {
        return (bool)$this->search()?->isSearch();
    }

    /**
     * @return \Search\Model\Behavior\SearchBehavior|null The table's Search behavior.
     */
    private function search(): ?SearchBehavior
    {
        if (!$this->table->hasBehavior('Search')) {
            return null;
        }
        $behavior = $this->table->getBehavior('Search');

        return $behavior instanceof SearchBehavior ? $behavior : null;
    }

    /**
     * @return bool Whether the request asks for the CSV (`?export=csv`).
     */
    public function isExport(): bool
    {
        return $this->request->getQuery(self::EXPORT) === 'csv';
    }

    /**
     * @return bool Whether the list can be exported (columns configured).
     */
    public function hasExport(): bool
    {
        return $this->export !== [];
    }

    /**
     * @return array<string, mixed> Query string of the export link: the current one (filters and sort),
     *   without the page, plus `export=csv`.
     */
    public function exportQuery(): array
    {
        $query = $this->request->getQueryParams();
        unset($query['page'], $query[self::EXPORT]);

        return $query + [self::EXPORT => 'csv'];
    }

    /**
     * CSV download of the (filtered) query, streamed in chunks.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<string, mixed>> $query Query.
     * @param \Cake\Http\Response $response Response.
     * @param string $name File name (the date and `.csv` are added).
     * @return \Cake\Http\Response
     */
    public function csv(SelectQuery $query, Response $response, string $name): Response
    {
        $stream = new CallbackStream(function () use ($query): string {
            $output = fopen('php://output', 'w');
            if ($output !== false) {
                $this->writeCsv($query, $output);
                fclose($output);
            }

            return '';
        });

        return $response->withType('csv')
            ->withHeader('Cache-Control', 'no-store')
            ->withDownload(sprintf('%s-%s.csv', $name, date('Y-m-d')))
            ->withBody($stream);
    }

    /**
     * Writes the CSV (UTF-8 with BOM, header row) in the list order (`sort`/`direction` when it is a
     * column of the table, then the primary key), reading `chunkSize` rows at a time.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<string, mixed>> $query Query.
     * @param resource $handle Stream.
     * @return void
     */
    public function writeCsv(SelectQuery $query, $handle): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values($this->export), ',', '"', '');

        $query = (clone $query)->orderBy($this->exportOrder(), true);
        $page = 1;
        do {
            $rows = (clone $query)->page($page++, $this->chunkSize)->all();
            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($this->export) as $path) {
                    $line[] = $this->cell(Hash::get($row, $path));
                }
                fputcsv($handle, $line, ',', '"', '');
            }
        } while ($rows->count() === $this->chunkSize);
    }

    /**
     * @param string $field Foreign key.
     * @return array<string|int, string>|null Related records (display field), null if not a belongsTo key.
     */
    private function relatedOptions(string $field): ?array
    {
        foreach ($this->table->associations()->getByType('BelongsTo') as $association) {
            if ($association instanceof BelongsTo && $association->getForeignKey() === $field) {
                $target = $association->getTarget();
                $display = $target->getDisplayField();
                /** @var array<string|int, string> $options */
                $options = $target->find('list')
                    ->orderBy(is_string($display) ? [$target->aliasField($display) => 'ASC'] : [])
                    ->limit(self::OPTIONS_LIMIT)
                    ->toArray();

                return $options;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function exportOrder(): array
    {
        $order = [];
        $sort = $this->request->getQuery('sort');
        if (is_string($sort)) {
            $alias = $this->table->getAlias() . '.';
            $field = str_starts_with($sort, $alias) ? substr($sort, strlen($alias)) : $sort;
            if ($this->table->getSchema()->hasColumn($field)) {
                $direction = strtolower((string)$this->request->getQuery('direction')) === 'desc' ? 'DESC' : 'ASC';
                $order[$this->table->aliasField($field)] = $direction;
            }
        }
        foreach ((array)$this->table->getPrimaryKey() as $key) {
            $order[$this->table->aliasField((string)$key)] ??= 'ASC';
        }

        return $order;
    }

    /**
     * @param mixed $value Value.
     * @return string Readable text; text starting with `= + - @` is prefixed with `'` so spreadsheets
     *   do not run it as a formula (CSV injection).
     */
    private function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? __d('basecoat_theme', 'Yes') : __d('basecoat_theme', 'No'),
            $value instanceof ChronosDate => $value->format('Y-m-d'),
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof EnumLabelInterface => $value->label(),
            $value instanceof BackedEnum => (string)$value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value), $value instanceof JsonSerializable => (string)json_encode($value),
            is_scalar($value), $value instanceof Stringable => (string)$value,
            default => '',
        };
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $text)) {
            return "'" . $text;
        }

        return $text;
    }
}
