<?php
/**
 * @title Single toast with action
 * @description Categories choose the icon; action and cancel render buttons linking to a URL.
 */
echo $this->Toast->render('Event created', [
    'category' => 'info',
    'description' => 'Friday, March 6 at 10:00 AM.',
    'action' => ['label' => 'Undo', 'url' => '/events/undo'],
    'cancel' => ['label' => 'Dismiss', 'url' => '#'],
]);
