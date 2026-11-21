<?php
/**
 * @title Flow editor
 * @description Steps on a canvas: drag them, connect their outputs, add steps from the palette and edit the selected one in the side panel. The hidden `flow` input keeps the edited flow as JSON.
 */
$flow = [
    'nodes' => [
        ['id' => 'received', 'type' => 'trigger', 'label' => 'Ticket received', 'icon' => 'mail', 'position' => ['x' => 0, 'y' => 0], 'properties' => ['queue' => 'Support']],
        ['id' => 'urgent', 'type' => 'condition', 'label' => 'Priority is urgent?', 'position' => ['x' => 0, 'y' => 150], 'properties' => ['condition' => 'priority = urgent'], 'outputs' => ['yes' => 'Urgent', 'no' => 'Normal']],
        ['id' => 'page', 'type' => 'action', 'label' => 'Page the on-call agent', 'icon' => 'bell', 'position' => ['x' => -160, 'y' => 320], 'properties' => ['team' => 'Tier 2']],
        ['id' => 'queue', 'type' => 'action', 'label' => 'Add to the queue', 'icon' => 'list-plus', 'position' => ['x' => 160, 'y' => 320], 'properties' => ['queue' => 'Support']],
    ],
    'edges' => [
        ['source' => 'received', 'target' => 'urgent'],
        ['source' => 'urgent', 'output' => 'yes', 'target' => 'page'],
        ['source' => 'urgent', 'output' => 'no', 'target' => 'queue'],
    ],
];
// ---
echo $this->Flow->editor($flow, [
    'label' => 'Ticket triage',
    'palette' => [
        ['label' => 'Actions', 'items' => [
            ['type' => 'action', 'label' => 'Send email', 'icon' => 'mail', 'properties' => ['to' => '']],
            ['type' => 'action', 'label' => 'Wait', 'icon' => 'hourglass', 'properties' => ['days' => 1]],
        ]],
        ['label' => 'Logic', 'items' => [
            ['type' => 'condition', 'label' => 'If / else', 'properties' => ['condition' => '']],
        ]],
    ],
    'fields' => ['queue' => 'Queue', 'condition' => 'Condition', 'team' => 'Team', 'to' => 'Recipient', 'days' => 'Days'],
    'class' => 'rounded-xl border bg-card',
]);
