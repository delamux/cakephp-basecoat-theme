<?php
declare(strict_types=1);

namespace BasecoatTheme\Bake;

use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;
use Cake\View\View;

/**
 * `bin/cake bake model` with the BasecoatTheme bake theme and friendsofcake/search loaded: adds
 * the `Search` behavior and list filters guessed from the columns (`searchFilters` view variable,
 * rendered by `templates/bake/Model/table.twig`):
 *
 * - `q` (`BasecoatTheme.Contains`): text columns, except secrets (password, token…).
 * - belongsTo keys (`BasecoatTheme.Equals`) and booleans (`Search.Boolean`), up to 4.
 * - `created` (or the first date column) as a `BasecoatTheme.DateRange`.
 *
 * The generated filters are a starting point: edit them in the table like any search filter.
 */
class SearchFiltersListener implements EventListenerInterface
{
    private const MAX_SEARCH_FIELDS = 6;
    private const MAX_SELECTS = 4;
    private const SECRET = '/(password|passwd|token|secret|salt|hash|api_key)/i';
    private const TEXT_TYPES = ['string', 'text', 'char'];
    private const DATE_TYPES = [
        'date', 'datetime', 'datetimefractional', 'timestamp', 'timestampfractional', 'timestamptimezone',
    ];

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return ['Bake.beforeRender.Model.table' => 'beforeRenderTable'];
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\View\View> $event Event.
     * @return void
     */
    public function beforeRenderTable(EventInterface $event): void
    {
        $view = $event->getSubject();
        if (!$view instanceof View || $view->getTheme() !== 'BasecoatTheme' || !Plugin::isLoaded('Search')) {
            return;
        }
        $filters = $this->filters($view);
        if ($filters === []) {
            return;
        }
        $view->set('searchFilters', $filters);
        $view->set('behaviors', ['Search.Search' => []] + (array)$view->get('behaviors', []));
    }

    /**
     * @param \Cake\View\View $view Bake view.
     * @return array<string, array{className: string, options: string}> Name => filter class and
     *   its options as PHP code.
     */
    public function filters(View $view): array
    {
        $connection = $view->get('connection');
        if (!$connection instanceof Connection) {
            $connection = ConnectionManager::get((string)($connection ?: 'default'));
        }
        if (!$connection instanceof Connection) {
            return [];
        }
        $table = (string)($view->get('table') ?: Inflector::tableize((string)$view->get('name')));
        $schema = $connection->getSchemaCollection()->describe($table);
        $plugin = (string)$view->get('plugin');
        $translate = fn(string $text): string => ($plugin !== ''
            ? "__d('" . Inflector::underscore($plugin) . "', "
            : '__(') . var_export($text, true) . ')';

        $foreignKeys = [];
        foreach ((array)($view->get('associations')['belongsTo'] ?? []) as $association) {
            $foreignKeys[(string)($association['foreignKey'] ?? '')] = (string)$association['alias'];
        }
        $primaryKey = $schema->getPrimaryKey();

        $search = [];
        $selects = [];
        $date = null;
        foreach ($schema->columns() as $column) {
            if (in_array($column, $primaryKey, true)) {
                continue;
            }
            $type = (string)$schema->getColumnType($column);
            if (isset($foreignKeys[$column])) {
                $label = Inflector::humanize(Inflector::underscore(Inflector::singularize($foreignKeys[$column])));
                $selects[$column] = [
                    'className' => 'BasecoatTheme.Equals',
                    'options' => "['label' => " . $translate($label) . ']',
                ];
            } elseif ($type === 'boolean') {
                $selects[$column] = [
                    'className' => 'Search.Boolean',
                    'options' => "['label' => " . $translate(Inflector::humanize($column)) . ']',
                ];
            } elseif (in_array($type, self::TEXT_TYPES, true) && !preg_match(self::SECRET, $column)) {
                $search[] = $column;
            } elseif (
                in_array($type, self::DATE_TYPES, true)
                && ($date === null || $column === 'created')
                && !in_array($column, ['modified', 'updated'], true)
            ) {
                $date = $column;
            }
        }

        $filters = [];
        if ($search !== []) {
            $fields = array_map(
                fn($field) => var_export($field, true),
                array_slice($search, 0, self::MAX_SEARCH_FIELDS),
            );
            $filters['q'] = [
                'className' => 'BasecoatTheme.Contains',
                'options' => "['fields' => [" . implode(', ', $fields) . "], 'label' => " . $translate('Search') . ']',
            ];
        }
        $filters += array_slice($selects, 0, self::MAX_SELECTS, true);
        if ($date !== null) {
            $filters[$date] = [
                'className' => 'BasecoatTheme.DateRange',
                'options' => "['label' => " . $translate(Inflector::humanize($date)) . ']',
            ];
        }

        return $filters;
    }
}
