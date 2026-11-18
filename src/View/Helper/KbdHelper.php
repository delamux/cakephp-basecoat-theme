<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Kbd (`.kbd`).
 *
 * ```
 * echo $this->Kbd->render('⌘K');
 * echo $this->Kbd->render(['Ctrl', 'Shift', 'P']); // grouped with "+"
 * ```
 */
class KbdHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'separator' => '<span>+</span>',
        'classes' => [
            'group' => 'inline-flex items-center gap-1',
        ],
        'templates' => [
            'kbd' => '<kbd{{attrs}}>{{content}}</kbd>',
            'group' => '<span{{attrs}}>{{content}}</span>',
        ],
    ];

    /**
     * Renders one key, or a group of keys.
     *
     * ### Options
     *
     * - `separator`: HTML between grouped keys.
     * - `groupOptions`: Attributes of the group wrapper.
     * - `escape`: Escape keys (default true).
     * - `class` and any attribute for each `<kbd>`.
     *
     * @param array<string>|string $keys Key(s).
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array|string $keys, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $attributes = $this->rootAttributes('kbd', $options, ['separator', 'groupOptions']);
        $render = fn(string $key): string => $this->renderTemplate('kbd', $attributes, ['content' => $this->text($key, $escape)]);

        if (is_string($keys)) {
            return $render($keys);
        }

        return $this->renderTemplate(
            'group',
            $this->partAttributes('group', (array)($options['groupOptions'] ?? [])),
            ['content' => implode((string)($options['separator'] ?? $this->getConfig('separator')), array_map($render, $keys))],
        );
    }
}
