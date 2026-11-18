<?php
/**
 * Flash message rendered as a Basecoat Alert (error).
 *
 * Supported params: title, description, icon, escape, dismissible (bool), class.
 *
 * @var \Cake\View\View $this
 * @var string $message
 * @var array<string, mixed> $params
 */
echo $this->element('BasecoatTheme.flash/alert', [
    'type' => 'error',
    'message' => $message,
    'params' => $params ?? [],
]);
