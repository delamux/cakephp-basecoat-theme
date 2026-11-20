<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Date range field: a Basecoat popover whose trigger shows the chosen range and whose panel holds
 * two `<input type="date">` named `{name}[from]` and `{name}[to]` (the shape read by the
 * `BasecoatTheme.DateRange` search filter).
 *
 * ```
 * echo $this->DateRangeField->render('created', [
 *     'label' => 'Created',
 *     'presets' => ['This month' => ['2026-11-01', '2026-11-30']],
 * ]);
 * ```
 *
 * The inputs are always in the form, so the current values are submitted without JavaScript. Limitation:
 * Basecoat hides the panel with `aria-hidden="true"` (set in the markup, not by its script, which only
 * toggles it on click), so without JavaScript the panel cannot be opened and the dates cannot be edited.
 * Dropping the attribute would show the panel permanently, so it is kept. The theme script fills the
 * inputs from the preset buttons (`data-range-from` / `data-range-to`), closes the popover and keeps the
 * trigger text (`data-range-label`) up to date.
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class DateRangeFieldHelper extends AbstractComponentHelper
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
        'classes' => [
            'trigger' => null,
            'popover' => 'w-72',
            'input' => null,
            'preset' => null,
        ],
        'templates' => [
            'root' => '<div{{attrs}}>{{trigger}}{{panel}}</div>',
            'trigger' => '<button{{attrs}}>{{icon}}<span data-range-label data-placeholder="{{placeholder}}">{{text}}</span></button>',
            'panel' => '<div data-popover{{attrs}}>{{content}}</div>',
            'field' => '<div class="grid gap-2"><label{{labelAttrs}}>{{label}}</label><input{{inputAttrs}}></div>',
            'presets' => '<div class="flex flex-wrap gap-2">{{content}}</div>',
            'preset' => '<button{{attrs}}>{{text}}</button>',
        ],
    ];

    /**
     * Fallback calendar glyph when the icon set has no `calendar` icon.
     */
    private const CALENDAR_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" '
        . 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true"><path d="M8 2v4M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/>'
        . '<path d="M3 10h18"/></svg>';

    /**
     * Renders the field.
     *
     * ### Options
     *
     * - `label`: Field label (default `Date range`).
     * - `from`, `to`: Values (`Y-m-d`). Default: request query `{name}.from` / `{name}.to`.
     * - `presets`: `label => [from, to]` buttons that fill both inputs.
     * - `min`, `max`: Bounds of both inputs.
     * - `placeholder`: Trigger text without a range (default `Pick a date range`).
     * - `id`: Id of the root element (default generated).
     * - `escape`: Escape the label and preset texts (default true).
     *
     * @param string $name Field name; inputs are `{name}[from]` and `{name}[to]`.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $name, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        $id = (string)($options['id'] ?? $this->uniqueId('date-range'));
        $label = (string)($options['label'] ?? __d('basecoat_theme', 'Date range'));
        $placeholder = (string)($options['placeholder'] ?? __d('basecoat_theme', 'Pick a date range'));
        $values = [];
        foreach (['from', 'to'] as $edge) {
            $value = $options[$edge] ?? $this->getView()->getRequest()->getQuery($name . '.' . $edge);
            $values[$edge] = is_string($value) ? $value : '';
        }

        if ($values['from'] === '' && $values['to'] === '') {
            $text = $placeholder;
        } else {
            $text = ($values['from'] !== '' ? $values['from'] : '…') . ' – ' . ($values['to'] !== '' ? $values['to'] : '…');
        }

        $icon = $this->Icon->has('calendar')
            ? $this->Icon->render('calendar', ['class' => 'size-4'])
            : self::CALENDAR_SVG;
        $trigger = $this->renderTemplate('trigger', $this->partAttributes('trigger', [
            'type' => 'button',
            'id' => $id . '-trigger',
            'aria-expanded' => 'false',
            'aria-controls' => $id . '-popover',
            'aria-haspopup' => 'dialog',
            'data-variant' => 'outline',
        ], 'btn'), [
            'icon' => $icon,
            'placeholder' => h($placeholder),
            'text' => h($text),
        ]);

        $fields = '';
        foreach (['from', 'to'] as $edge) {
            $inputId = $id . '-' . $edge;
            $labelText = $edge === 'from' ? __d('basecoat_theme', '{0} from', $label) : __d('basecoat_theme', 'to');
            $fields .= $this->formatTemplate('field', [
                'labelAttrs' => $this->formatAttributes(['for' => $inputId, 'class' => 'label']),
                'label' => $this->text($labelText, $escape),
                'inputAttrs' => $this->formatAttributes($this->partAttributes('input', [
                    'type' => 'date',
                    'name' => $name . '[' . $edge . ']',
                    'id' => $inputId,
                    'value' => $values[$edge] !== '' ? $values[$edge] : null,
                    'min' => $options['min'] ?? null,
                    'max' => $options['max'] ?? null,
                ], 'input')),
            ]);
        }

        $presets = '';
        foreach ((array)($options['presets'] ?? []) as $presetLabel => $range) {
            $range = array_values((array)$range);
            $presets .= $this->renderTemplate('preset', $this->partAttributes('preset', [
                'type' => 'button',
                'data-range-from' => (string)($range[0] ?? ''),
                'data-range-to' => (string)($range[1] ?? ''),
                'data-variant' => 'outline',
                'data-size' => 'sm',
            ], 'btn'), ['text' => $this->text((string)$presetLabel, $escape)]);
        }
        if ($presets !== '') {
            $presets = $this->formatTemplate('presets', ['content' => $presets]);
        }

        $panel = $this->renderTemplate('panel', $this->partAttributes('popover', [
            'id' => $id . '-popover',
            'aria-hidden' => 'true',
            'role' => 'group',
            'aria-label' => $label,
        ]), ['content' => '<div class="grid gap-3">' . $presets . $fields . '</div>']);

        $attributes = $this->rootAttributes('popover', $options, [
            'label', 'from', 'to', 'presets', 'min', 'max', 'placeholder',
        ]);
        $attributes = ['id' => $id] + $attributes;

        return $this->renderTemplate('root', $attributes, ['trigger' => $trigger, 'panel' => $panel]);
    }
}
