<?php
/**
 * @title Invoices table
 * @description Columns map keys to labels; cells may hold HTML (escape false) such as badges.
 */
$rows = [
    ['invoice' => 'INV-001', 'status' => $this->Badge->render('Paid', ['variant' => 'secondary']), 'method' => 'Credit Card', 'amount' => '$250.00'],
    ['invoice' => 'INV-002', 'status' => $this->Badge->render('Pending', ['variant' => 'outline']), 'method' => 'PayPal', 'amount' => '$150.00'],
    ['invoice' => 'INV-003', 'status' => $this->Badge->render('Unpaid', ['variant' => 'destructive']), 'method' => 'Bank Transfer', 'amount' => '$350.00'],
];
// ---
echo $this->Table->render([
    'invoice' => 'Invoice',
    'status' => 'Status',
    'method' => 'Method',
    'amount' => ['label' => 'Amount', 'class' => 'text-right'],
], $rows, [
    'caption' => 'A list of your recent invoices.',
    'footer' => ['', '', 'Total', ['content' => '$750.00', 'class' => 'text-right']],
    'escape' => false,
]);
