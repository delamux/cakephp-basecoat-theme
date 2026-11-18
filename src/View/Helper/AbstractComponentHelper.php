<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use function Cake\Core\h;

/**
 * Base class shared by every Basecoat component helper.
 *
 * Every component helper can be parametrized at three levels (lowest to highest priority):
 *
 * 1. Helper defaults (`$_defaultConfig` of each helper).
 * 2. Helper configuration, when loading it:
 *
 *    ```
 *    $this->addHelper('BasecoatTheme.Button', [
 *        'variant' => 'outline',              // default data-variant
 *        'size' => 'sm',                      // default data-size
 *        'class' => 'rounded-full',           // extra classes for the root element
 *        'attributes' => ['data-turbo' => 'false'], // extra attributes for the root element
 *        'classes' => ['icon' => 'size-3'],   // extra classes for inner parts (helper specific)
 *        'templates' => ['button' => '<button{{attrs}}>{{content}}</button>'],
 *    ]);
 *    ```
 *
 * 3. Per call `$options`: `class`, `variant`, `size`, `escape`, `templateVars`
 *    and any other key is rendered as an HTML attribute.
 *
 * Templates use CakePHP's `StringTemplate` (`{{placeholder}}` syntax), so they can be
 * changed with `setTemplates()` at runtime or loaded from a config file
 * (`'templates' => 'MyPlugin.basecoat_button'`).
 */
abstract class AbstractComponentHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Option keys that are never rendered as HTML attributes.
     *
     * @var array<string>
     */
    protected array $reservedOptions = ['variant', 'size', 'escape', 'templateVars'];

    /**
     * Counter used to generate unique DOM ids.
     *
     * @var array<string, int>
     */
    protected array $idCounters = [];

    /**
     * Builds the attribute list of the component root element.
     *
     * Merge order: base class, config `attributes`, config `class`, `variant`/`size`
     * (option or config) and finally the per call options (`class` is appended, any
     * other attribute overrides the config one).
     *
     * @param array<string>|string $baseClass Basecoat class(es) of the component, e.g. `btn`.
     * @param array<string, mixed> $options Per call options.
     * @param array<string> $exclude Option keys consumed by the caller (not rendered).
     * @return array<string, mixed>
     */
    protected function rootAttributes(array|string $baseClass, array $options, array $exclude = []): array
    {
        $attributes = (array)$this->getConfig('attributes', []);
        $attributes['class'] = $this->mergeClasses(
            $baseClass,
            $this->getConfig('class'),
            $attributes['class'] ?? null,
            $options['class'] ?? null,
        );

        $variant = array_key_exists('variant', $options) ? $options['variant'] : $this->getConfig('variant');
        if ($variant !== null && $variant !== false && $variant !== '') {
            $attributes['data-variant'] = $variant;
        }
        $size = array_key_exists('size', $options) ? $options['size'] : $this->getConfig('size');
        if ($size !== null && $size !== false && $size !== '') {
            $attributes['data-size'] = $size;
        }

        unset($options['class']);

        return $this->cleanAttributes($this->stripOptions($options, $exclude) + $attributes);
    }

    /**
     * Builds the attribute list of an inner element of the component.
     *
     * The config key `classes.<part>` lets apps add classes to a part globally.
     *
     * @param string $part Part name, e.g. `header`, `footer`, `trigger`.
     * @param array<string, mixed> $options Attributes given by the caller.
     * @param array<string>|string|null $baseClass Base class(es) of the part.
     * @return array<string, mixed>
     */
    protected function partAttributes(string $part, array $options = [], array|string|null $baseClass = null): array
    {
        $options['class'] = $this->mergeClasses(
            $baseClass,
            $this->getConfig('classes.' . $part),
            $options['class'] ?? null,
        );

        return $this->cleanAttributes($options);
    }

    /**
     * Formats an attribute array into an HTML attribute string (values are always escaped).
     *
     * @param array<string, mixed> $attributes Attributes.
     * @return string
     */
    protected function formatAttributes(array $attributes): string
    {
        unset($attributes['escape']);

        return $this->templater()->formatAttributes($attributes);
    }

    /**
     * Renders a named template with the given attributes and data.
     *
     * @param string $template Template name.
     * @param array<string, mixed> $attributes Attributes for the `{{attrs}}` placeholder.
     * @param array<string, mixed> $data Other placeholders.
     * @param array<string, mixed> $templateVars Extra template vars.
     * @return string
     */
    protected function renderTemplate(
        string $template,
        array $attributes = [],
        array $data = [],
        array $templateVars = [],
    ): string {
        $data['attrs'] = $this->formatAttributes($attributes);
        if ($templateVars) {
            $data['templateVars'] = $templateVars;
        }

        return $this->formatTemplate($template, $data);
    }

    /**
     * Escapes text when `$escape` is true.
     *
     * @param string|null $text Text.
     * @param bool $escape Whether to escape.
     * @return string
     */
    protected function text(?string $text, bool $escape = true): string
    {
        if ($text === null) {
            return '';
        }

        return $escape ? h($text) : $text;
    }

    /**
     * Merges class lists (strings or arrays), removing duplicates and empty values.
     *
     * @param mixed ...$classes Class lists.
     * @return string|null
     */
    protected function mergeClasses(mixed ...$classes): ?string
    {
        $merged = [];
        foreach ($classes as $class) {
            if ($class === null || $class === false || $class === '') {
                continue;
            }
            $list = is_array($class) ? $class : preg_split('/\s+/', trim((string)$class));
            foreach ((array)$list as $item) {
                if ($item !== '' && $item !== null && !in_array($item, $merged, true)) {
                    $merged[] = $item;
                }
            }
        }

        return $merged ? implode(' ', $merged) : null;
    }

    /**
     * Generates a unique, deterministic id for this helper instance.
     *
     * @param string $prefix Id prefix.
     * @return string
     */
    protected function uniqueId(string $prefix): string
    {
        $this->idCounters[$prefix] = ($this->idCounters[$prefix] ?? 0) + 1;

        return $prefix . '-' . $this->idCounters[$prefix];
    }

    /**
     * Removes reserved and consumed option keys.
     *
     * @param array<string, mixed> $options Options.
     * @param array<string> $exclude Keys consumed by the caller.
     * @return array<string, mixed>
     */
    protected function stripOptions(array $options, array $exclude = []): array
    {
        foreach (array_merge($this->reservedOptions, $exclude) as $key) {
            unset($options[$key]);
        }

        return $options;
    }

    /**
     * Removes attributes whose value is null/false and empty classes.
     *
     * @param array<string, mixed> $attributes Attributes.
     * @return array<string, mixed>
     */
    protected function cleanAttributes(array $attributes): array
    {
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = $this->mergeClasses($attributes['class']);
        }

        return array_filter($attributes, fn($value) => $value !== null && $value !== false && $value !== '');
    }

    /**
     * Returns `true`/`false` as the string used by ARIA attributes.
     *
     * @param bool $value Value.
     * @return string
     */
    protected function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
