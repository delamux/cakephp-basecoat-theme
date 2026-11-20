<?php
/**
 * @title Row actions menu
 * @description An icon-only trigger aligned to the end, with an icon, a shortcut and a destructive item.
 */
echo $this->DropdownMenu->render('', [
    ['label' => 'Edit', 'url' => ['controller' => 'Projects', 'action' => 'edit', 1], 'icon' => 'pencil'],
    ['label' => 'Make a copy', 'icon' => 'copy', 'shortcut' => '⌘C'],
    '-',
    ['label' => 'Delete', 'icon' => 'trash', 'variant' => 'destructive'],
], [
    'id' => 'project-actions',
    'align' => 'end',
    'trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis-vertical', 'aria-label' => 'Actions'],
]);
