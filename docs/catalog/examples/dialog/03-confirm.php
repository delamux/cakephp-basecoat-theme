<?php
/**
 * @title Confirmation without close button
 * @description closeButton and closeOnOverlay force an explicit choice.
 */
echo $this->Dialog->render('delete-project', null, [
    'title' => 'Delete Acme Website?',
    'description' => 'This action cannot be undone and removes all its files.',
    'trigger' => ['text' => 'Delete project', 'variant' => 'destructive', 'icon' => 'trash'],
    'closeButton' => false,
    'closeOnOverlay' => false,
    'footer' => $this->Dialog->close('Cancel', ['variant' => 'outline'])
        . $this->Button->render('Delete', ['variant' => 'destructive']),
]);
