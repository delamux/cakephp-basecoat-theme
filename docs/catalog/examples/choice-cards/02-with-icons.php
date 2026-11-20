<?php
/**
 * @title Payment type with icons
 * @description Payment methods as two-column cards with icons; one option is disabled.
 */
$icons = [
    'landmark' => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
    'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
];
$methods = [
    'card' => ['title' => 'Credit card', 'description' => 'Visa, Mastercard or American Express.', 'icon' => 'credit-card'],
    'transfer' => ['title' => 'Bank transfer', 'description' => 'Settles in one to two business days.', 'icon' => 'landmark'],
    'wallet' => ['title' => 'Digital wallet', 'description' => 'Not available in your region.', 'icon' => 'wallet', 'disabled' => true],
];
// ---
$this->Icon->add($icons);
echo $this->ChoiceCards->render('payment_type', $methods, [
    'legend' => 'Payment type',
    'columns' => 2,
    'value' => 'card',
]);
