<?php
/**
 * @title Inbox
 * @description Unread rows are highlighted with a dot, access requests get Accept and Decline buttons, and people show as avatars.
 */
$notifications = [
    ['id' => 1, 'type' => 'access', 'title' => 'Noor Haddad asked for access to Billing', 'body' => 'She joined the finance team on Monday.', 'time' => '12 min ago', 'unread' => true, 'actor' => ['name' => 'Noor Haddad']],
    ['id' => 2, 'type' => 'task', 'title' => 'Weekly export finished', 'body' => 'The usage report for 9 to 15 November is ready to download.', 'time' => '1 h ago', 'unread' => true],
    ['id' => 3, 'type' => 'message', 'title' => 'Kofi Mensah replied in Onboarding', 'body' => 'Harbor & Pine can start the migration on Thursday.', 'time' => 'Yesterday', 'unread' => false, 'actor' => ['name' => 'Kofi Mensah']],
    ['id' => 4, 'type' => 'team', 'title' => 'Ines Duarte joined Fernway', 'body' => 'Added to the Growth team as an editor.', 'time' => '2 days ago', 'unread' => false],
];
// ---
echo $this->NotificationList->render($notifications, [
    'types' => ['task' => 'Task', 'message' => 'Message', 'team' => 'Team', 'access' => 'Access'],
    'class' => 'max-w-2xl',
]);
