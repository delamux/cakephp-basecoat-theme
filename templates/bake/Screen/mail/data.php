<?php
declare(strict_types=1);

/**
 * Sample data of the `mail` screen: a mailbox of the fictional Fernway workspace.
 *
 * View variables: `folders` (key, label, icon, count), `labels` (key => label), `messages` (the
 * list in the middle pane, newest first; `labels` holds label keys, `body` the paragraphs),
 * `currentFolder` (folder key), `filter` (`all` or `unread`) and `selected` (id of the open
 * message, or null).
 */
return [
    'folders' => [
        ['key' => 'inbox', 'label' => 'Inbox', 'icon' => 'inbox', 'count' => 4],
        ['key' => 'starred', 'label' => 'Starred', 'icon' => 'star', 'count' => 2],
        ['key' => 'sent', 'label' => 'Sent', 'icon' => 'send', 'count' => 0],
        ['key' => 'drafts', 'label' => 'Drafts', 'icon' => 'file-pen', 'count' => 3],
        ['key' => 'archive', 'label' => 'Archive', 'icon' => 'archive', 'count' => 0],
        ['key' => 'spam', 'label' => 'Spam', 'icon' => 'shield-alert', 'count' => 0],
        ['key' => 'trash', 'label' => 'Trash', 'icon' => 'trash', 'count' => 0],
    ],
    'labels' => [
        'customers' => 'Customers',
        'billing' => 'Billing',
        'team' => 'Team',
        'planning' => 'Planning',
    ],
    'currentFolder' => 'inbox',
    'filter' => 'all',
    'selected' => 1,
    'messages' => [
        [
            'id' => 1,
            'from' => ['name' => 'Kofi Mensah', 'email' => 'kofi.mensah@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Harbor & Pine onboarding: field mapping',
            'snippet' => 'The data migration can start on Thursday if we confirm the field mapping today.',
            'time' => '09:42',
            'date' => 'Fri, 20 Nov 2026, 09:42',
            'unread' => true,
            'starred' => true,
            'labels' => ['customers'],
            'body' => [
                'Hi Elena,',
                'The data migration for Harbor & Pine can start on Thursday if we confirm the field mapping today. I attached nothing yet: the list is short enough to paste here.',
                'Customer name, billing contact and plan map one to one. Their legacy "region" field needs a decision: we can drop it or keep it as a free text tag.',
                'Tell me which one you prefer and I will book the slot.',
                'Kofi',
            ],
        ],
        [
            'id' => 2,
            'from' => ['name' => 'Ines Duarte', 'email' => 'ines.duarte@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Q4 plan: comments open until Friday',
            'snippet' => 'I shared the draft of the Q4 plan. Please add your comments before the end of the week.',
            'time' => '08:15',
            'date' => 'Fri, 20 Nov 2026, 08:15',
            'unread' => true,
            'starred' => false,
            'labels' => ['planning', 'team'],
            'body' => [
                'Hello,',
                'I shared the draft of the Q4 plan. Please add your comments before the end of the week so we can lock the scope on Monday.',
                'Ines',
            ],
        ],
        [
            'id' => 3,
            'from' => ['name' => 'Pavel Novak', 'email' => 'pavel.novak@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Ostrava Freight: invoice question',
            'snippet' => 'Our finance team sees two lines for the November seats. Could you check?',
            'time' => 'Yesterday',
            'date' => 'Thu, 19 Nov 2026, 16:30',
            'unread' => true,
            'starred' => false,
            'labels' => ['billing', 'customers'],
            'body' => [
                'Hi Elena,',
                'Our finance team sees two lines for the November seats on the latest invoice. Could you check whether one of them is a duplicate?',
                'Thanks,',
                'Pavel',
            ],
        ],
        [
            'id' => 4,
            'from' => ['name' => 'Noor Haddad', 'email' => 'noor.haddad@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Access to Billing',
            'snippet' => 'Thanks for approving my request. I can see the invoices now.',
            'time' => 'Yesterday',
            'date' => 'Thu, 19 Nov 2026, 11:05',
            'unread' => true,
            'starred' => true,
            'labels' => ['team'],
            'body' => [
                'Thanks for approving my request. I can see the invoices now.',
                'Noor',
            ],
        ],
        [
            'id' => 5,
            'from' => ['name' => 'Mireia Vidal', 'email' => 'mireia.vidal@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Brightmoor Clinic renewal',
            'snippet' => 'They want to add five seats at renewal. Can we keep the current price?',
            'time' => 'Mon',
            'date' => 'Mon, 16 Nov 2026, 14:20',
            'unread' => false,
            'starred' => false,
            'labels' => ['customers', 'billing'],
            'body' => [
                'Hi Elena,',
                'Brightmoor Clinic wants to add five seats at renewal. Can we keep the current price per seat?',
                'Mireia',
            ],
        ],
        [
            'id' => 6,
            'from' => ['name' => 'Quillon Studio', 'email' => 'hello@example.com'],
            'to' => 'Elena Sarto',
            'subject' => 'Welcome to the Scale plan',
            'snippet' => 'Your workspace now has 25 seats and priority support.',
            'time' => 'Sun',
            'date' => 'Sun, 15 Nov 2026, 10:00',
            'unread' => false,
            'starred' => false,
            'labels' => [],
            'body' => [
                'Your workspace now has 25 seats and priority support. Reply to this message if you need anything.',
            ],
        ],
    ],
];
