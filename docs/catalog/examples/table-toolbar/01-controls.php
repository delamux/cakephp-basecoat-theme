<?php
/**
 * @title Search, filters and actions
 * @description The controls a ListFilter produces (search, select, date range) with an action button on the right.
 */
$controls = [
    'q' => ['type' => 'search', 'label' => 'Search orders'],
    'status' => ['type' => 'select', 'label' => 'Status', 'options' => [
        'paid' => 'Paid',
        'pending' => 'Pending',
        'failed' => 'Failed',
    ]],
    'created' => ['type' => 'dateRange', 'label' => 'Created'],
];
// ---
echo $this->TableToolbar->render([
    'filter' => $controls,
    'actions' => $this->Button->link('New order', '/orders/add', ['variant' => 'primary']),
]);
