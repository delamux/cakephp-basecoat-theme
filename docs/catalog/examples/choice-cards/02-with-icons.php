<?php
/**
 * @title Payment type with icons
 * @description Payment methods as two-column cards with built-in icons; one option is disabled.
 */
$methods = [
    'card' => ['title' => 'Credit card', 'description' => 'Visa, Mastercard or American Express.', 'icon' => 'credit-card'],
    'transfer' => ['title' => 'Bank transfer', 'description' => 'Settles in one to two business days.', 'icon' => 'landmark'],
    'wallet' => ['title' => 'Digital wallet', 'description' => 'Not available in your region.', 'icon' => 'wallet', 'disabled' => true],
];
// ---
echo $this->ChoiceCards->render('payment_type', $methods, [
    'legend' => 'Payment type',
    'columns' => 2,
    'value' => 'card',
]);
