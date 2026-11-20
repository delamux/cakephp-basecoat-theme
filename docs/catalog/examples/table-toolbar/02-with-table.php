<?php
/**
 * @title Toolbar above a table
 * @description An orders list: the toolbar filters the rows and each row has an actions menu.
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
$orders = [
    ['customer' => 'Marta Ruiz', 'total' => '$1,240.00', 'status' => 'Paid', 'variant' => 'primary'],
    ['customer' => 'Northwind Studio', 'total' => '$860.50', 'status' => 'Pending', 'variant' => 'secondary'],
    ['customer' => 'Tomas Berg', 'total' => '$92.00', 'status' => 'Failed', 'variant' => 'destructive'],
];
// A filtered state: the toolbar fields take their values from the query string
$this->setRequest($this->getRequest()->withQueryParams([
    'q' => 'studio',
    'status' => 'pending',
    'created' => ['from' => '2026-11-01', 'to' => '2026-11-30'],
]));
$rows = [];
foreach ($orders as $order) {
    $rows[] = [
        'customer' => $order['customer'],
        'total' => $order['total'],
        'status' => ['content' => $this->Badge->render($order['status'], ['variant' => $order['variant']]), 'escape' => false],
        'actions' => ['escape' => false, 'content' => $this->DropdownMenu->render('', [
            ['label' => 'View', 'url' => '/orders/view'],
            ['label' => 'Refund'],
            '-',
            ['label' => 'Delete', 'variant' => 'destructive'],
        ], ['trigger' => ['icon' => 'ellipsis-vertical', 'variant' => 'ghost', 'size' => 'icon-sm', 'label' => 'Row actions']])],
    ];
}
// ---
echo '<div class="grid gap-4">';
echo $this->TableToolbar->render([
    'filter' => $controls,
    'actions' => $this->Button->link('New order', '/orders/add', ['variant' => 'primary']),
    'htmx' => ['target' => 'orders-list'],
    'clear' => true,
]);
echo '<div id="orders-list">';
echo $this->Table->render(
    ['customer' => 'Customer', 'total' => ['label' => 'Total', 'class' => 'text-right'], 'status' => 'Status', 'actions' => ''],
    $rows,
);
echo '</div></div>';
