<?php
/**
 * @title Search, filters and actions
 * @description The controls a ListFilter produces (search, select, date range) for a customers list, with an action button on the right.
 */
$controls = [
    'search' => ['type' => 'search', 'label' => 'Search customers'],
    'country' => ['type' => 'select', 'label' => 'Country', 'options' => [
        'es' => 'Spain',
        'pt' => 'Portugal',
        'fr' => 'France',
    ]],
    'signed_up' => ['type' => 'dateRange', 'label' => 'Signed up'],
];
// ---
echo $this->TableToolbar->render([
    'filter' => $controls,
    'actions' => $this->Button->link('New customer', '/customers/add', ['variant' => 'primary']),
]);
