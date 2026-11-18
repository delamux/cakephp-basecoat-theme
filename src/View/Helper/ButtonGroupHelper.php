<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Button Group (`.button-group`).
 *
 * ```
 * echo $this->ButtonGroup->render([
 *     ['text' => 'Last 3 months', 'variant' => 'secondary', 'aria-pressed' => 'true'],
 *     ['text' => 'Last 30 days'],
 *     $this->Button->render('Last 7 days', ['variant' => 'outline']), // raw HTML is accepted
 * ], ['aria-label' => 'Range', 'button' => ['variant' => 'outline', 'size' => 'sm']]);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 */
class ButtonGroupHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Button'];

    /**
     * Default configuration.
     *
     * - `button`: Options applied to every button described as an array.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'orientation' => null,
        'button' => ['variant' => 'outline'],
        'templates' => [
            'group' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Renders a button group.
     *
     * ### Options
     *
     * - `orientation`: `vertical` (`data-orientation`).
     * - `button`: Options merged into every array button.
     * - `class` and any attribute (e.g. `aria-label`).
     *
     * @param array<array<string, mixed>|string> $buttons Buttons: HTML strings or arrays with
     *   `text` (and optional `url` for links) plus ButtonHelper options.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $buttons, array $options = []): string
    {
        $shared = (array)($options['button'] ?? []) + (array)$this->getConfig('button');
        $content = '';
        foreach ($buttons as $button) {
            if (is_string($button)) {
                $content .= $button;
                continue;
            }
            $button += $shared;
            $text = (string)($button['text'] ?? '');
            unset($button['text']);
            if (array_key_exists('url', $button)) {
                $url = $button['url'];
                unset($button['url']);
                $content .= $this->Button->link($text, $url, $button);
            } else {
                $content .= $this->Button->render($text, $button);
            }
        }

        $options += [
            'role' => 'group',
            'data-orientation' => $options['orientation'] ?? $this->getConfig('orientation'),
        ];
        $attributes = $this->rootAttributes('button-group', $options, ['orientation', 'button']);

        return $this->renderTemplate('group', $attributes, ['content' => $content], $options['templateVars'] ?? []);
    }
}
