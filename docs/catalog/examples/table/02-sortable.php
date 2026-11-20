<?php
/**
 * @title Sortable columns
 * @description With paginated results in the view, sort headers link to the sort URL and mark the active column.
 */
$this->set('projects', new \Cake\Datasource\Paging\PaginatedResultSet(new ArrayIterator([]), [
    'count' => 3, 'currentPage' => 1, 'perPage' => 20, 'totalCount' => 3, 'pageCount' => 1,
    'hasPrevPage' => false, 'hasNextPage' => false, 'sort' => 'name', 'direction' => 'asc',
    'sortDefault' => false, 'directionDefault' => false, 'completeSort' => [], 'alias' => 'Projects',
    'scope' => null, 'limit' => null, 'start' => 1, 'end' => 3, 'requestedPage' => 1,
]));
// ---
echo $this->Table->render([
    'name' => ['label' => 'Name', 'sort' => true],
    'owner' => 'Owner',
    'created' => ['label' => 'Created', 'sort' => true],
], [
    ['name' => 'Churn forecast', 'owner' => 'Elena Sarto', 'created' => '2026-02-09'],
    ['name' => 'Funnel review', 'owner' => 'Kofi Mensah', 'created' => '2026-04-17'],
    ['name' => 'Warehouse sync', 'owner' => 'Pavel Novak', 'created' => '2026-08-03'],
]);
