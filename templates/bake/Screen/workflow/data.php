<?php
declare(strict_types=1);

/**
 * Sample data of the `workflow` screen: an automation of the fictional Fernway team.
 *
 * View variables:
 * - `workflow`: id, name, description, status (active|draft), updated.
 * - `flow`: the steps for FlowHelper::editor(). `nodes`: id, type (trigger|action|condition), label,
 *   icon (IconHelper name), position (x, y on the canvas), properties (key => string|int) and, for a
 *   condition, outputs (key => label). `edges`: source, output (condition outputs), target.
 * - `palette`: groups (label, items) of step templates the editor can add (steps without id / position).
 * - `runs`: recent runs, id, started, trigger, status (succeeded|failed|running), duration.
 */
return [
    'workflow' => [
        'id' => 7,
        'name' => 'New customer onboarding',
        'description' => 'Welcomes every new customer and routes Scale accounts to a success manager.',
        'status' => 'active',
        'updated' => 'Edited by Kofi Mensah 2 hours ago',
    ],
    'flow' => [
        'nodes' => [
            [
                'id' => 'signed-up',
                'type' => 'trigger',
                'label' => 'Customer signed up',
                'icon' => 'user-plus',
                'position' => ['x' => 0, 'y' => 0],
                'properties' => ['event' => 'customer.created'],
            ],
            [
                'id' => 'welcome',
                'type' => 'action',
                'label' => 'Send welcome email',
                'icon' => 'mail',
                'position' => ['x' => 0, 'y' => 150],
                'properties' => ['to' => 'Customer owner', 'subject' => 'Welcome to Fernway'],
            ],
            [
                'id' => 'plan',
                'type' => 'condition',
                'label' => 'Plan is Scale?',
                'icon' => 'split',
                'position' => ['x' => 0, 'y' => 300],
                'properties' => ['condition' => 'plan = Scale'],
                'outputs' => ['yes' => 'Yes', 'no' => 'No'],
            ],
            [
                'id' => 'success-manager',
                'type' => 'action',
                'label' => 'Assign success manager',
                'icon' => 'user-check',
                'position' => ['x' => -160, 'y' => 470],
                'properties' => ['assignee' => 'Ines Duarte'],
            ],
            [
                'id' => 'nurture',
                'type' => 'action',
                'label' => 'Add to nurture list',
                'icon' => 'list-plus',
                'position' => ['x' => 160, 'y' => 470],
                'properties' => ['list' => 'Starter nurture'],
            ],
            [
                'id' => 'wait',
                'type' => 'action',
                'label' => 'Wait 3 days',
                'icon' => 'hourglass',
                'position' => ['x' => 0, 'y' => 620],
                'properties' => ['days' => 3],
            ],
            [
                'id' => 'follow-up',
                'type' => 'action',
                'label' => 'Create follow-up task',
                'icon' => 'list-checks',
                'position' => ['x' => 0, 'y' => 770],
                'properties' => ['title' => 'Check in on the first week', 'assignee' => 'Pavel Novak'],
            ],
        ],
        'edges' => [
            ['source' => 'signed-up', 'target' => 'welcome'],
            ['source' => 'welcome', 'target' => 'plan'],
            ['source' => 'plan', 'output' => 'yes', 'target' => 'success-manager'],
            ['source' => 'plan', 'output' => 'no', 'target' => 'nurture'],
            ['source' => 'success-manager', 'target' => 'wait'],
            ['source' => 'nurture', 'target' => 'wait'],
            ['source' => 'wait', 'target' => 'follow-up'],
        ],
    ],
    'palette' => [
        [
            'label' => 'Triggers',
            'items' => [
                ['type' => 'trigger', 'label' => 'Customer signed up', 'icon' => 'user-plus', 'properties' => ['event' => 'customer.created']],
                ['type' => 'trigger', 'label' => 'Invoice paid', 'icon' => 'zap', 'properties' => ['event' => 'invoice.paid']],
            ],
        ],
        [
            'label' => 'Actions',
            'items' => [
                ['type' => 'action', 'label' => 'Send email', 'icon' => 'mail', 'properties' => ['to' => '', 'subject' => '']],
                ['type' => 'action', 'label' => 'Assign owner', 'icon' => 'user-check', 'properties' => ['assignee' => '']],
                ['type' => 'action', 'label' => 'Add to list', 'icon' => 'list-plus', 'properties' => ['list' => '']],
                ['type' => 'action', 'label' => 'Create task', 'icon' => 'list-checks', 'properties' => ['title' => '', 'assignee' => '']],
                ['type' => 'action', 'label' => 'Wait', 'icon' => 'hourglass', 'properties' => ['days' => 1]],
            ],
        ],
        [
            'label' => 'Logic',
            'items' => [
                ['type' => 'condition', 'label' => 'If / else', 'icon' => 'split', 'properties' => ['condition' => ''], 'outputs' => ['yes' => 'Yes', 'no' => 'No']],
            ],
        ],
    ],
    'runs' => [
        ['id' => 'run-1048', 'started' => 'Today, 09:42', 'trigger' => 'Lantern Books signed up', 'status' => 'running', 'duration' => '3 days left'],
        ['id' => 'run-1047', 'started' => 'Yesterday, 16:05', 'trigger' => 'Brightmoor Clinic signed up', 'status' => 'succeeded', 'duration' => '1.2 s'],
        ['id' => 'run-1046', 'started' => 'Yesterday, 11:30', 'trigger' => 'Ostrava Freight signed up', 'status' => 'failed', 'duration' => '0.8 s'],
        ['id' => 'run-1045', 'started' => '18 Nov, 14:12', 'trigger' => 'Quillon Studio signed up', 'status' => 'succeeded', 'duration' => '1.4 s'],
        ['id' => 'run-1044', 'started' => '17 Nov, 10:58', 'trigger' => 'Harbor & Pine signed up', 'status' => 'succeeded', 'duration' => '1.1 s'],
    ],
];
