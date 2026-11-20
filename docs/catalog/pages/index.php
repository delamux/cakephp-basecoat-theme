<?php
/**
 * Catalog home (placeholder, replaced by the demo dashboard).
 *
 * Rendered with the theme's `default` layout; root-relative URLs are made relative by the generator.
 *
 * @var \Cake\View\View $this
 */
$this->assign('title', 'Overview');
?>
<div class="flex flex-col gap-4 px-4 lg:px-6">
    <h2 class="text-2xl font-semibold">Overview</h2>
    <p class="max-w-prose text-muted-foreground">
        CakePHP 5 theme plugin for Basecoat UI: one configurable View Helper per component.
    </p>
    <div>
        <?= $this->Button->link('Browse components', '/components/index.html') ?>
    </div>
</div>
