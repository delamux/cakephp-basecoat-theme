<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use LogicException;

/**
 * Basecoat Card (`.card`).
 *
 * ```
 * echo $this->Card->render('<p>Body</p>', [
 *     'title' => 'Total Revenue',
 *     'description' => 'Last 6 months',
 *     'action' => $this->Badge->render('+12%', ['variant' => 'outline']),
 *     'footer' => $this->Button->render('Save'),
 * ]);
 *
 * // Capture mode, useful for big bodies
 * echo $this->Card->start(['title' => 'Team']);
 * ...any template code...
 * echo $this->Card->end(['footer' => '...']);
 * ```
 */
class CardHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'titleTag' => 'h2',
        'classes' => [
            'header' => null,
            'title' => null,
            'description' => null,
            'action' => null,
            'body' => null,
            'footer' => null,
        ],
        'templates' => [
            'card' => '<div{{attrs}}>{{header}}{{body}}{{footer}}</div>',
            'cardStart' => '<div{{attrs}}>{{header}}<section{{bodyAttrs}}>',
            'cardEnd' => '</section>{{footer}}</div>',
            'header' => '<header{{attrs}}>{{title}}{{description}}{{action}}</header>',
            'title' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'description' => '<p{{attrs}}>{{content}}</p>',
            'action' => '<div{{attrs}}>{{content}}</div>',
            'body' => '<section{{attrs}}>{{content}}</section>',
            'footer' => '<footer{{attrs}}>{{content}}</footer>',
        ],
    ];

    /**
     * Number of open cards (capture mode).
     *
     * @var int
     */
    protected int $open = 0;

    /**
     * Renders a card.
     *
     * ### Options
     *
     * - `title`, `description`: Header texts (escaped unless `escape` is false).
     * - `titleTag`: Title tag (default `h2`).
     * - `action`: HTML placed at the right of the header (`.card-action`).
     * - `header`: Raw HTML replacing the generated header content.
     * - `footer`: Footer HTML.
     * - `headerOptions`, `titleOptions`, `descriptionOptions`, `actionOptions`,
     *   `bodyOptions`, `footerOptions`: Attributes of the parts.
     * - `class` and any HTML attribute for the root.
     *
     * @param string|null $body Body HTML (not escaped). Null renders no `<section>`.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(?string $body = null, array $options = []): string
    {
        $bodyHtml = '';
        if ($body !== null) {
            $bodyHtml = $this->renderTemplate(
                'body',
                $this->partAttributes('body', (array)($options['bodyOptions'] ?? [])),
                ['content' => $body],
            );
        }

        return $this->renderTemplate('card', $this->attributes($options), [
            'header' => $this->header($options),
            'body' => $bodyHtml,
            'footer' => $this->footer($options),
        ], $options['templateVars'] ?? []);
    }

    /**
     * Opens a card (header + `<section>`). Close it with end().
     *
     * @param array<string, mixed> $options Options, see render() (`footer` is given to end()).
     * @return string
     */
    public function start(array $options = []): string
    {
        $this->open++;

        return $this->formatTemplate('cardStart', [
            'attrs' => $this->formatAttributes($this->attributes($options)),
            'header' => $this->header($options),
            'bodyAttrs' => $this->formatAttributes($this->partAttributes('body', (array)($options['bodyOptions'] ?? []))),
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Closes a card opened with start().
     *
     * @param array<string, mixed> $options `footer` and `footerOptions`.
     * @return string
     * @throws \LogicException When no card is open.
     */
    public function end(array $options = []): string
    {
        if ($this->open === 0) {
            throw new LogicException('CardHelper::end() called without a matching start().');
        }
        $this->open--;

        return $this->formatTemplate('cardEnd', ['footer' => $this->footer($options)]);
    }

    /**
     * Root attributes.
     *
     * @param array<string, mixed> $options Options.
     * @return array<string, mixed>
     */
    protected function attributes(array $options): array
    {
        return $this->rootAttributes('card', $options, [
            'title', 'description', 'titleTag', 'action', 'header', 'footer',
            'headerOptions', 'titleOptions', 'descriptionOptions', 'actionOptions', 'bodyOptions', 'footerOptions',
        ]);
    }

    /**
     * Renders the header (empty string when there is nothing to show).
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function header(array $options): string
    {
        $escape = $options['escape'] ?? true;
        $attributes = $this->partAttributes('header', (array)($options['headerOptions'] ?? []));
        if (isset($options['header'])) {
            return $this->renderTemplate('header', $attributes, ['title' => $options['header']]);
        }

        $title = '';
        if (isset($options['title']) && $options['title'] !== '') {
            $title = $this->renderTemplate(
                'title',
                $this->partAttributes('title', (array)($options['titleOptions'] ?? [])),
                [
                    'tag' => $options['titleTag'] ?? $this->getConfig('titleTag'),
                    'content' => $this->text((string)$options['title'], $escape),
                ],
            );
        }
        $description = '';
        if (isset($options['description']) && $options['description'] !== '') {
            $description = $this->renderTemplate(
                'description',
                $this->partAttributes('description', (array)($options['descriptionOptions'] ?? [])),
                ['content' => $this->text((string)$options['description'], $escape)],
            );
        }
        $action = '';
        if (!empty($options['action'])) {
            $action = $this->renderTemplate(
                'action',
                $this->partAttributes('action', (array)($options['actionOptions'] ?? []), 'card-action'),
                ['content' => $options['action']],
            );
        }

        if ($title === '' && $description === '' && $action === '') {
            return '';
        }

        return $this->renderTemplate('header', $attributes, [
            'title' => $title,
            'description' => $description,
            'action' => $action,
        ]);
    }

    /**
     * Renders the footer (empty string when not given).
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function footer(array $options): string
    {
        if (empty($options['footer'])) {
            return '';
        }

        return $this->renderTemplate(
            'footer',
            $this->partAttributes('footer', (array)($options['footerOptions'] ?? [])),
            ['content' => $options['footer']],
        );
    }
}
