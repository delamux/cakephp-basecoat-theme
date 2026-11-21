<?php
/**
 * Catalog page shell: sidebar navigation, sticky header with the theme toggle, content.
 *
 * URLs are root-relative here (`/assets/...`); CatalogBuilder makes them relative to the page.
 *
 * @var \Cake\View\View $this
 * @var string $title Page title.
 * @var string $content Page content (HTML).
 * @var array<array<string, mixed>> $menu Sidebar menu.
 * @var array{name: string, url: string, icon: string} $brand Brand link.
 * @var bool $charts Whether Chart.js is needed.
 * @var string $catalogAssets Highlight.js and catalog CSS/JS tags.
 * @var string $pageAssets Tags the examples of this page added to the view blocks.
 */
$header = sprintf(
    '<a href="%s" class="flex items-center gap-2 px-2 py-1.5 text-base font-semibold">%s<span>%s</span></a>',
    h($brand['url']),
    $this->Icon->render($brand['icon'], ['class' => 'size-5']),
    h($brand['name']),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · CakePHP Basecoat Theme</title>
    <?= $this->Theme->head(['charts' => $charts]) ?>
    <?= $catalogAssets ?>
    <?= $pageAssets ?>
</head>
<body class="bg-sidebar font-sans antialiased">
<?= $this->Sidebar->render($menu, ['label' => 'Catalog navigation', 'header' => $header]) ?>

<main class="@container/main flex min-h-svh min-w-0 flex-col bg-background md:my-2 md:mr-2 md:min-h-[calc(100svh-1rem)] md:rounded-xl md:border md:shadow-sm">
    <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-2 border-b bg-background/90 px-4 backdrop-blur md:rounded-t-xl lg:px-6">
        <?= $this->Sidebar->toggle(['class' => '-ml-1']) ?>
        <span class="mx-1 h-4 w-px bg-border" aria-hidden="true"></span>
        <h1 class="min-w-0 flex-1 truncate text-base font-medium"><?= h($title) ?></h1>
        <div class="ml-auto flex shrink-0 items-center gap-1">
            <?= $this->Theme->toggle() ?>
        </div>
    </header>

    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 lg:p-6">
        <?= $content ?>
    </div>
</main>
</body>
</html>
