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
 * Forms get a stacked layout (`formLayout`, default `grid gap-6 [&>.btn]:justify-self-start`)
 * unless their `class` already sets one; see create().
 *
 * Buttons accept `variant` and `size` options:
 * `$this->Form->button('Delete', ['variant' => 'destructive'])`.
 */
class FormHelper extends CoreFormHelper
{
    /**
     * Nesting depth of control() calls (fieldWidgetClasses apply only inside a control).
     */
    protected int $controlDepth = 0;

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
            // Classes create() adds to a form whose `class` sets no layout of its own (see create()).
            // Basecoat 1.1 styles `.field` / `.fieldset` but leaves the space between them to the form.
            // Extra classes for widgets rendered by control() inside a `.field` (Basecoat sizes
            // `select.select` to its content; in a field it fills the width like inputs and textareas).
            'fieldWidgetClasses' => ['select' => 'w-full'],
            'formLayout' => 'grid gap-6 [&>.btn]:justify-self-start',
        ];
        parent::__construct($view, $config);
    }

    /**
     * Display / spacing utilities that mean "the caller chose the form layout".
     */
    protected const LAYOUT_CLASS = '/^(?:\S+:)?!?(?:flex|inline-flex|grid|inline-grid|block|inline-block|inline|contents|flow-root|table|hidden|space-[xy]-[^!]+)!?$/';

    /**
     * Opens a form with the Basecoat layout: fields evenly spaced and full width, buttons at their size.
     *
     * The `formLayout` config classes (default `grid gap-6 [&>.btn]:justify-self-start`) are added
     * unless the `class` option already sets a layout (a display utility such as `flex`, `grid`,
     * `inline-flex`, `md:grid`, or `space-y-*`), so inline toolbars and forms with their own layout are
     * left as they are. Other classes (`w-full max-w-md`) are kept next to the default layout.
     *
     * ### Extra option
     *
     * - `layout`: `false` adds no layout classes; a string is added instead of `formLayout`, even when
     *   `class` has a layout of its own.
     *
     * @param mixed $context Context.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function create(mixed $context = null, array $options = []): string
    {
        $layout = array_key_exists('layout', $options) ? $options['layout'] : null;
        unset($options['layout']);
        if ($layout === null || $layout === true) {
            $layout = $this->hasLayoutClass($options['class'] ?? null) ? false : $this->getConfig('formLayout');
        }
        if (is_string($layout) && trim($layout) !== '') {
            $options = $this->addClass($options, $layout);
        }

        return parent::create($context, $options);
    }

    /**
     * Renders a control in a Basecoat `.field`; see `fieldWidgetClasses`.
     *
     * @param string $fieldName Field name.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function control(string $fieldName, array $options = []): string
    {
        $this->controlDepth++;
        try {
            return parent::control($fieldName, $options);
        } finally {
            $this->controlDepth--;
        }
    }

    /**
     * Inline POST button: its form gets no layout classes (pass `form.layout` to add some).
     *
     * @param string $title Title.
     * @param array|string $url URL.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function postButton(string $title, array|string $url, array $options = []): string
    {
        $options['form'] = (array)($options['form'] ?? []) + ['layout' => false];

        return parent::postButton($title, $url, $options);
    }

    /**
     * Whether the control being rendered is wrapped in a Basecoat `.field` (the `inputContainer`
     * template in use, including one passed in the control's `templates` option, has the class).
     *
     * @return bool
     */
    protected function inFieldContainer(): bool
    {
        return (bool)preg_match('/class="(?:[^"]*\s)?field[\s"{]/', (string)$this->templater()->get('inputContainer'));
    }

    /**
     * Whether a class option already sets the form layout (display or `space-*` utilities).
     *
     * @param mixed $class Class string or list.
     * @return bool
     */
    protected function hasLayoutClass(mixed $class): bool
    {
        $class = is_array($class) ? implode(' ', array_filter($class, 'is_string')) : (string)$class;
        foreach (preg_split('/\s+/', trim($class)) ?: [] as $token) {
            if (preg_match(static::LAYOUT_CLASS, $token)) {
                return true;
            }
        }

        return false;
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
            if ($this->controlDepth > 0 && $this->inFieldContainer()) {
                $class = trim($class . ' ' . (string)($this->getConfig('fieldWidgetClasses.' . $name) ?? ''));
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
