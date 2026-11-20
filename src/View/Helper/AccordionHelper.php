<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Accordion (`.accordion` with native `<details>`). Requires Basecoat JS
 * for single-open behaviour.
 *
 * ```
 * echo $this->Accordion->render([
 *     'Can I export raw events?' => 'Yes. Scheduled exports run daily.',
 *     ['title' => 'Is SSO included?', 'content' => '<p>On the Scale plan.</p>', 'open' => true],
 *     ['title' => 'Locked', 'content' => '...', 'disabled' => true],
 * ], ['multiple' => true]);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class AccordionHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'multiple' => false,
        'icon' => 'chevron-down',
        'classes' => [
            'item' => null,
            'summary' => null,
            'content' => null,
        ],
        'templates' => [
            'accordion' => '<div{{attrs}}>{{content}}</div>',
            'item' => '<details{{attrs}}><summary{{summaryAttrs}}>{{title}}{{icon}}</summary><div{{contentAttrs}}>{{content}}</div></details>',
        ],
    ];

    /**
     * Renders an accordion.
     *
     * Items: `title => contentHtml` or arrays with `title`, `content`, `open`, `disabled`,
     * `escape`, `attributes`, `summaryOptions`, `contentOptions`.
     *
     * ### Options
     *
     * - `multiple`: Allow several items open at once (`data-multiple`).
     * - `icon`: Chevron icon (false for none).
     * - `escape`: Escape titles (default true). Contents are never escaped.
     * - `class` and any attribute for the root.
     *
     * @param array<string|int, mixed> $items Items.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $items, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $iconName = array_key_exists('icon', $options) ? $options['icon'] : $this->getConfig('icon');
        $icon = $iconName ? $this->Icon->render((string)$iconName) : '';

        $content = '';
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                $item = ['title' => (string)$key, 'content' => $item];
            }
            $attributes = (array)($item['attributes'] ?? []) + [
                'open' => !empty($item['open']),
                'aria-disabled' => !empty($item['disabled']) ? 'true' : null,
            ];
            $content .= $this->formatTemplate('item', [
                'attrs' => $this->formatAttributes($this->partAttributes('item', $attributes)),
                'summaryAttrs' => $this->formatAttributes($this->partAttributes('summary', (array)($item['summaryOptions'] ?? []))),
                'contentAttrs' => $this->formatAttributes($this->partAttributes('content', (array)($item['contentOptions'] ?? []))),
                'title' => $this->text((string)($item['title'] ?? ''), (bool)($item['escape'] ?? $escape)),
                'icon' => $icon,
                'content' => (string)($item['content'] ?? ''),
            ]);
        }

        $multiple = $options['multiple'] ?? $this->getConfig('multiple');
        $options['data-multiple'] = $multiple ? 'true' : null;
        $attributes = $this->rootAttributes('accordion', $options, ['multiple', 'icon']);

        return $this->renderTemplate('accordion', $attributes, ['content' => $content], $options['templateVars'] ?? []);
    }
}
