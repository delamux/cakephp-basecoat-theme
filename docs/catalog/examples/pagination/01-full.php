<?php
/**
 * @title Full pagination
 * @description Numbers, first/last links and a record counter for a paginated result.
 */
$this->set('articles', new \Cake\Datasource\Paging\PaginatedResultSet(new ArrayIterator([]), [
    'count' => 20, 'currentPage' => 5, 'perPage' => 20, 'totalCount' => 200,
    'pageCount' => 10, 'hasPrevPage' => true, 'hasNextPage' => true,
    'sort' => null, 'direction' => null, 'sortDefault' => false, 'directionDefault' => false,
    'completeSort' => [], 'alias' => 'Articles', 'scope' => null, 'limit' => null,
    'start' => 81, 'end' => 100, 'requestedPage' => 5,
]));
// ---
echo $this->Pagination->render(['first' => true, 'last' => true, 'counter' => true, 'modulus' => 2]);
