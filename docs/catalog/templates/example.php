<?php
/**
 * One example: title, description and Preview / PHP / HTML tabs.
 *
 * @var \Cake\View\View $this
 * @var \BasecoatTheme\Docs\ExampleFile $example Example.
 * @var string $id DOM id of the example.
 * @var string $preview Live output of the example.
 * @var string $html Pretty-printed output.
 */
$codeBlock = function (string $codeId, string $language, string $code): string {
    return '<div class="catalog-code">'
        . $this->Button->icon('copy', 'Copy code', [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            'class' => 'catalog-copy',
            'data-copy' => '#' . $codeId,
        ])
        . '<pre><code id="' . h($codeId) . '" class="language-' . h($language) . '">' . h($code) . '</code></pre>'
        . '</div>';
};

$php = $codeBlock($id . '-php', 'php', $example->code);
if ($example->setup !== '') {
    $php = '<details class="catalog-setup"><summary>Setup</summary>'
        . $codeBlock($id . '-setup', 'php', $example->setup)
        . '</details>' . $php;
}

echo $this->Card->render($this->Tabs->render([
    ['tab' => 'Preview', 'panel' => '<div class="catalog-preview">' . $preview . '</div>'],
    ['tab' => 'PHP', 'panel' => $php],
    ['tab' => 'HTML', 'panel' => $codeBlock($id . '-html', 'xml', $html)],
], ['id' => $id . '-tabs']), [
    'id' => $id,
    'title' => $example->title,
    'description' => $example->description,
    'class' => 'catalog-example',
]);
