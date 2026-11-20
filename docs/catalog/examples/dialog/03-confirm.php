<?php
/**
 * @title Confirmation without close button
 * @description closeButton and closeOnOverlay force an explicit choice.
 */
echo $this->Dialog->render('delete-dashboard', null, [
    'title' => 'Delete the Churn forecast dashboard?',
    'description' => 'This action cannot be undone and removes its 14 saved charts.',
    'trigger' => ['text' => 'Delete dashboard', 'variant' => 'destructive', 'icon' => 'trash'],
    'closeButton' => false,
    'closeOnOverlay' => false,
    'footer' => $this->Dialog->close('Cancel', ['variant' => 'outline'])
        . $this->Button->render('Delete', ['variant' => 'destructive']),
]);
