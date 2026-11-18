<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Basecoat Empty state (`.empty`).
 *
 * Named `EmptyState` because `empty` is a reserved word in PHP.
 *
 * ```
 * echo $this->EmptyState->render('No projects yet', [
 *     'description' => 'Create your first project to get started.',
 *     'icon' => 'inbox',
 *     'actions' => $this->Button->link('New project', ['action' => 'add']),
 * ]);
 * ```
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class EmptyStateHelper extends AbstractComponentHelper
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
        'titleTag' => 'h3',
        'classes' => [
            'header' => null,
            'media' => null,
            'title' => null,
            'description' => null,
            'content' => null,
            'footer' => null,
        ],
        'templates' => [
            'empty' => '<div{{attrs}}><header{{headerAttrs}}>{{media}}{{title}}{{description}}</header>{{content}}{{footer}}</div>',
            'media' => '<figure{{attrs}}>{{content}}</figure>',
            'title' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'description' => '<p{{attrs}}>{{content}}</p>',
            'content' => '<section{{attrs}}>{{content}}</section>',
            'footer' => '<footer{{attrs}}>{{content}}</footer>',
        ],
    ];

    /**
     * Renders an empty state.
     *
     * ### Options
     *
     * - `description`: Text below the title.
     * - `icon`: Icon name/raw SVG, or `image` (URL) for the media.
     * - `actions`: HTML placed in a `<section>` (buttons).
     * - `footer`: HTML for a `<footer>`.
     * - `headerOptions`, `mediaOptions`, `titleOptions`, `descriptionOptions`, `contentOptions`, `footerOptions`.
     * - `escape`: Escape title/description (default true).
     *
     * @param string $title Title.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $title, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $media = '';
        if (!empty($options['icon'])) {
            $media = $this->renderTemplate(
                'media',
                $this->partAttributes('media', (array)($options['mediaOptions'] ?? [])),
                ['content' => $this->Icon->render((string)$options['icon'])],
            );
        } elseif (!empty($options['image'])) {
            $media = $this->renderTemplate(
                'media',
                $this->partAttributes('media', (array)($options['mediaOptions'] ?? [])),
                ['content' => '<img src="' . h((string)$options['image']) . '" alt="">'],
            );
        }

        $part = function (string $name, ?string $content, array $extra = []) use ($options): string {
            if ($content === null || $content === '') {
                return '';
            }

            return $this->renderTemplate(
                $name,
                $this->partAttributes($name, (array)($options[$name . 'Options'] ?? [])),
                ['content' => $content] + $extra,
            );
        };

        $attributes = $this->rootAttributes('empty', $options, [
            'description', 'icon', 'image', 'actions', 'footer', 'titleTag',
            'headerOptions', 'mediaOptions', 'titleOptions', 'descriptionOptions', 'contentOptions', 'footerOptions',
        ]);

        return $this->formatTemplate('empty', [
            'attrs' => $this->formatAttributes($attributes),
            'headerAttrs' => $this->formatAttributes($this->partAttributes('header', (array)($options['headerOptions'] ?? []))),
            'media' => $media,
            'title' => $part('title', $this->text($title, $escape), ['tag' => $options['titleTag'] ?? $this->getConfig('titleTag')]),
            'description' => $part('description', isset($options['description']) ? $this->text((string)$options['description'], $escape) : null),
            'content' => $part('content', $options['actions'] ?? null),
            'footer' => $part('footer', $options['footer'] ?? null),
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }
}
