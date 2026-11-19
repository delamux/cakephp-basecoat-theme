<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use Cake\View\Helper\FormHelper as CoreFormHelper;
use Cake\View\View;

/**
 * FormHelper with Basecoat markup (`.field`, `.input`, `.select`, `.textarea`, `.label`, `.btn`).
 *
 * It extends the core FormHelper, so its whole API (create(), control(), end(),
 * postLink()...) keeps working. Load it replacing the core one:
 *
 * ```
 * // src/View/AppView.php
 * $this->addHelper('Form', ['className' => 'BasecoatTheme.Form']);
 *
 * // Tune classes per widget or templates
 * $this->addHelper('Form', [
 *     'className' => 'BasecoatTheme.Form',
 *     'widgetClasses' => ['textarea' => 'textarea min-h-32'],
 *     'button' => ['variant' => 'secondary'],
 * ]);
 * ```
 *
 * Buttons accept `variant` and `size` options:
 * `$this->Form->button('Delete', ['variant' => 'destructive'])`.
 */
class FormHelper extends CoreFormHelper
{
    /**
     * Basecoat templates merged over the core ones.
     *
     * @var array<string, string>
     */
    protected array $basecoatTemplates = [
        'inputContainer' => '<div class="field{{required}}" role="group" data-type="{{type}}">{{content}}</div>',
        'inputContainerError' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-invalid="true">{{content}}{{error}}</div>',
        'checkboxContainer' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-orientation="horizontal">{{content}}</div>',
        'checkboxContainerError' => '<div class="field{{required}}" role="group" data-type="{{type}}" data-orientation="horizontal" data-invalid="true">{{content}}{{error}}</div>',
        'error' => '<p class="text-sm" role="alert" id="{{id}}">{{content}}</p>',
        'errorList' => '<ul>{{content}}</ul>',
        'errorItem' => '<li>{{text}}</li>',
        'fieldset' => '<fieldset class="fieldset"{{attrs}}>{{content}}</fieldset>',
        'legend' => '<legend>{{text}}</legend>',
        'multicheckboxTitle' => '<legend>{{text}}</legend>',
        'multicheckboxWrapper' => '<fieldset class="fieldset"{{attrs}}>{{content}}</fieldset>',
        // {{input}} is empty when nested (the input is inside {{label}})
        'checkboxWrapper' => '<div class="flex items-center gap-3">{{input}}{{label}}</div>',
        'radioWrapper' => '<div class="flex items-center gap-3">{{input}}{{label}}</div>',
        'submitContainer' => '<div class="flex gap-2">{{content}}</div>',
    ];

    /**
     * Constructor: merges the Basecoat defaults into the core configuration.
     *
     * @param \Cake\View\View $view View.
     * @param array<string, mixed> $config Config.
     */
    public function __construct(View $view, array $config = [])
    {
        $this->_defaultConfig['templates'] = $this->basecoatTemplates + $this->_defaultConfig['templates'];
        // A single checkbox/radio is a plain horizontal `.field` (input + label siblings).
        // Basecoat styles `label:has(input)` as a "choice card": opt in per control with
        // `'nestedInput' => true` or globally with `'nestedCheckboxAndRadio' => true`.
        $this->_defaultConfig['nestedCheckboxAndRadio'] = false;
        $this->_defaultConfig += [
            'widgetClasses' => [
                'textarea' => 'textarea',
                'select' => 'select',
                'checkbox' => 'input',
                'radio' => 'input',
                'multicheckbox' => 'input',
                'label' => 'label',
                'nestingLabel' => 'label gap-3',
                'button' => 'btn',
                'file' => 'input',
                '_input' => 'input',
            ],
            'skipClassWidgets' => ['hidden', 'year'],
            'button' => ['variant' => null, 'size' => null],
        ];
        parent::__construct($view, $config);
    }

    /**
     * Adds the Basecoat class of the widget before rendering it.
     *
     * @param string $name Widget name.
     * @param array<string, mixed> $data Widget data.
     * @return string
     */
    public function widget(string $name, array $data = []): string
    {
        if (!in_array($name, (array)$this->getConfig('skipClassWidgets'), true)) {
            $classes = (array)$this->getConfig('widgetClasses');
            $class = $classes[$name] ?? $classes['_input'] ?? null;
            if ($name === 'button') {
                $data = $this->buttonData($data);
            }
            if ($class && ($data['basecoat'] ?? true) !== false) {
                $data = $this->addClass($data, $class);
            }
        }
        unset($data['basecoat']);

        return parent::widget($name, $data);
    }

    /**
     * Submit input/button styled as `.btn`.
     *
     * @param string|null $caption Caption.
     * @param array<string, mixed> $options Options (`variant`, `size` supported).
     * @return string
     */
    public function submit(?string $caption = null, array $options = []): string
    {
        $options = $this->buttonData($options);
        $options = $this->addClass($options, (string)$this->getConfig('widgetClasses.button', 'btn'));

        return parent::submit($caption, $options);
    }

    /**
     * Converts `variant`/`size` options into data attributes.
     *
     * @param array<string, mixed> $data Data.
     * @return array<string, mixed>
     */
    protected function buttonData(array $data): array
    {
        $variant = array_key_exists('variant', $data) ? $data['variant'] : $this->getConfig('button.variant');
        $size = array_key_exists('size', $data) ? $data['size'] : $this->getConfig('button.size');
        unset($data['variant'], $data['size']);
        if ($variant) {
            $data['data-variant'] = $variant;
        }
        if ($size) {
            $data['data-size'] = $size;
        }

        return $data;
    }

    /**
     * Adds a class to an option array without duplicates.
     *
     * @param array<string, mixed> $options Options.
     * @param string $class Class(es).
     * @param string $key Key.
     * @return array<string, mixed>
     */
    public function addClass(array $options, string $class, string $key = 'class'): array
    {
        $existing = $options[$key] ?? [];
        $existing = is_array($existing) ? $existing : preg_split('/\s+/', trim((string)$existing));
        $merged = array_values(array_unique(array_filter(array_merge(
            preg_split('/\s+/', trim($class)) ?: [],
            (array)$existing,
        ))));
        $options[$key] = implode(' ', $merged);

        return $options;
    }
}
