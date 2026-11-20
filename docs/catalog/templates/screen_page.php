<?php
/**
 * A screen page: what it is, the bake command, the live preview and the baked files.
 *
 * URLs are root-relative here; CatalogBuilder makes them relative to the page.
 *
 * @var \Cake\View\View $this
 * @var string $slug Screen slug.
 * @var array{title: string, description: string, controller: string, path: string, icon?: string} $screen Registry entry.
 * @var array{command: string, controller: string, files: array<string, string>, template: string, data: array<string, mixed>} $baked ScreenBuilder::bake() result.
 */
$codeBlock = function (string $codeId, string $language, string $code, string $copyLabel): string {
    return '<div class="catalog-code">'
        . $this->Button->icon('copy', $copyLabel, [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            'class' => 'catalog-copy',
            'data-copy' => '#' . $codeId,
        ])
        . '<pre><code id="' . h($codeId) . '" class="language-' . h($language) . '">' . h($code) . '</code></pre>'
        . '</div>';
};

$preview = '/screens/' . $slug . '/preview.html';
$tabs = [];
foreach ($baked['files'] as $path => $code) {
    $isController = str_starts_with($path, 'src/');
    $key = $isController ? 'controller' : 'template';
    $tabs[] = [
        'tab' => $isController ? 'Controller' : 'Template',
        'panel' => '<p class="catalog-file">' . $this->Icon->render('file-text', ['class' => 'size-4']) . '<code>' . h($path) . '</code></p>'
            . $codeBlock('screen-' . $key . '-code', $isController ? 'php' : 'php-template', $code, 'Copy ' . $key . ' code'),
    ];
}
?>
<section class="flex flex-col gap-3">
    <p class="max-w-prose text-base"><?= h($screen['description']) ?></p>
    <p class="max-w-prose text-sm text-muted-foreground">
        Bake it into your app with the command below: you get a controller whose <code>index()</code> sets sample data,
        and an <code>index</code> template built with the theme helpers. Replace the sample data with your own queries.
        Needs <code>cakephp/bake</code> (<code>composer require --dev cakephp/bake</code>).
    </p>
    <?= $codeBlock('screen-command', 'bash', $baked['command'], 'Copy command') ?>
    <p class="text-sm text-muted-foreground">
        Pass a controller name to bake it under another name (<code><?= h($baked['command']) ?> TeamInbox</code>),
        <code>--plugin</code> to bake it into a plugin and <code>--force</code> to overwrite existing files.
    </p>
</section>

<?= $this->Card->render(
    '<div class="catalog-screen-preview"><iframe src="' . h($preview) . '" title="' . h($screen['title']) . ' screen preview" class="catalog-screen-frame" loading="lazy"></iframe></div>',
    [
        'id' => 'screen-preview',
        'title' => 'Preview',
        'description' => 'The baked template with its sample data, in the theme\'s default layout.',
        'action' => $this->Button->link('Open full page<span class="sr-only"> (opens in a new tab)</span>', $preview, [
            'escape' => false,
            'variant' => 'outline',
            'size' => 'sm',
            'icon' => 'arrow-up-right',
            'iconPosition' => 'end',
            'target' => '_blank',
        ]),
        'class' => 'catalog-example',
    ],
) ?>

<?= $this->Card->render($this->Tabs->render($tabs, ['id' => 'screen-code-tabs']), [
    'id' => 'screen-code',
    'title' => 'Generated files',
    'description' => 'Exactly what the command writes, paths relative to your app.',
    'class' => 'catalog-example catalog-screen-code',
]) ?>
