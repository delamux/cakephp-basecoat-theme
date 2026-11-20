<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\I18n\__d;

/**
 * Basecoat Dialog (native `<dialog class="dialog">`).
 *
 * ```
 * echo $this->Dialog->render('digest-settings', $this->element('digest_form'), [
 *     'title' => 'Weekly digest',
 *     'description' => 'Choose who receives the summary email.',
 *     'trigger' => 'Digest settings',
 *     'footer' => $this->Button->render('Save changes', ['type' => 'submit']),
 * ]);
 *
 * // Separate trigger anywhere in the page
 * echo $this->Dialog->trigger('digest-settings', 'Open', ['variant' => 'outline']);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class DialogHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Button', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'closeButton' => true,
        'closeOnOverlay' => true,
        'titleTag' => 'h2',
        'trigger' => ['variant' => 'outline'],
        'classes' => [
            'content' => null,
            'header' => null,
            'title' => null,
            'description' => null,
            'body' => null,
            'footer' => null,
        ],
        'templates' => [
            'dialog' => '<dialog{{attrs}}><div{{contentAttrs}}>{{header}}{{body}}{{footer}}{{close}}</div></dialog>',
            'header' => '<header{{attrs}}>{{title}}{{description}}</header>',
            'title' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'description' => '<p{{attrs}}>{{content}}</p>',
            'body' => '<section{{attrs}}>{{content}}</section>',
            'footer' => '<footer{{attrs}}>{{content}}</footer>',
        ],
    ];

    /**
     * Renders a dialog (and optionally its trigger button, placed before it).
     *
     * ### Options
     *
     * - `title`, `description`: Header texts.
     * - `footer`: Footer HTML.
     * - `trigger`: Trigger button text (or options array with `text` + button options).
     * - `open`: Render it open (`open` attribute).
     * - `closeButton`: Render the top-right close button (default true).
     * - `closeOnOverlay`: Close when clicking the backdrop (default true).
     * - `contentOptions`, `headerOptions`, `titleOptions`, `descriptionOptions`,
     *   `bodyOptions`, `footerOptions`: Attributes of the parts.
     * - `escape`: Escape title/description (default true).
     * - `class` and any attribute for the `<dialog>`.
     *
     * @param string $id Dialog id.
     * @param string|null $body Body HTML (not escaped).
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $id, ?string $body = null, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $hasTitle = !empty($options['title']);
        $hasDescription = !empty($options['description']);

        $header = '';
        if ($hasTitle || $hasDescription) {
            $title = $hasTitle ? $this->renderTemplate(
                'title',
                $this->partAttributes('title', (array)($options['titleOptions'] ?? []) + ['id' => $id . '-title']),
                [
                    'tag' => $options['titleTag'] ?? $this->getConfig('titleTag'),
                    'content' => $this->text((string)$options['title'], $escape),
                ],
            ) : '';
            $description = $hasDescription ? $this->renderTemplate(
                'description',
                $this->partAttributes('description', (array)($options['descriptionOptions'] ?? []) + ['id' => $id . '-description']),
                ['content' => $this->text((string)$options['description'], $escape)],
            ) : '';
            $header = $this->renderTemplate(
                'header',
                $this->partAttributes('header', (array)($options['headerOptions'] ?? [])),
                ['title' => $title, 'description' => $description],
            );
        }

        $bodyHtml = $body !== null && $body !== '' ? $this->renderTemplate(
            'body',
            $this->partAttributes('body', (array)($options['bodyOptions'] ?? [])),
            ['content' => $body],
        ) : '';

        $footer = !empty($options['footer']) ? $this->renderTemplate(
            'footer',
            $this->partAttributes('footer', (array)($options['footerOptions'] ?? [])),
            ['content' => $options['footer']],
        ) : '';

        $close = '';
        if ($options['closeButton'] ?? $this->getConfig('closeButton')) {
            $close = $this->Button->icon('x', __d('basecoat_theme', 'Close dialog'), [
                'variant' => 'ghost',
                'size' => 'icon-sm',
                'data-dialog-close' => 'true',
            ]);
        }

        $rootOptions = $options + [
            'id' => $id,
            'aria-labelledby' => $hasTitle ? $id . '-title' : null,
            'aria-describedby' => $hasDescription ? $id . '-description' : null,
            'open' => (bool)($options['open'] ?? false),
        ];
        if ($options['closeOnOverlay'] ?? $this->getConfig('closeOnOverlay')) {
            // Closing on a backdrop click is handled by webroot/js/basecoat-theme.js
            $rootOptions += ['data-dialog-backdrop-close' => 'true'];
        }
        $attributes = $this->rootAttributes('dialog', $rootOptions, [
            'title', 'description', 'footer', 'trigger', 'closeButton', 'closeOnOverlay', 'titleTag',
            'contentOptions', 'headerOptions', 'titleOptions', 'descriptionOptions', 'bodyOptions', 'footerOptions',
        ]);

        $trigger = '';
        if (!empty($options['trigger'])) {
            $triggerOptions = is_array($options['trigger']) ? $options['trigger'] : ['text' => $options['trigger']];
            $text = (string)($triggerOptions['text'] ?? '');
            unset($triggerOptions['text']);
            $trigger = $this->trigger($id, $text, $triggerOptions);
        }

        return $trigger . $this->formatTemplate('dialog', [
            'attrs' => $this->formatAttributes($attributes),
            'contentAttrs' => $this->formatAttributes($this->partAttributes('content', (array)($options['contentOptions'] ?? []))),
            'header' => $header,
            'body' => $bodyHtml,
            'footer' => $footer,
            'close' => $close,
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Renders a button that opens the dialog `$id`.
     *
     * @param string $id Dialog id.
     * @param string $text Button text.
     * @param array<string, mixed> $options Button options (see ButtonHelper::render()).
     * @return string
     */
    public function trigger(string $id, string $text, array $options = []): string
    {
        $options += (array)$this->getConfig('trigger') + [
            'data-dialog-open' => $id,
            'aria-haspopup' => 'dialog',
        ];

        return $this->Button->render($text, $options);
    }

    /**
     * Renders a button that closes the dialog it is in.
     *
     * @param string $text Button text.
     * @param array<string, mixed> $options Button options.
     * @return string
     */
    public function close(string $text, array $options = []): string
    {
        $options += ['variant' => 'outline', 'data-dialog-close' => 'true'];

        return $this->Button->render($text, $options);
    }
}
