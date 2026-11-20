<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Radio group rendered as selectable cards (plans, payment types...).
 *
 * Native radio inputs (visually hidden) drive the state, so it works without JavaScript:
 * the card highlights through `has-[:checked]` and `has-[:focus-visible]`.
 *
 * ```
 * echo $this->ChoiceCards->render('plan', [
 *     'starter' => ['title' => 'Starter', 'description' => 'For side projects'],
 *     'team' => ['title' => 'Team', 'description' => 'For small teams', 'icon' => 'users'],
 *     'enterprise' => 'Enterprise',
 * ], ['legend' => 'Choose a plan', 'value' => 'team']);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class ChoiceCardsHelper extends AbstractComponentHelper
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
        'columns' => 3,
        'classes' => [
            'legend' => 'text-sm font-medium mb-3',
            'grid' => 'grid gap-3',
            'card' => 'relative flex cursor-pointer items-start gap-3 rounded-lg border p-4 shadow-xs transition-colors hover:bg-accent/50 has-[:checked]:border-primary has-[:checked]:bg-accent/30 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring/50 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50',
            'input' => 'sr-only peer',
            'icon' => 'size-5 shrink-0 text-muted-foreground',
            'body' => 'grid gap-1',
            'title' => 'text-sm font-medium leading-none',
            'description' => 'text-sm text-muted-foreground',
        ],
        'columnClasses' => [
            1 => 'sm:grid-cols-1',
            2 => 'sm:grid-cols-2',
            3 => 'sm:grid-cols-3',
            4 => 'sm:grid-cols-4',
        ],
        'templates' => [
            'fieldset' => '<fieldset{{attrs}}>{{legend}}<div{{gridAttrs}}>{{cards}}</div></fieldset>',
            'legend' => '<legend{{attrs}}>{{content}}</legend>',
            'card' => '<label{{attrs}}>{{input}}{{icon}}<span{{bodyAttrs}}>{{title}}{{description}}</span></label>',
            'input' => '<input{{attrs}}>',
            'title' => '<span{{attrs}}>{{content}}</span>',
            'description' => '<span{{attrs}}>{{content}}</span>',
        ],
    ];

    /**
     * Renders the choice cards.
     *
     * ### Options
     *
     * - `value`: Selected value (default: request data for `$name`).
     * - `legend`: Group legend.
     * - `columns`: Columns from the `sm` breakpoint, 1 to 4 (default config `columns`, 3).
     * - `required`: Mark the radios as required.
     * - `id`: Group id (the radios get `{id}-{n}`); generated when omitted.
     * - `escape`: Escape titles, descriptions and the legend (default true).
     *
     * @param string $name Field name.
     * @param array<string|int, string|array<string, mixed>> $choices Value => title, or
     *   value => `['title' => ..., 'description' => ..., 'icon' => ..., 'disabled' => bool]`.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $name, array $choices, array $options = []): string
    {
        if (!$choices) {
            return '';
        }

        $escape = (bool)($options['escape'] ?? true);
        $selected = array_key_exists('value', $options)
            ? $options['value']
            : $this->getView()->getRequest()->getData($name);
        $groupId = (string)($options['id'] ?? $this->uniqueId('choice-cards'));
        $required = !empty($options['required']);
        $templateVars = $options['templateVars'] ?? [];

        $columns = (int)($options['columns'] ?? $this->getConfig('columns'));
        $columnClasses = (array)$this->getConfig('columnClasses');
        $columnClass = $columnClasses[$columns] ?? 'sm:grid-cols-' . $columns;

        $cards = '';
        $index = 0;
        foreach ($choices as $value => $choice) {
            $index++;
            $choice = is_array($choice) ? $choice : ['title' => (string)$choice];
            $disabled = !empty($choice['disabled']);
            $inputId = $groupId . '-' . $index;

            $input = $this->renderTemplate('input', $this->partAttributes('input', [
                'type' => 'radio',
                'name' => $name,
                'id' => $inputId,
                'value' => (string)$value,
                'checked' => $selected !== null && $selected !== '' && (string)$selected === (string)$value,
                'disabled' => $disabled,
                'required' => $required,
            ], 'sr-only peer'));

            $icon = '';
            if (!empty($choice['icon'])) {
                $icon = $this->Icon->render((string)$choice['icon'], [
                    'class' => $this->getConfig('classes.icon'),
                ]);
            }

            $title = $this->renderTemplate('title', $this->partAttributes('title'), [
                'content' => $this->text((string)($choice['title'] ?? $value), $escape),
            ]);
            $description = '';
            if (isset($choice['description']) && $choice['description'] !== '') {
                $description = $this->renderTemplate('description', $this->partAttributes('description'), [
                    'content' => $this->text((string)$choice['description'], $escape),
                ]);
            }

            $cards .= $this->renderTemplate('card', $this->partAttributes('card'), [
                'input' => $input,
                'icon' => $icon,
                'bodyAttrs' => $this->formatAttributes($this->partAttributes('body')),
                'title' => $title,
                'description' => $description,
            ]);
        }

        $legend = '';
        if (isset($options['legend']) && $options['legend'] !== '') {
            $legend = $this->renderTemplate('legend', $this->partAttributes('legend'), [
                'content' => $this->text((string)$options['legend'], $escape),
            ]);
        }

        $attributes = ['role' => 'radiogroup', 'id' => $groupId]
            + $this->rootAttributes([], $options, ['value', 'legend', 'columns', 'required', 'id']);

        return $this->renderTemplate('fieldset', $attributes, [
            'legend' => $legend,
            'gridAttrs' => $this->formatAttributes(
                $this->partAttributes('grid', [], ['grid', 'gap-3', $columnClass]),
            ),
            'cards' => $cards,
        ], $templateVars);
    }
}
