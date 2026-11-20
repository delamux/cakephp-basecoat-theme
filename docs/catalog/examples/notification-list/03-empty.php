<?php
/**
 * @title Empty inbox
 * @description Text shown when there are no notifications; use EmptyState for a richer screen.
 */
echo $this->NotificationList->render([], ['empty' => 'You are all caught up.']);
