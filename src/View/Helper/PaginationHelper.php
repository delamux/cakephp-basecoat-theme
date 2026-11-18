<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use Cake\Core\Exception\CakeException;
use function Cake\Core\h;
use function Cake\I18n\__d;

/**
 * Basecoat Pagination, built on top of the core PaginatorHelper.
 *
 * ```
 * // In a template rendering paginated results
 * echo $this->Pagination->render();
 * echo $this->Pagination->render(['first' => true, 'last' => true, 'counter' => true, 'modulus' => 4]);
 *
 * // Paginated result not set as a view var: tell the core helper first
 * $this->Paginator->setPaginated($articles);
 * echo $this->Pagination->simple(); // previous / next only
 * ```
 *
 * The core PaginatorHelper templates are swapped only while rendering (push/pop), so
 * `$this->Paginator` keeps its own templates. To style the core helper globally load it
 * with `['templates' => 'BasecoatTheme.paginator_templates']`.
 *
 * @property \Cake\View\Helper\PaginatorHelper $Paginator
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class PaginationHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Paginator', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * - `paginatorTemplates`: Templates pushed into the core PaginatorHelper.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => 'mx-auto flex w-full justify-center',
        'attributes' => ['role' => 'navigation'],
        'modulus' => 2,
        'edges' => 1,
        'classes' => [
            'list' => 'flex flex-row items-center gap-1',
            'counter' => 'text-sm text-muted-foreground',
        ],
        'paginatorTemplates' => [
            'nextActive' => '<li><a class="btn" data-variant="ghost" rel="next" href="{{url}}">{{text}}</a></li>',
            'nextDisabled' => '<li><a class="btn pointer-events-none opacity-50" data-variant="ghost" aria-disabled="true" tabindex="-1">{{text}}</a></li>',
            'prevActive' => '<li><a class="btn" data-variant="ghost" rel="prev" href="{{url}}">{{text}}</a></li>',
            'prevDisabled' => '<li><a class="btn pointer-events-none opacity-50" data-variant="ghost" aria-disabled="true" tabindex="-1">{{text}}</a></li>',
            'first' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
            'last' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
            'number' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
            'current' => '<li><a class="btn" data-variant="outline" data-size="icon" aria-current="page" href="{{url}}">{{text}}</a></li>',
            'ellipsis' => '<li><div class="flex size-9 items-center justify-center" aria-hidden="true">{{ellipsisIcon}}</div></li>',
        ],
        'templates' => [
            'pagination' => '<nav{{attrs}}><ul{{listAttrs}}>{{content}}</ul></nav>',
            'counter' => '<p{{attrs}}>{{content}}</p>',
            'wrapper' => '<div class="flex flex-col items-center gap-2">{{counter}}{{pagination}}</div>',
        ],
    ];

    /**
     * Renders the full pagination control.
     *
     * ### Options
     *
     * - `prev`, `next`: Labels (HTML allowed). Defaults: chevron + "Previous"/"Next".
     * - `first`, `last`: `true` or a label to show first/last links.
     * - `numbers`: Show page numbers (default true).
     * - `modulus`: Pages shown around the current one (default 2).
     * - `edges`: Number of page links always shown at both ends, separated by an
     *   ellipsis (default 1, `false` to disable).
     * - `counter`: `true` or a format string for PaginatorHelper::counter().
     * - `url`: Extra URL options for the links.
     * - `listOptions`, `counterOptions`.
     * - `class` and any attribute for the `<nav>`.
     *
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $options = []): string
    {
        if (!$this->hasPaging($options)) {
            return '';
        }
        $linkOptions = $this->linkOptions($options);

        $content = $this->withTemplates(function () use ($options, $linkOptions): string {
            $out = '';
            if (!empty($options['first'])) {
                $out .= (string)$this->Paginator->first(
                    $options['first'] === true ? $this->Icon->render('chevrons-left') : $options['first'],
                    $linkOptions + ['escape' => false],
                );
            }
            $out .= $this->Paginator->prev($options['prev'] ?? $this->prevLabel(), $linkOptions + ['escape' => false]);
            if ($options['numbers'] ?? true) {
                $edges = $options['edges'] ?? $this->getConfig('edges');
                $out .= $this->Paginator->numbers($linkOptions + [
                    'modulus' => $options['modulus'] ?? $this->getConfig('modulus'),
                    'first' => $edges ?: null,
                    'last' => $edges ?: null,
                ]);
            }
            $out .= $this->Paginator->next($options['next'] ?? $this->nextLabel(), $linkOptions + ['escape' => false]);
            if (!empty($options['last'])) {
                $out .= (string)$this->Paginator->last(
                    $options['last'] === true ? $this->Icon->render('chevrons-right') : $options['last'],
                    $linkOptions + ['escape' => false],
                );
            }

            return $out;
        });

        $nav = $this->nav($content, $options);
        if (empty($options['counter'])) {
            return $nav;
        }

        return $this->formatTemplate('wrapper', [
            'counter' => $this->counter(is_string($options['counter']) ? $options['counter'] : null, $options),
            'pagination' => $nav,
        ]);
    }

    /**
     * Renders only previous/next buttons.
     *
     * @param array<string, mixed> $options Options, see render().
     * @return string
     */
    public function simple(array $options = []): string
    {
        return $this->render(['numbers' => false, 'first' => false, 'last' => false] + $options);
    }

    /**
     * Renders the paging counter ("Page 1 of 10, showing 20 records out of 200 total").
     *
     * @param string|null $format Counter format (see PaginatorHelper::counter()).
     * @param array<string, mixed> $options `counterOptions`.
     * @return string
     */
    public function counter(?string $format = null, array $options = []): string
    {
        if (!$this->hasPaging($options)) {
            return '';
        }
        $text = $this->Paginator->counter($format ?? __d('basecoat_theme', 'Page {{page}} of {{pages}}'));

        return $this->renderTemplate(
            'counter',
            $this->partAttributes('counter', (array)($options['counterOptions'] ?? [])),
            ['content' => $text],
        );
    }

    /**
     * Wraps list items in the `<nav><ul>` markup.
     *
     * @param string $content List items.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    protected function nav(string $content, array $options): string
    {
        $options += ['aria-label' => __d('basecoat_theme', 'pagination')];
        $attributes = $this->rootAttributes([], $options, [
            'prev', 'next', 'first', 'last', 'numbers', 'modulus', 'edges', 'counter', 'url',
            'listOptions', 'counterOptions',
        ]);

        return $this->formatTemplate('pagination', [
            'attrs' => $this->formatAttributes($attributes),
            'listAttrs' => $this->formatAttributes($this->partAttributes('list', (array)($options['listOptions'] ?? []))),
            'content' => $content,
            'templateVars' => $options['templateVars'] ?? [],
        ]);
    }

    /**
     * Runs `$callback` with the Basecoat templates pushed into the PaginatorHelper.
     *
     * @param callable():string $callback Callback.
     * @return string
     */
    protected function withTemplates(callable $callback): string
    {
        $templater = $this->Paginator->templater();
        $templater->push();
        $templates = (array)$this->getConfig('paginatorTemplates');
        $ellipsis = $this->Icon->render('ellipsis');
        $templates = array_map(fn($template) => str_replace('{{ellipsisIcon}}', $ellipsis, (string)$template), $templates);
        $templater->add($templates);
        try {
            return $callback();
        } finally {
            $templater->pop();
        }
    }

    /**
     * Options passed to every PaginatorHelper link method.
     *
     * @param array<string, mixed> $options Options.
     * @return array<string, mixed>
     */
    protected function linkOptions(array $options): array
    {
        $linkOptions = [];
        if (isset($options['url'])) {
            $linkOptions['url'] = $options['url'];
        }

        return $linkOptions;
    }

    /**
     * Whether there are paging params available.
     *
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    protected function hasPaging(array $options): bool
    {
        try {
            return (int)($this->Paginator->params()['pageCount'] ?? 0) > 0;
        } catch (CakeException) {
            return false;
        }
    }

    /**
     * Default "previous" label.
     *
     * @return string
     */
    protected function prevLabel(): string
    {
        return $this->Icon->render('chevron-left') . '<span class="hidden sm:block">'
            . h(__d('basecoat_theme', 'Previous')) . '</span>';
    }

    /**
     * Default "next" label.
     *
     * @return string
     */
    protected function nextLabel(): string
    {
        return '<span class="hidden sm:block">' . h(__d('basecoat_theme', 'Next')) . '</span>'
            . $this->Icon->render('chevron-right');
    }
}
