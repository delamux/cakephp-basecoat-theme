<?php
declare(strict_types=1);

/**
 * Sample data of the `notifications` screen: an inbox of the fictional Fernway workspace.
 *
 * View variables: `notifications` (this page), `unreadCount` (unread in the whole inbox), `types`
 * (value => label, also the toolbar filter), `total`, `page`, `perPage`.
 */
return [
    'notifications' => [
        [
            'id' => 1,
            'type' => 'access',
            'title' => 'Noor Haddad asked for access to Billing',
            'body' => 'Noor joined the finance team on Monday and needs to download invoices.',
            'time' => '12 min ago',
            'unread' => true,
            'actor' => ['name' => 'Noor Haddad'],
        ],
        [
            'id' => 2,
            'type' => 'task',
            'title' => 'Weekly usage export finished',
            'body' => 'The report for 9 to 15 November is ready: 12,486 active users across 5 customers.',
            'time' => '1 hour ago',
            'unread' => true,
        ],
        [
            'id' => 3,
            'type' => 'message',
            'title' => 'Kofi Mensah replied in Harbor & Pine onboarding',
            'body' => 'The data migration can start on Thursday if we confirm the field mapping today.',
            'time' => '3 hours ago',
            'unread' => true,
            'actor' => ['name' => 'Kofi Mensah'],
        ],
        [
            'id' => 4,
            'type' => 'access',
            'title' => 'Pavel Novak asked for access to Reports',
            'body' => 'Pavel leads support at Ostrava Freight and wants to share dashboards with his team.',
            'time' => '5 hours ago',
            'unread' => true,
            'actor' => ['name' => 'Pavel Novak'],
        ],
        [
            'id' => 5,
            'type' => 'team',
            'title' => 'Ines Duarte joined the Growth team',
            'body' => 'Ines was added as an editor and can now publish campaign segments.',
            'time' => 'Yesterday',
            'unread' => true,
        ],
        [
            'id' => 6,
            'type' => 'task',
            'title' => 'Renewal review for Lantern Books is due Friday',
            'body' => 'The Starter plan renews on 28 November. Check the seat count before then.',
            'time' => 'Yesterday',
            'unread' => false,
        ],
        [
            'id' => 7,
            'type' => 'message',
            'title' => 'Elena Sarto mentioned you in Churn review',
            'body' => 'Can you add the Brightmoor Clinic cohort to the November chart before the call?',
            'time' => '2 days ago',
            'unread' => false,
            'actor' => ['name' => 'Elena Sarto'],
        ],
        [
            'id' => 8,
            'type' => 'team',
            'title' => 'Quillon Studio moved to the Scale plan',
            'body' => 'Their workspace now has 25 seats and priority support.',
            'time' => '3 days ago',
            'unread' => false,
        ],
        [
            'id' => 9,
            'type' => 'task',
            'title' => 'Payment failed for Lantern Books',
            'body' => 'The card ending in 7719 was declined. We will retry automatically on Monday.',
            'time' => '4 days ago',
            'unread' => false,
        ],
        [
            'id' => 10,
            'type' => 'message',
            'title' => 'Mireia Vidal shared the Q4 plan',
            'body' => 'Comments are open until the end of the week.',
            'time' => '5 days ago',
            'unread' => false,
            'actor' => ['name' => 'Mireia Vidal'],
        ],
    ],
    'unreadCount' => 5,
    'types' => [
        'task' => 'Task',
        'message' => 'Message',
        'team' => 'Team',
        'access' => 'Access',
    ],
    'total' => 24,
    'page' => 1,
    'perPage' => 10,
];
