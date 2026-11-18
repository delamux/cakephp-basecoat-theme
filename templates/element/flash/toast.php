<?php
/**
 * Flash message rendered as a Basecoat Toast.
 *
 * Use it from a controller: $this->Flash->set('Saved', ['element' => 'toast', 'plugin' => 'BasecoatTheme', 'params' => ['category' => 'success']]);
 * Supported params: category (success|error|info|warning), description, duration, escape, action, cancel.
 * Moved into the toaster by webroot/js/basecoat-theme.js (no inline script: CSP without 'unsafe-inline').
 *
 * @var \Cake\View\View $this
 * @var string $message
 * @var array<string, mixed> $params
 */
if (!$this->helpers()->has('Toast')) {
    $this->loadHelper('Toast', ['className' => 'BasecoatTheme.Toast']);
}
$params = (array)($params ?? []);
$toast = $this->Toast->render($message, $params + ['escape' => true]);
?>
<template data-basecoat-toast data-toaster="<?= h((string)$this->Toast->getConfig('id')) ?>"><?= $toast ?></template>
