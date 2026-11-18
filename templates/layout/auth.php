<?php
/**
 * BasecoatTheme centered layout for login, register, password reset...
 *
 * @var \Cake\View\View $this
 */
$this->element('BasecoatTheme.layout/helpers');
$title = $this->fetch('title') ?: (string)$this->get('title', '');
$brand = (array)$this->get('brand', \Cake\Core\Configure::read('BasecoatTheme.brand', []));
?>
<!DOCTYPE html>
<html lang="<?= h((string)$this->get('lang', 'en')) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(strip_tags($title)) ?></title>
    <?= $this->fetch('meta') ?>
    <?= $this->Theme->head() ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="bg-muted font-sans antialiased">
<div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
    <div class="flex w-full max-w-sm flex-col gap-6">
        <?php if (!empty($brand['name'])) : ?>
            <a href="<?= $this->Url->build($brand['url'] ?? '/') ?>" class="flex items-center gap-2 self-center font-medium">
                <?= $this->Icon->optional($brand['icon'] ?? null, ['class' => 'size-5']) ?>
                <span><?= h($brand['name']) ?></span>
            </a>
        <?php endif; ?>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>
    <div class="fixed right-4 top-4"><?= $this->Theme->toggle() ?></div>
</div>
<?= $this->Toast->toaster() ?>
<?= $this->fetch('scriptBottom') ?>
</body>
</html>
