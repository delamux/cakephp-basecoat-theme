<?php
/**
 * @title Workspace switcher
 * @description A dropdown showing the current workspace with its plan and a link to add another.
 */
$workspaces = [
    ['name' => 'Northwind Labs', 'plan' => 'Pro plan', 'url' => '#northwind', 'icon' => 'layout-dashboard'],
    ['name' => 'Bluebird Studio', 'plan' => 'Free plan', 'url' => '#bluebird'],
];
// ---
echo $this->WorkspaceSwitcher->render($workspaces, ['addUrl' => '#add-workspace']);
