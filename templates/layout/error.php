<?php
/**
 * BasecoatTheme error layout.
 *
 * @var \Cake\View\View $this
 */
$this->element('BasecoatTheme.layout/helpers');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(strip_tags((string)$this->fetch('title'))) ?></title>
    <?= $this->fetch('meta') ?>
    <?= $this->Theme->head() ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="bg-background font-sans antialiased">
<main class="mx-auto flex min-h-svh max-w-3xl flex-col justify-center gap-6 p-6">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
    <div>
        <?= $this->Button->link(__d('basecoat_theme', 'Back'), '/', ['variant' => 'outline', 'icon' => 'arrow-left', 'data-history-back' => 'true']) ?>
    </div>
</main>
</body>
</html>
