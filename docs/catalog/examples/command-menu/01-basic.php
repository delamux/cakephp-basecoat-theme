<?php
/**
 * @title Command palette
 * @description A search button that opens a command dialog with grouped, filterable actions.
 */
$groups = [
    'Actions' => [
        ['label' => 'New project', 'url' => '#new-project', 'icon' => 'plus', 'shortcut' => 'N', 'keywords' => 'create add'],
        ['label' => 'Invite people', 'url' => '#invite', 'icon' => 'user', 'shortcut' => 'I', 'keywords' => 'team member'],
    ],
    'Preferences' => [
        ['label' => 'Toggle theme', 'icon' => 'sun', 'keywords' => 'dark light mode'],
        ['label' => 'Settings', 'url' => '#settings', 'icon' => 'list', 'shortcut' => ['Ctrl', ',']],
    ],
];
// ---
echo $this->CommandMenu->trigger('demo-command-menu');
echo $this->CommandMenu->render($groups, ['id' => 'demo-command-menu']);
