<?php
/**
 * @title Submenu, separator and footer
 * @description A collapsible submenu, a separator and a footer built with another helper.
 */
$menu = [
    ['label' => 'Inbox', 'url' => '/inbox', 'icon' => 'inbox'],
    ['label' => 'Notifications', 'url' => '/notifications', 'icon' => 'bell'],
    '-',
    ['type' => 'submenu', 'label' => 'Settings', 'icon' => 'settings-2', 'open' => true, 'items' => [
        ['label' => 'General', 'url' => '/settings/general'],
        ['label' => 'Billing', 'url' => '/settings/billing'],
        ['label' => 'Members', 'url' => '/settings/members'],
    ]],
];
// ---
echo $this->Sidebar->render($menu, [
    'id' => 'settings-sidebar',
    'footer' => $this->Button->render('Log out', ['variant' => 'ghost', 'icon' => 'log-out', 'class' => 'w-full justify-start']),
    'class' => 'relative h-96 w-64 border',
]);
