<?php
declare(strict_types=1);

/**
 * Sample data of the `chats` screen: the messenger of the fictional Fernway workspace.
 *
 * View variables: `chats` (conversation list: id, name, group, members, status, preview, time,
 * unread), `activeChat` (the open conversation: id, name, group, status, subtitle, messages) and
 * `statuses` (status value => label). Message `from` is `me` or `them`; `status` of a sent message
 * is `sent`, `delivered` or `read`.
 */
return [
    'chats' => [
        ['id' => 1, 'name' => 'Kofi Mensah', 'group' => false, 'status' => 'online', 'preview' => 'The data migration can start on Thursday.', 'time' => '10:42', 'unread' => 2],
        ['id' => 2, 'name' => 'Harbor & Pine onboarding', 'group' => true, 'members' => 6, 'status' => 'online', 'preview' => 'Ines: I uploaded the field mapping.', 'time' => '10:15', 'unread' => 5],
        ['id' => 3, 'name' => 'Sofia Brandt', 'group' => false, 'status' => 'away', 'preview' => 'Can you add the Brightmoor cohort to the chart?', 'time' => '09:30', 'unread' => 0],
        ['id' => 4, 'name' => 'Pavel Novak', 'group' => false, 'status' => 'offline', 'preview' => 'Thanks, the dashboards look great.', 'time' => 'Yesterday', 'unread' => 0],
        ['id' => 5, 'name' => 'Growth team', 'group' => true, 'members' => 4, 'status' => 'online', 'preview' => 'Noor: Segments are published.', 'time' => 'Yesterday', 'unread' => 0],
        ['id' => 6, 'name' => 'Noor Haddad', 'group' => false, 'status' => 'offline', 'preview' => 'I will send the invoice list tomorrow.', 'time' => 'Tuesday', 'unread' => 0],
        ['id' => 7, 'name' => 'Mireia Vidal', 'group' => false, 'status' => 'away', 'preview' => 'Comments on the Q4 plan are open.', 'time' => 'Monday', 'unread' => 0],
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
