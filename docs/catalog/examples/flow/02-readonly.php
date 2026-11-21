<?php
/**
 * @title Read-only flow
 * @description With `readonly` the steps can be selected and inspected but not moved, connected or edited; there is no palette.
 */
$flow = [
    'nodes' => [
        ['id' => 'overdue', 'type' => 'trigger', 'label' => 'Invoice overdue', 'position' => ['x' => 0, 'y' => 0], 'properties' => ['event' => 'invoice.overdue']],
        ['id' => 'wait', 'type' => 'action', 'label' => 'Wait 3 days', 'icon' => 'hourglass', 'position' => ['x' => 0, 'y' => 150], 'properties' => ['days' => 3]],
        ['id' => 'remind', 'type' => 'action', 'label' => 'Send a reminder', 'icon' => 'mail', 'position' => ['x' => 0, 'y' => 300], 'properties' => ['to' => 'Billing contact']],
    ],
    'edges' => [
        ['source' => 'overdue', 'target' => 'wait'],
        ['source' => 'wait', 'target' => 'remind'],
    ],
];
// ---
echo $this->Flow->editor($flow, [
    'label' => 'Invoice reminder',
    'readonly' => true,
    'name' => false,
    'fields' => ['event' => 'Event', 'days' => 'Days', 'to' => 'Recipient'],
    'class' => 'rounded-xl border bg-card',
]);
