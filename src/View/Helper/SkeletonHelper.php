<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Basecoat Skeleton (`.skeleton`) placeholders.
 *
 * ```
 * echo $this->Skeleton->render(['class' => 'h-4 w-[250px]']);
 * echo $this->Skeleton->lines(3);
 * ```
 */
class SkeletonHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => 'rounded-md bg-accent',
        'attributes' => ['aria-hidden' => 'true'],
        'lineClass' => 'h-4 w-full',
        'classes' => [
            'lines' => 'flex flex-col gap-2',
        ],
        'templates' => [
            'skeleton' => '<div{{attrs}}></div>',
            'lines' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Renders one skeleton block.
     *
     * @param array<string, mixed> $options `class` (size) and any attribute.
     * @return string
     */
    public function render(array $options = []): string
    {
        return $this->renderTemplate('skeleton', $this->rootAttributes('skeleton', $options), [], $options['templateVars'] ?? []);
    }

    /**
     * Renders `$count` text lines (the last one shorter).
     *
     * @param int $count Number of lines.
     * @param array<string, mixed> $options `lineClass`, `class` and attributes of the wrapper.
     * @return string
     */
    public function lines(int $count = 3, array $options = []): string
    {
        $lineClass = $options['lineClass'] ?? $this->getConfig('lineClass');
        $content = '';
        for ($i = 1; $i <= $count; $i++) {
            $content .= $this->render(['class' => $i === $count && $count > 1 ? $lineClass . ' w-3/4' : $lineClass]);
        }
        unset($options['lineClass']);

        return $this->renderTemplate('lines', $this->partAttributes('lines', $options), ['content' => $content]);
    }
}
