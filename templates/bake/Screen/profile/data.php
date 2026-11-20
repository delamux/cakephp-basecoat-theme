<?php
declare(strict_types=1);

/**
 * Sample data of the `profile` screen: one member of the fictional Fernway workspace.
 */
return [
    'profile' => [
        'name' => 'Elena Sarto',
        'role' => 'Head of Operations',
        'location' => 'Lisbon, Portugal',
        'email' => 'elena.sarto@example.com',
        'phone' => '+351 21 555 0142',
        'timezone' => 'Europe/Lisbon (GMT+0)',
        'joined' => 'March 2021',
        'about' => 'Elena runs day-to-day operations at Fernway: onboarding new customers such as Harbor & Pine and Ostrava Freight, keeping the support rota healthy and turning recurring requests into repeatable playbooks.',
        'bio' => 'Operations lead at Fernway. Fond of checklists, long walks and good espresso.',
        'skills' => ['Operations', 'Customer onboarding', 'Process design', 'Reporting', 'Team coaching', 'Vendor management'],
        'notifications' => [
            'mentions' => true,
            'weekly_digest' => true,
            'product_news' => false,
        ],
    ],
    'stats' => [
        ['label' => 'Projects', 'value' => '14', 'change' => '+2', 'trend' => 'up', 'description' => 'this quarter', 'icon' => 'briefcase'],
        ['label' => 'Tasks closed', 'value' => '286', 'change' => '+18%', 'trend' => 'up', 'description' => 'vs. last quarter', 'icon' => 'check'],
        ['label' => 'Customers', 'value' => '23', 'change' => '0', 'trend' => 'flat', 'description' => 'in her portfolio', 'icon' => 'users'],
    ],
    'activity' => [
        ['id' => 1, 'icon' => 'check', 'text' => 'Closed the onboarding checklist for Harbor & Pine', 'time' => '2 hours ago'],
        ['id' => 2, 'icon' => 'message-circle', 'text' => 'Commented on the Quillon Studio renewal', 'time' => 'Yesterday'],
        ['id' => 3, 'icon' => 'user-plus', 'text' => 'Invited Marit Solheim to the Ostrava Freight project', 'time' => '2 days ago'],
        ['id' => 4, 'icon' => 'file-text', 'text' => 'Published the Q3 support playbook', 'time' => 'Last week'],
        ['id' => 5, 'icon' => 'calendar', 'text' => 'Scheduled the Brightmoor Clinic kick-off call', 'time' => 'Last week'],
        ['id' => 6, 'icon' => 'check', 'text' => 'Closed the invoicing review for Lantern Books', 'time' => '2 weeks ago'],
    ],
    'projects' => [
        ['id' => 1, 'name' => 'Harbor & Pine onboarding', 'customer' => 'Harbor & Pine', 'progress' => 80, 'status' => 'On track', 'due' => 'Dec 4'],
        ['id' => 2, 'name' => 'Quillon Studio renewal', 'customer' => 'Quillon Studio', 'progress' => 45, 'status' => 'At risk', 'due' => 'Dec 18'],
        ['id' => 3, 'name' => 'Ostrava Freight rollout', 'customer' => 'Ostrava Freight', 'progress' => 25, 'status' => 'On track', 'due' => 'Jan 22'],
        ['id' => 4, 'name' => 'Brightmoor Clinic kick-off', 'customer' => 'Brightmoor Clinic', 'progress' => 10, 'status' => 'Planned', 'due' => 'Feb 5'],
        ['id' => 5, 'name' => 'Lantern Books invoicing', 'customer' => 'Lantern Books', 'progress' => 100, 'status' => 'Done', 'due' => 'Nov 12'],
        ['id' => 6, 'name' => 'Support playbook v2', 'customer' => 'Fernway', 'progress' => 60, 'status' => 'On track', 'due' => 'Dec 30'],
    ],
];
