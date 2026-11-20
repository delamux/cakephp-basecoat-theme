<?php
declare(strict_types=1);

/**
 * Sample data of the `chats` screen: the messenger of the fictional Fernway workspace.
 *
 * View variables: `chats` (conversation list: id, name, group, members, status, preview, time,
 * unread, and optional `subtitle` + `messages`: the thread that opens when the conversation is
 * selected; the open one comes from `activeChat`), `activeChat` (the open conversation: id, name,
 * group, status, subtitle, messages) and `statuses` (status value => label). Message `from` is `me` or `them`; `status` of a sent message
 * is `sent`, `delivered` or `read`.
 */
return [
    'chats' => [
        ['id' => 1, 'name' => 'Kofi Mensah', 'group' => false, 'status' => 'online', 'preview' => 'The data migration can start on Thursday.', 'time' => '10:42', 'unread' => 2],
        [
            'id' => 2, 'name' => 'Harbor & Pine onboarding', 'group' => true, 'members' => 6, 'status' => 'online', 'preview' => 'Ines: I uploaded the field mapping.', 'time' => '10:15', 'unread' => 5,
            'subtitle' => 'Harbor & Pine, 6 members',
            'messages' => [
                ['text' => 'Kickoff notes are in the shared folder.', 'from' => 'them', 'author' => 'Ines Duarte', 'time' => '10:02'],
                ['text' => 'Plan and seats columns are still open.', 'from' => 'them', 'author' => 'Ines Duarte', 'time' => '10:09'],
                ['text' => 'I will map them after lunch.', 'from' => 'me', 'time' => '10:11', 'status' => 'read'],
                ['text' => 'I uploaded the field mapping.', 'from' => 'them', 'author' => 'Ines Duarte', 'time' => '10:15'],
            ],
        ],
        [
            'id' => 3, 'name' => 'Sofia Brandt', 'group' => false, 'status' => 'away', 'preview' => 'Can you add the Brightmoor cohort to the chart?', 'time' => '09:30', 'unread' => 0,
            'subtitle' => 'Data analyst, Brightmoor Clinic',
            'messages' => [
                ['text' => 'Morning! Do you have a minute for the retention chart?', 'from' => 'them', 'time' => '09:12'],
                ['text' => 'Sure, what do you need?', 'from' => 'me', 'time' => '09:20', 'status' => 'read'],
                ['text' => 'Can you add the Brightmoor cohort to the chart?', 'from' => 'them', 'time' => '09:30'],
            ],
        ],
        [
            'id' => 4, 'name' => 'Pavel Novak', 'group' => false, 'status' => 'offline', 'preview' => 'Thanks, the dashboards look great.', 'time' => 'Yesterday', 'unread' => 0,
            'subtitle' => 'Finance, Ostrava Freight',
            'messages' => [
                ['text' => 'The November invoice is ready for your review.', 'from' => 'me', 'time' => 'Yesterday', 'status' => 'read'],
                ['text' => 'Thanks, the dashboards look great.', 'from' => 'them', 'time' => 'Yesterday'],
            ],
        ],
        [
            'id' => 5, 'name' => 'Growth team', 'group' => true, 'members' => 4, 'status' => 'online', 'preview' => 'Noor: Segments are published.', 'time' => 'Yesterday', 'unread' => 0,
            'subtitle' => 'Growth team, 4 members',
            'messages' => [
                ['text' => 'Segments are published.', 'from' => 'them', 'author' => 'Noor Haddad', 'time' => 'Yesterday'],
                ['text' => 'Great, I will announce them on Monday.', 'from' => 'me', 'time' => 'Yesterday', 'status' => 'delivered'],
            ],
        ],
        [
            'id' => 6, 'name' => 'Noor Haddad', 'group' => false, 'status' => 'offline', 'preview' => 'I will send the invoice list tomorrow.', 'time' => 'Tuesday', 'unread' => 0,
            'subtitle' => 'Billing, Lantern Books',
            'messages' => [
                ['text' => 'I will send the invoice list tomorrow.', 'from' => 'them', 'time' => 'Tuesday'],
                ['text' => 'Perfect, thank you.', 'from' => 'me', 'time' => 'Tuesday', 'status' => 'read'],
            ],
        ],
        [
            'id' => 7, 'name' => 'Mireia Vidal', 'group' => false, 'status' => 'away', 'preview' => 'Comments on the Q4 plan are open.', 'time' => 'Monday', 'unread' => 0,
            'subtitle' => 'Product, Quillon Studio',
            'messages' => [
                ['text' => 'Comments on the Q4 plan are open.', 'from' => 'them', 'time' => 'Monday'],
                ['text' => 'I will add mine today.', 'from' => 'me', 'time' => 'Monday', 'status' => 'sent'],
            ],
        ],
    ],
    'activeChat' => [
        'id' => 1,
        'name' => 'Kofi Mensah',
        'group' => false,
        'status' => 'online',
        'subtitle' => 'Customer success, Harbor & Pine',
        'messages' => [
            ['text' => 'Hi Kofi, did Harbor & Pine confirm the field mapping?', 'from' => 'me', 'time' => '10:31', 'status' => 'read'],
            ['text' => 'Almost. They changed two columns this morning.', 'from' => 'them', 'time' => '10:36'],
            ['text' => 'Which ones? I can update the import before Thursday.', 'from' => 'me', 'time' => '10:38', 'status' => 'read'],
            ['text' => 'Plan and seats. I will share the new sheet in a minute.', 'from' => 'them', 'time' => '10:40'],
            ['text' => 'The data migration can start on Thursday.', 'from' => 'them', 'time' => '10:42'],
            ['text' => 'Perfect, I will block the morning for it.', 'from' => 'me', 'time' => '10:43', 'status' => 'delivered'],
        ],
    ],
    'statuses' => [
        'online' => 'Online',
        'away' => 'Away',
        'offline' => 'Offline',
    ],
];
