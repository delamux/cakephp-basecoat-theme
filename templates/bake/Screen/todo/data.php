<?php
declare(strict_types=1);

/**
 * Sample data of the `todo` screen: tasks of the fictional Fernway workspace, grouped by due date.
 *
 * View variables: `groups` (group key => tasks; keys `today`, `upcoming`, `later`), `filter`
 * (`all`, `active` or `completed`) and `counts` (tasks per filter). Task priority is `high`,
 * `medium` or `low`.
 */
return [
    'groups' => [
        'today' => [
            [
                'id' => 1,
                'title' => 'Send the renewal quote to Lantern Books',
                'done' => false,
                'priority' => 'high',
                'due' => 'Today, 17:00',
                'assignee' => ['name' => 'Elena Sarto'],
                'tags' => ['Billing', 'Renewal'],
            ],
            [
                'id' => 2,
                'title' => 'Review the field mapping with Harbor & Pine',
                'done' => false,
                'priority' => 'medium',
                'due' => 'Today, 15:30',
                'assignee' => ['name' => 'Kofi Mensah'],
                'tags' => ['Onboarding'],
            ],
            [
                'id' => 3,
                'title' => 'Publish the November usage report',
                'done' => true,
                'priority' => 'low',
                'due' => 'Today, 09:00',
                'assignee' => ['name' => 'Ines Duarte'],
                'tags' => ['Reports'],
            ],
            [
                'id' => 4,
                'title' => 'Answer the access request from Noor Haddad',
                'done' => true,
                'priority' => 'medium',
                'due' => 'Today, 10:15',
                'assignee' => ['name' => 'Pavel Novak'],
                'tags' => ['Access'],
            ],
        ],
        'upcoming' => [
            [
                'id' => 5,
                'title' => 'Prepare the churn review for Brightmoor Clinic',
                'done' => false,
                'priority' => 'high',
                'due' => 'Tomorrow',
                'assignee' => ['name' => 'Noor Haddad'],
                'tags' => ['Reports', 'Retention'],
            ],
            [
                'id' => 6,
                'title' => 'Book the onboarding call with Quillon Studio',
                'done' => false,
                'priority' => 'medium',
                'due' => 'Wed 25 Nov',
                'assignee' => ['name' => 'Mireia Vidal'],
                'tags' => ['Onboarding'],
            ],
            [
                'id' => 7,
                'title' => 'Check the seat count for Ostrava Freight',
                'done' => false,
                'priority' => 'low',
                'due' => 'Fri 27 Nov',
                'assignee' => ['name' => 'Pavel Novak'],
                'tags' => ['Billing'],
            ],
        ],
        'later' => [
            [
                'id' => 8,
                'title' => 'Draft the Q1 customer success plan',
                'done' => false,
                'priority' => 'medium',
                'due' => 'Mon 7 Dec',
                'assignee' => ['name' => 'Elena Sarto'],
                'tags' => ['Planning'],
            ],
            [
                'id' => 9,
                'title' => 'Archive the inactive workspaces',
                'done' => false,
                'priority' => 'low',
                'due' => 'Fri 18 Dec',
                'assignee' => ['name' => 'Kofi Mensah'],
                'tags' => [],
            ],
        ],
    ],
    'filter' => 'all',
    'counts' => ['all' => 9, 'active' => 7, 'completed' => 2],
];
