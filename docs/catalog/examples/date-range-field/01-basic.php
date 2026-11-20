<?php
/**
 * @title Date range popover
 * @description Two date inputs in a popover; the trigger shows the chosen range and the inputs work without JavaScript.
 */
$from = '2026-11-01';
$to = '2026-11-30';
// ---
echo $this->DateRangeField->render('created', [
    'label' => 'Created',
    'from' => $from,
    'to' => $to,
]);
