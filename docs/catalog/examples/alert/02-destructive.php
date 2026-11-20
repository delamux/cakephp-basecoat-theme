<?php
/**
 * @title Destructive alert with a list
 * @description Pass an array as description to render a list of problems.
 */
echo $this->Alert->render('Unable to process your payment', [
    'Check your card details.',
    'Make sure there are enough funds.',
    'Verify the billing address.',
], ['variant' => 'destructive', 'icon' => 'circle-alert']);
