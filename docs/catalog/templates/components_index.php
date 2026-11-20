<?php
/**
 * Components index: one grid of helper cards per group.
 *
 * @var \Cake\View\View $this
 * @var array<string, array<string, array{alias: string, title: string, group: string, intro: string}>> $groups Entries by group, keyed by slug.
 */
?>
<p class="max-w-prose text-muted-foreground">Every component is a CakePHP View Helper. Each page shows the PHP call, the live result and the HTML it renders.</p>
<?php foreach ($groups as $group => $entries) : ?>
    <section class="flex flex-col gap-3">
        <h2 class="text-lg font-semibold"><?= h($group) ?></h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($entries as $slug => $entry) : ?>
                <a href="/components/<?= h($slug) ?>.html" class="catalog-tile">
                    <?= $this->Card->render(null, ['title' => $entry['title'], 'description' => $entry['intro'], 'titleTag' => 'h3']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
