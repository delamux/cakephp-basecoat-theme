<?php
/**
 * Screens index: one card per screen with its description and bake command.
 *
 * @var \Cake\View\View $this
 * @var array<string, array{title: string, description: string, controller: string, path: string, icon?: string}> $screens Screens by slug.
 */
?>
<section class="flex flex-col gap-3">
    <p class="max-w-prose text-muted-foreground">
        Ready-made screens you can bake into your app with <code>bin/cake bake screen &lt;screen&gt;</code>:
        a controller with sample data and a template built with the theme helpers. Each page shows a live preview
        and the exact files the command writes.
    </p>
    <?php if (!$screens) : ?>
        <p class="text-sm text-muted-foreground">No screens yet.</p>
    <?php else : ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($screens as $slug => $screen) : ?>
                <?php $titleId = 'screen-' . $slug . '-title'; ?>
                <a href="/screens/<?= h($slug) ?>.html" class="catalog-tile" aria-labelledby="<?= h($titleId) ?>">
                    <?= $this->Card->render(
                        '<code class="text-xs text-muted-foreground">bin/cake bake screen ' . h($slug) . '</code>',
                        ['title' => $screen['title'], 'description' => $screen['description'], 'titleTag' => 'h2', 'titleOptions' => ['id' => $titleId]],
                    ) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
