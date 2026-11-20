<?php
/**
 * @title Basic tabs
 * @description label => panel HTML pairs; the id prefixes the tab and panel ids.
 */
echo $this->Tabs->render([
    'Account' => '<p class="text-sm">Make changes to your account here.</p>',
    'Password' => '<p class="text-sm">Change your password here.</p>',
    'Notifications' => '<p class="text-sm">Choose what you want to be notified about.</p>',
], ['id' => 'settings']);
