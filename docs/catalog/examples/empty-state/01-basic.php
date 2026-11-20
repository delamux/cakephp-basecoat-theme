<?php
/**
 * @title Empty state with action
 * @description An icon, a title, a description and call-to-action buttons.
 */
echo $this->EmptyState->render('No projects yet', [
    'description' => 'Create your first project to get started.',
    'icon' => 'inbox',
    'actions' => $this->Button->link('New project', ['controller' => 'Projects', 'action' => 'add'], ['icon' => 'plus'])
        . $this->Button->render('Import', ['variant' => 'outline']),
]);
