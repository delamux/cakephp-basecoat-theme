<?php
/**
 * @title Groups and headings
 * @description group items hold a label and nested items; strings become plain items.
 */
echo $this->DropdownMenu->render('My account', [
    ['type' => 'heading', 'label' => 'Signed in as jane@example.com'],
    ['type' => 'group', 'label' => 'Workspace', 'items' => [
        ['label' => 'Profile', 'icon' => 'user'],
        ['label' => 'Billing', 'icon' => 'credit-card'],
        ['label' => 'Settings', 'icon' => 'settings-2'],
    ]],
    '-',
    ['label' => 'Log out', 'icon' => 'log-out'],
], ['id' => 'account-menu', 'trigger' => ['variant' => 'outline']]);
