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
    ['name' => 'Acme Website', 'owner' => 'Jane Doe', 'created' => '2026-01-14'],
    ['name' => 'Mobile App', 'owner' => 'Marta Vega', 'created' => '2026-03-02'],
    ['name' => 'Data Pipeline', 'owner' => 'Leo Park', 'created' => '2026-05-21'],
]);
