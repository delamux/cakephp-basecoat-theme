<?php
declare(strict_types=1);

/**
 * Sample data of the `workflow` screen: an automation of the fictional Fernway team.
 *
 * View variables:
 * - `workflow`: id, name, description, status (active|draft), updated.
 * - `definition`: the sequential-workflow-designer definition, `properties` (name, description)
 *   and `sequence` (steps). A step is id, componentType (`task`, or `switch` with `branches`:
 *   branch name => steps), type (`[a-z0-9-]+`), name and properties (key => string|int).
 * - `toolbox`: groups (name, steps) of step templates the designer can add (steps without id).
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
    'definition' => [
        'properties' => [
            'name' => 'New customer onboarding',
            'description' => 'Welcomes every new customer and routes Scale accounts to a success manager.',
        ],
        'sequence' => [
            [
                'id' => 'step-signed-up',
                'componentType' => 'task',
                'type' => 'trigger',
                'name' => 'Customer signed up',
                'properties' => ['event' => 'customer.created'],
            ],
            [
                'id' => 'step-welcome',
                'componentType' => 'task',
                'type' => 'email',
                'name' => 'Send welcome email',
                'properties' => ['to' => 'Customer owner', 'subject' => 'Welcome to Fernway'],
            ],
            [
                'id' => 'step-plan',
                'componentType' => 'switch',
                'type' => 'if',
                'name' => 'Plan is Scale?',
                'properties' => ['condition' => 'plan = Scale'],
                'branches' => [
                    'Yes' => [
                        [
                            'id' => 'step-success-manager',
                            'componentType' => 'task',
                            'type' => 'assign',
                            'name' => 'Assign success manager',
                            'properties' => ['assignee' => 'Ines Duarte'],
                        ],
                    ],
                    'No' => [
                        [
                            'id' => 'step-nurture',
                            'componentType' => 'task',
                            'type' => 'list',
                            'name' => 'Add to nurture list',
                            'properties' => ['list' => 'Starter nurture'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'step-wait',
                'componentType' => 'task',
                'type' => 'wait',
                'name' => 'Wait 3 days',
                'properties' => ['days' => 3],
            ],
            [
                'id' => 'step-follow-up',
                'componentType' => 'task',
                'type' => 'task',
                'name' => 'Create follow-up task',
                'properties' => ['title' => 'Check in on the first week', 'assignee' => 'Pavel Novak'],
            ],
        ],
    ],
    'toolbox' => [
        [
            'name' => 'Triggers',
            'steps' => [
                ['componentType' => 'task', 'type' => 'trigger', 'name' => 'Customer signed up', 'properties' => ['event' => 'customer.created']],
                ['componentType' => 'task', 'type' => 'trigger', 'name' => 'Invoice paid', 'properties' => ['event' => 'invoice.paid']],
            ],
        ],
        [
            'name' => 'Actions',
            'steps' => [
                ['componentType' => 'task', 'type' => 'email', 'name' => 'Send email', 'properties' => ['to' => '', 'subject' => '']],
                ['componentType' => 'task', 'type' => 'assign', 'name' => 'Assign owner', 'properties' => ['assignee' => '']],
                ['componentType' => 'task', 'type' => 'list', 'name' => 'Add to list', 'properties' => ['list' => '']],
                ['componentType' => 'task', 'type' => 'task', 'name' => 'Create task', 'properties' => ['title' => '', 'assignee' => '']],
            ],
        ],
        [
            'name' => 'Logic',
            'steps' => [
                ['componentType' => 'switch', 'type' => 'if', 'name' => 'If / else', 'properties' => ['condition' => ''], 'branches' => ['Yes' => [], 'No' => []]],
                ['componentType' => 'task', 'type' => 'wait', 'name' => 'Wait', 'properties' => ['days' => 1]],
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
