<?php
/**
 * @title Page header with actions
 * @description Header actions are raw HTML, typically buttons, aligned to the right.
 */
$actions = [
    $this->Button->render('Export', ['variant' => 'outline', 'icon' => 'arrow-down']),
    $this->Button->render('New customer', ['icon' => 'plus']),
];
// ---
echo $this->PageHeader->render('Customers', [
    'description' => 'Review and manage the people who buy from your store.',
    'actions' => $actions,
]);
