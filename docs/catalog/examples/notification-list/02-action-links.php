<?php
/**
 * @title POST actions and custom actions
 * @description With the "urls" option Accept and Decline post to your actions (CSRF token included), titles can link to the source, and a row can bring its own actions.
 */
$notifications = [
    ['id' => 41, 'type' => 'access', 'title' => 'Pavel Novak asked for access to Reports', 'time' => '3 h ago', 'unread' => true, 'actor' => ['name' => 'Pavel Novak']],
    ['id' => 42, 'type' => 'task', 'title' => 'Review the Lantern Books renewal', 'url' => '#renewal', 'time' => 'Yesterday', 'unread' => false],
    ['id' => 43, 'type' => 'team', 'title' => 'Quillon Studio invited you to a shared workspace', 'time' => '3 days ago', 'unread' => false, 'actions' => '<a class="btn" data-size="sm" data-variant="outline" href="#invite">View invite</a>'],
];
// ---
echo $this->NotificationList->render($notifications, [
    'types' => ['task' => 'Task', 'team' => 'Team', 'access' => 'Access'],
    'urls' => ['accept' => '/requests/{id}/accept', 'decline' => '/requests/{id}/decline'],
    'class' => 'max-w-2xl',
]);
