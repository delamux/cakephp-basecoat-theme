<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Basecoat Avatar (`.avatar`) and avatar group (`.avatar-group`).
 *
 * ```
 * echo $this->Avatar->render('Elena Sarto');                          // initials "ES"
 * echo $this->Avatar->render('Kofi Mensah', ['src' => '/img/avatars/kofi.png']);
 * echo $this->Avatar->render('Ana', ['size' => 'lg', 'class' => 'rounded-lg']);
 * echo $this->Avatar->group([['name' => 'A'], ['name' => 'B'], ['name' => 'C']], ['max' => 2]);
 * // Next to the visible name: hide the initials from screen readers (no "KM Kofi Mensah")
 * echo $this->Avatar->render('Kofi Mensah', ['decorative' => true]) . h('Kofi Mensah');
 * ```
 *
 * Sizes: sm, default, lg.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class AvatarHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'size' => null,
        'class' => null,
        'attributes' => [],
        'maxInitials' => 2,
        'classes' => [
            'image' => null,
            'fallback' => null,
            'badge' => null,
            'group' => null,
            'count' => null,
        ],
        'templates' => [
            'avatar' => '<span{{attrs}}>{{image}}{{fallback}}{{badge}}</span>',
            'image' => '<img src="{{src}}"{{attrs}}>',
            'fallback' => '<span{{attrs}}>{{content}}</span>',
            'badge' => '<span{{attrs}}>{{content}}</span>',
            'group' => '<div{{attrs}}>{{content}}</div>',
            'count' => '<span data-count{{attrs}}>{{content}}</span>',
        ],
    ];

    /**
     * Renders an avatar.
     *
     * ### Options
     *
     * - `src`: Image URL (string or route array, built with UrlHelper::image() for relative paths).
     * - `alt`: Image alt (defaults to `$name`).
     * - `initials`: Fallback text (defaults to the initials of `$name`).
     * - `size`: `sm` or `lg`.
     * - `badge`: HTML/text of a status badge (`.avatar-badge`).
     * - `imageOptions`, `fallbackOptions`, `badgeOptions`: Attributes of the parts.
     * - `decorative`: `true` when the name is rendered next to the avatar: the initials get
     *   `aria-hidden="true"` and the image an empty `alt`, so screen readers read the name once.
     *   The badge stays exposed (put its meaning in `sr-only` text). Default `false`.
     *
     * @param string $name Person name.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(string $name, array $options = []): string
    {
        $decorative = !empty($options['decorative']);
        $image = '';
        if (!empty($options['src'])) {
            $src = $options['src'];
            $src = is_array($src) ? $this->Url->build($src) : $this->Url->image((string)$src);
            $imageOptions = (array)($options['imageOptions'] ?? []);
            $alt = $imageOptions['alt'] ?? ($decorative ? '' : ($options['alt'] ?? $name));
            unset($imageOptions['alt']);
            // Added after the clean-up so an empty `alt` (decorative image) is kept.
            $imageAttrs = $this->partAttributes('image', $imageOptions) + ['alt' => (string)$alt];
            $image = $this->renderTemplate('image', $imageAttrs, ['src' => h($src)]);
        }

        $initials = $options['initials'] ?? $this->initials($name);
        $fallback = $this->renderTemplate(
            'fallback',
            $this->partAttributes('fallback', (array)($options['fallbackOptions'] ?? []) + ($decorative ? ['aria-hidden' => 'true'] : [])),
            ['content' => h($initials)],
        );

        $badge = '';
        if (!empty($options['badge'])) {
            $badge = $this->renderTemplate(
                'badge',
                $this->partAttributes('badge', (array)($options['badgeOptions'] ?? []), 'avatar-badge'),
                ['content' => $options['badge'] === true ? '' : $options['badge']],
            );
        }

        $attributes = $this->rootAttributes('avatar', $options, [
            'src', 'alt', 'initials', 'decorative', 'badge', 'imageOptions', 'fallbackOptions', 'badgeOptions',
        ]);

        return $this->renderTemplate('avatar', $attributes, [
            'image' => $image,
            'fallback' => $fallback,
            'badge' => $badge,
        ], $options['templateVars'] ?? []);
    }

    /**
     * Renders an avatar group.
     *
     * ### Options
     *
     * - `max`: Maximum avatars shown; the rest is summarized in a `+N` counter.
     * - `count`: Force the counter content (HTML), e.g. an icon.
     * - `countOptions`: Attributes of the counter.
     * - `avatar`: Options merged into every avatar (e.g. `['size' => 'sm']`).
     * - `class` and any attribute for the group.
     *
     * @param array<array<string, mixed>|string> $avatars List of names or option arrays with a `name` key.
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function group(array $avatars, array $options = []): string
    {
        $max = $options['max'] ?? null;
        $shared = (array)($options['avatar'] ?? []);
        $hidden = 0;
        if ($max !== null && count($avatars) > $max) {
            $hidden = count($avatars) - (int)$max;
            $avatars = array_slice($avatars, 0, (int)$max);
        }

        $content = '';
        foreach ($avatars as $avatar) {
            $avatar = is_string($avatar) ? ['name' => $avatar] : $avatar;
            $name = (string)($avatar['name'] ?? '');
            unset($avatar['name']);
            $content .= $this->render($name, $avatar + $shared);
        }

        $count = $options['count'] ?? ($hidden > 0 ? '+' . $hidden : null);
        if ($count !== null) {
            $content .= $this->renderTemplate(
                'count',
                $this->partAttributes('count', (array)($options['countOptions'] ?? [])),
                ['content' => $count],
            );
        }

        $attributes = (array)$options + ['class' => null];
        $attributes['class'] = $this->mergeClasses('avatar-group', $this->getConfig('classes.group'), $attributes['class']);
        $attributes = $this->cleanAttributes($this->stripOptions($attributes, ['max', 'count', 'countOptions', 'avatar']));

        return $this->renderTemplate('group', $attributes, ['content' => $content]);
    }

    /**
     * Computes the initials of a name ("Ines van Duarte" => "ID").
     *
     * @param string $name Name.
     * @return string
     */
    public function initials(string $name): string
    {
        $words = preg_split('/[\s._@-]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $max = (int)$this->getConfig('maxInitials');
        if (count($words) > $max) {
            $words = array_merge(array_slice($words, 0, $max - 1), [end($words)]);
        }
        $initials = '';
        foreach ($words as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }
}
