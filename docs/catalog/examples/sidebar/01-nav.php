<?php
/**
 * @title Application sidebar
 * @description Groups of links with icons; the item matching the current request gets aria-current.
 */
$menu = [
    ['type' => 'group', 'label' => 'Platform', 'items' => [
        ['label' => 'Dashboard', 'url' => '/', 'icon' => 'layout-dashboard'],
        ['label' => 'Projects', 'url' => ['controller' => 'Projects', 'action' => 'index'], 'icon' => 'folder', 'badge' => '12'],
        ['label' => 'Team', 'url' => '/team', 'icon' => 'users'],
    ]],
    ['type' => 'group', 'label' => 'Documents', 'items' => [
        ['label' => 'Reports', 'url' => '/reports', 'icon' => 'file-chart'],
        ['label' => 'Invoices', 'url' => '/invoices', 'icon' => 'file-text'],
    ]],
];
// ---
echo $this->Sidebar->render($menu, [
    'id' => 'app-sidebar',
    'label' => 'Main navigation',
    'header' => '<a href="/" class="flex items-center gap-2 px-2 font-semibold">Fernway</a>',
    'class' => 'relative h-96 w-64 border',
]);
