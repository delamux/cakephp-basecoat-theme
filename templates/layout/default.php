<?php
/**
 * BasecoatTheme default layout: inset sidebar + sticky header (dashboard look).
 *
 * Blocks:
 * - `title`: <title> and header heading (falls back to the `title` view var).
 * - `sidebar`: replaces the sidebar (default element `BasecoatTheme.layout/sidebar`).
 * - `header_actions`: extra controls at the right of the header.
 * - `breadcrumb`: rendered under the header title.
 * - `meta`, `css`, `script`, `scriptBottom`: usual asset blocks.
 *
 * View vars: `title`, `lang`, `sidebarMenu`, `sidebarUser`, `brand`, `basecoatCharts`,
 * `basecoatEmbed` (see README).
 *
 * @var \Cake\View\View $this
 */
$this->element('BasecoatTheme.layout/helpers');

$title = $this->fetch('title') ?: (string)$this->get('title', '');
$embed = (bool)$this->get('basecoatEmbed', false);
$sidebar = $embed ? '' : ($this->fetch('sidebar') ?: $this->element('BasecoatTheme.layout/sidebar'));
$sidebarId = (string)$this->Sidebar->getConfig('id');
?>
<!DOCTYPE html>
<html lang="<?= h((string)$this->get('lang', 'en')) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(strip_tags($title)) ?></title>
    <?= $this->fetch('meta') ?>
    <?= $this->Theme->head(['charts' => $this->get('basecoatCharts')]) ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="<?= $embed ? 'bg-background' : 'bg-sidebar' ?> font-sans antialiased">
<?= $sidebar ?>

<main class="@container/main flex min-h-svh flex-col bg-background<?= $embed ? '' : ' md:my-2 md:mr-2 md:min-h-[calc(100svh-1rem)] md:rounded-xl md:border md:shadow-sm' ?>">
    <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-2 border-b bg-background/90 px-4 backdrop-blur<?= $embed ? '' : ' md:rounded-t-xl' ?> lg:px-6">
        <?php if (!$embed) : ?>
            <?= $this->Sidebar->toggle(['class' => '-ml-1', 'target' => $sidebarId]) ?>
            <span class="mx-1 h-4 w-px bg-border" aria-hidden="true"></span>
        <?php endif; ?>
        <div class="flex min-w-0 flex-1 flex-col">
            <h1 class="truncate text-base font-medium"><?= h(strip_tags($title)) ?></h1>
            <?= $this->fetch('breadcrumb') ?>
        </div>
        <?php /* Actions never shrink: the title truncates instead. Use Button `collapse` for icon-only actions on mobile. */ ?>
        <div class="ml-auto flex shrink-0 items-center gap-1">
            <?= $this->fetch('header_actions') ?>
            <?= $this->Theme->toggle() ?>
        </div>
    </header>

    <div class="flex flex-1 flex-col gap-4 py-4 md:gap-6 md:py-6">
        <?php $flash = $this->Flash->render(); ?>
        <?php if ($flash) : ?>
            <div class="flex flex-col gap-2 px-4 lg:px-6"><?= $flash ?></div>
        <?php endif; ?>
        <?= $this->fetch('content') ?>
    </div>
</main>

<?= $this->Toast->toaster() ?>
<?= $this->fetch('scriptBottom') ?>
</body>
</html>
