<?php
/**
 * @title Payment type with icons
 * @description Payment methods as two-column cards with built-in icons; one option is disabled.
 */
$methods = [
    'card' => ['title' => 'Card', 'description' => 'Charged on the 1st of every month.', 'icon' => 'credit-card'],
    'transfer' => ['title' => 'Bank transfer', 'description' => 'Invoice with 14-day terms, Scale plan only.', 'icon' => 'landmark'],
    'wallet' => ['title' => 'Wallet', 'description' => 'Coming to Fernway in 2027.', 'icon' => 'wallet', 'disabled' => true],
];
// ---
echo $this->ChoiceCards->render('payment_type', $methods, [
    'legend' => 'Payment type',
    'columns' => 2,
    'value' => 'card',
]);
