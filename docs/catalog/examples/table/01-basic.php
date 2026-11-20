<?php
/**
 * @title Billing table
 * @description Columns map keys to labels; cells may hold HTML (escape false) such as badges.
 */
$rows = [
    ['invoice' => 'FW-2311', 'customer' => 'Harbor & Pine', 'status' => $this->Badge->render('Paid', ['variant' => 'secondary']), 'amount' => '$1,280.00'],
    ['invoice' => 'FW-2312', 'customer' => 'Quillon Studio', 'status' => $this->Badge->render('Due', ['variant' => 'outline']), 'amount' => '$96.00'],
    ['invoice' => 'FW-2313', 'customer' => 'Ostrava Freight', 'status' => $this->Badge->render('Overdue', ['variant' => 'destructive']), 'amount' => '$4,560.00'],
];
// ---
echo $this->Table->render([
    'invoice' => 'Invoice',
    'customer' => 'Customer',
    'status' => 'Status',
    'amount' => ['label' => 'Amount', 'class' => 'text-right'],
], $rows, [
    'caption' => 'Fernway invoices issued in November.',
    'footer' => ['', '', 'Total', ['content' => '$5,936.00', 'class' => 'text-right']],
    'escape' => false,
]);
