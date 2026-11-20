<?php
/**
 * @title Previous / next only
 * @description simple() renders only the two buttons, with custom labels.
 */
$this->set('articles', new \Cake\Datasource\Paging\PaginatedResultSet(new ArrayIterator([]), [
    'count' => 20, 'currentPage' => 1, 'perPage' => 20, 'totalCount' => 60,
    'pageCount' => 3, 'hasPrevPage' => false, 'hasNextPage' => true,
    'sort' => null, 'direction' => null, 'sortDefault' => false, 'directionDefault' => false,
    'completeSort' => [], 'alias' => 'Articles', 'scope' => null, 'limit' => null,
    'start' => 1, 'end' => 20, 'requestedPage' => 1,
]));
// ---
echo $this->Pagination->simple(['prev' => 'Newer', 'next' => 'Older', 'counter' => true]);
