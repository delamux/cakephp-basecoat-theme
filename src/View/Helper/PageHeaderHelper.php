<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

/**
 * Page header: title, optional description, breadcrumb and a row of actions.
 *
 * ```
 * echo $this->PageHeader->render('Customers', [
 *     'description' => 'Manage your customers.',
 *     'actions' => [$this->Button->link('Export', '/customers/export', ['variant' => 'outline'])],
 * ]);
 * ```
 */
class PageHeaderHelper extends AbstractComponentHelper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => 'flex flex-wrap items-center justify-between gap-4',
        'attributes' => [],
        'classes' => [
            'main' => 'grid gap-1',
            'title' => 'text-2xl font-semibold tracking-tight',
            'description' => 'text-muted-foreground text-sm',
            'actions' => 'flex flex-wrap items-center gap-2',
        ],
        'templates' => [
            'header' => '<header{{attrs}}><div{{mainAttrs}}>{{breadcrumb}}{{title}}{{description}}</div>{{actions}}</header>',
            'title' => '<h{{level}}{{attrs}}>{{content}}</h{{level}}>',
            'description' => '<p{{attrs}}>{{content}}</p>',
            'actions' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Renders a page header.
     *
     * ### Options
     *
     * - `description`: Text under the title (escaped unless `escape` is false).
     * - `actions`: HTML (or list of HTML strings) placed at the right. Not escaped.
     * - `level`: Heading level, 1 by default.
     * - `breadcrumb`: HTML placed above the title. Not escaped.
     * - `escape`: Escape the title and description (default true).
     * - `class` and any HTML attribute for the root.
     *
     * @param string $title Title.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $title, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        $level = max(1, min(6, (int)($options['level'] ?? 1)));

        $description = '';
        if (($options['description'] ?? '') !== '') {
            $description = $this->renderTemplate(
                'description',
                $this->partAttributes('description'),
                ['content' => $this->text((string)$options['description'], $escape)],
            );
        }

        $actions = '';
        $actionsHtml = $options['actions'] ?? '';
        $actionsHtml = is_array($actionsHtml) ? implode('', $actionsHtml) : (string)$actionsHtml;
        if ($actionsHtml !== '') {
            $actions = $this->renderTemplate('actions', $this->partAttributes('actions'), ['content' => $actionsHtml]);
        }

        $attributes = $this->rootAttributes([], $options, ['description', 'actions', 'level', 'breadcrumb']);

        return $this->renderTemplate('header', $attributes, [
            'mainAttrs' => $this->formatAttributes($this->partAttributes('main')),
            'breadcrumb' => (string)($options['breadcrumb'] ?? ''),
            'title' => $this->renderTemplate(
                'title',
                $this->partAttributes('title'),
                ['level' => $level, 'content' => $this->text($title, $escape)],
            ),
            'description' => $description,
            'actions' => $actions,
        ], $options['templateVars'] ?? []);
    }
}
