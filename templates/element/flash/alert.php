<?php
/**
 * Shared flash renderer (Basecoat Alert).
 *
 * @var \Cake\View\View $this
 * @var string $type
 * @var string $message
 * @var array<string, mixed> $params
 */
if (!$this->helpers()->has('Alert')) {
    $this->loadHelper('Alert', ['className' => 'BasecoatTheme.Alert']);
}
$escape = !isset($params['escape']) || $params['escape'] !== false;
$hasTitle = isset($params['title']);
$title = $hasTitle ? (string)$params['title'] : $message;
$description = $hasTitle ? $message : ($params['description'] ?? null);

$options = ['escape' => $escape, 'class' => $params['class'] ?? null];
if (array_key_exists('icon', $params)) {
    $options['icon'] = $params['icon'];
}
if (!empty($params['dismissible'])) {
    $options['footer'] = sprintf(
        '<button type="button" class="btn" data-variant="ghost" data-size="sm" data-alert-dismiss="true">%s</button>',
        h(__d('basecoat_theme', 'Dismiss')),
    );
}

echo $this->Alert->type($type, $title, $description, array_filter($options, fn($v) => $v !== null));
