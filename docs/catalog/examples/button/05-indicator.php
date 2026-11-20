<?php
/**
 * @title Button with indicator
 * @description A dot or a count badge on top of a button, with a screen reader label.
 */
echo $this->Button->icon('bell', 'Notifications', ['variant' => 'outline', 'indicator' => true]);
echo ' ';
echo $this->Button->icon('mail', 'Inbox', ['variant' => 'outline', 'indicator' => 3, 'indicatorLabel' => '3 unread messages']);
echo ' ';
echo $this->Button->render('Messages', ['variant' => 'secondary', 'indicator' => '12']);
