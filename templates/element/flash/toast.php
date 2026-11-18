<?php
/**
 * Flash message rendered as a Basecoat Toast.
 *
 * Use it from a controller: $this->Flash->set('Saved', ['element' => 'toast', 'plugin' => 'BasecoatTheme', 'params' => ['category' => 'success']]);
 * Supported params: category (success|error|info|warning), description, duration, escape, action, cancel.
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
<template data-basecoat-toast><?= $toast ?></template>
<script>
(() => {
    const tpl = document.currentScript.previousElementSibling;
    const move = () => {
        const toaster = document.getElementById('<?= h((string)$this->Toast->getConfig('id')) ?>');
        if (!toaster) return;
        toaster.appendChild(tpl.content.cloneNode(true));
        tpl.remove();
        window.basecoat && window.basecoat.init && window.basecoat.init('toast');
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', move) : move();
})();
</script>
