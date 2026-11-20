<?php
/**
 * A helper page: purpose, how to load the helper, then its examples.
 *
 * @var \Cake\View\View $this
 * @var array{alias: string, title: string, group: string, intro: string} $entry Registry entry.
 * @var string $examples Rendered example blocks.
 */
$load = "// src/View/AppView.php, in initialize()\n" . ($entry['alias'] === 'Form'
    ? "\$this->addHelper('Form', ['className' => 'BasecoatTheme.Form']);"
    : sprintf("\$this->addHelper('BasecoatTheme.%s');", $entry['alias']));
?>
<section class="flex flex-col gap-3">
    <p class="text-sm text-muted-foreground"><?= h($entry['group']) ?></p>
    <p class="max-w-prose text-base"><?= h($entry['intro']) ?></p>
    <details class="catalog-setup">
        <summary>How to load it</summary>
        <pre><code class="language-php"><?= h($load) ?></code></pre>
    </details>
</section>
<?= $examples ?>
