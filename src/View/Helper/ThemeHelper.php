<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\I18n\__d;

/**
 * Theme assets and global UI (dark mode, theme toggle) for Basecoat.
 *
 * ```
 * // In the <head> of a layout
 * echo $this->Theme->head();             // dark-mode script + CSS + JS
 *
 * // Or piece by piece
 * echo $this->Theme->darkModeScript();
 * echo $this->Theme->css();
 * echo $this->Theme->scripts(['charts' => true]);
 *
 * echo $this->Theme->toggle();           // light/dark toggle button
 * ```
 *
 * By default everything is loaded from jsDelivr together with the Tailwind browser
 * build (zero build step). For production compile your own CSS with Tailwind CLI +
 * `basecoat-css` and point `css` to it while disabling `tailwindBrowser`:
 *
 * ```
 * $this->addHelper('BasecoatTheme.Theme', ['css' => ['app'], 'tailwindBrowser' => false]);
 * ```
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 */
class ThemeHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Html', 'BasecoatTheme.Button', 'BasecoatTheme.Icon'];

    /**
     * Default configuration.
     *
     * - `version`: basecoat-css version on the CDN.
     * - `cdn`: Base URL (`{{version}}` is replaced).
     * - `style`: Basecoat style pack (vega, nova, luma, lyra, maia, mira, rhea, sera) or null (default).
     * - `css`: Your own stylesheet(s); replaces the CDN Basecoat CSS when set.
     * - `tailwindBrowser`: URL of the Tailwind v4 browser build, or false.
     * - `tailwindTheme`: Inject the `@theme` token mapping for the browser build.
     * - `font`: Google Fonts stylesheet URL (false to disable) and `fontFamily`.
     * - `js`: Basecoat JS bundle URL (false to disable).
     * - `chartJs`, `chartJsBasecoat`: Chart.js and Basecoat chart adapter URLs.
     * - `pluginJs`: Plugin init script (charts auto-init, theme toggle).
     * - `defaultMode`: `system`, `light` or `dark`.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'version' => '1.1.0',
        'cdn' => 'https://cdn.jsdelivr.net/npm/basecoat-css@{{version}}/dist/',
        'style' => null,
        'css' => null,
        'tailwindBrowser' => 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.2.4',
        'tailwindTheme' => true,
        'font' => 'https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&display=swap',
        'fontFamily' => '"Geist", ui-sans-serif, system-ui, sans-serif',
        'js' => '{{cdn}}js/all.min.js',
        'chartJs' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.js',
        'chartJsBasecoat' => '{{cdn}}js/chart.min.js',
        'pluginJs' => 'BasecoatTheme.basecoat-theme',
        'charts' => false,
        'defaultMode' => 'system',
        'storageKey' => 'themeMode',
        'class' => null,
        'attributes' => [],
        'templates' => [
            'darkModeScript' => '<script{{attrs}}>{{content}}</script>',
            'tailwindTheme' => '<style type="text/tailwindcss"{{attrs}}>{{content}}</style>',
            'toggleContent' => '<span class="hidden dark:inline">{{sun}}</span><span class="dark:hidden">{{moon}}</span>',
        ],
    ];

    /**
     * Renders everything needed in `<head>`.
     *
     * @param array<string, mixed> $options Options passed to css() and scripts() (e.g. `charts`).
     * @return string
     */
    public function head(array $options = []): string
    {
        return $this->darkModeScript() . "\n" . $this->css($options) . "\n" . $this->scripts($options);
    }

    /**
     * Inline script that applies the `.dark` class before first paint.
     *
     * @return string
     */
    public function darkModeScript(): string
    {
        $key = json_encode((string)$this->getConfig('storageKey'));
        $default = json_encode((string)$this->getConfig('defaultMode'));
        $js = "(function(){try{var m=localStorage.getItem({$key})||{$default};"
            . "if(m==='dark'||(m==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches))"
            . "{document.documentElement.classList.add('dark');}}catch(e){}})();";

        return $this->formatTemplate('darkModeScript', [
            'content' => $js,
            'attrs' => $this->nonceAttribute('cspScriptNonce'),
        ]);
    }

    /**
     * Stylesheets: font, Basecoat CSS (or your own) and the Tailwind theme mapping.
     *
     * @param array<string, mixed> $options `block` (see HtmlHelper::css()).
     * @return string
     */
    public function css(array $options = []): string
    {
        $block = $options['block'] ?? null;
        $out = '';
        $font = $this->getConfig('font');
        if ($font) {
            $preconnect = $this->Html->tag('link', null, ['rel' => 'preconnect', 'href' => 'https://fonts.googleapis.com'])
                . $this->Html->tag('link', null, [
                    'rel' => 'preconnect',
                    'href' => 'https://fonts.gstatic.com',
                    'crossorigin' => 'anonymous',
                ]);
            if ($block) {
                $this->getView()->append($block === true ? 'css' : (string)$block, $preconnect);
            } else {
                $out .= $preconnect;
            }
            $out .= (string)$this->Html->css($font, ['block' => $block]);
        }

        $css = $this->getConfig('css') ?: $this->basecoatCss();
        $out .= (string)$this->Html->css($css, ['block' => $block]);

        if ($this->getConfig('tailwindBrowser') && $this->getConfig('tailwindTheme')) {
            $out .= $this->formatTemplate('tailwindTheme', [
                'content' => $this->tailwindTheme(),
                'attrs' => $this->nonceAttribute('cspStyleNonce'),
            ]);
        }

        return $out;
    }

    /**
     * Scripts: Tailwind browser build, Basecoat JS, optional Chart.js and the plugin script.
     *
     * @param array<string, mixed> $options `charts` (bool, auto-detected from ChartHelper when omitted), `block`.
     * @return string
     */
    public function scripts(array $options = []): string
    {
        $block = $options['block'] ?? null;
        $out = '';
        if ($this->getConfig('tailwindBrowser')) {
            $out .= (string)$this->Html->script($this->getConfig('tailwindBrowser'), ['block' => $block]);
        }
        if ($this->getConfig('js')) {
            $out .= (string)$this->Html->script($this->cdnUrl((string)$this->getConfig('js')), ['defer' => true, 'block' => $block]);
        }
        $charts = $options['charts'] ?? ($this->getConfig('charts') ?: $this->chartsRendered());
        if ($charts) {
            $out .= (string)$this->Html->script($this->getConfig('chartJs'), ['defer' => true, 'block' => $block]);
            $out .= (string)$this->Html->script(
                $this->cdnUrl((string)$this->getConfig('chartJsBasecoat')),
                ['defer' => true, 'block' => $block],
            );
        }
        if ($this->getConfig('pluginJs')) {
            $out .= (string)$this->Html->script($this->getConfig('pluginJs'), ['defer' => true, 'block' => $block]);
        }

        return $out;
    }

    /**
     * Whether ChartHelper rendered at least one chart in this view.
     *
     * @return bool
     */
    protected function chartsRendered(): bool
    {
        $helpers = $this->getView()->helpers();
        if (!$helpers->has('Chart')) {
            return false;
        }
        $chart = $helpers->get('Chart');

        return $chart instanceof ChartHelper && $chart->renderedCount() > 0;
    }

    /**
     * Light/dark toggle button (handled by the plugin script through `data-theme-toggle`).
     *
     * @param array<string, mixed> $options Button options plus `label`.
     * @return string
     */
    public function toggle(array $options = []): string
    {
        $label = (string)($options['label'] ?? __d('basecoat_theme', 'Toggle theme'));
        unset($options['label']);
        $content = $this->formatTemplate('toggleContent', [
            'sun' => $this->Icon->render('sun'),
            'moon' => $this->Icon->render('moon'),
        ]);
        $options += [
            'variant' => 'ghost',
            'size' => 'icon-sm',
            'aria-label' => $label,
            'data-theme-toggle' => 'true',
            'escape' => false,
        ];

        return $this->Button->render($content, $options);
    }

    /**
     * ` nonce="…"` from the request's CSP nonce (CakePHP CspMiddleware), or '' without CSP.
     * HtmlHelper adds it on its own to the scripts/stylesheets it renders.
     *
     * @param string $attribute `cspScriptNonce` or `cspStyleNonce`.
     * @return string
     */
    protected function nonceAttribute(string $attribute): string
    {
        $nonce = $this->getView()->getRequest()->getAttribute($attribute);

        return is_string($nonce) && $nonce !== '' ? ' nonce="' . h($nonce) . '"' : '';
    }

    /**
     * URL of the Basecoat CDN stylesheet for the configured style pack.
     *
     * @return string
     */
    public function basecoatCss(): string
    {
        $style = $this->getConfig('style');
        $file = $style ? 'basecoat-' . $style . '.cdn.min.css' : 'basecoat.cdn.min.css';

        return $this->cdnUrl('{{cdn}}' . $file);
    }

    /**
     * Resolves `{{cdn}}` and `{{version}}` placeholders.
     *
     * @param string $url URL.
     * @return string
     */
    protected function cdnUrl(string $url): string
    {
        $cdn = str_replace('{{version}}', (string)$this->getConfig('version'), (string)$this->getConfig('cdn'));

        return str_replace(['{{cdn}}', '{{version}}'], [$cdn, (string)$this->getConfig('version')], $url);
    }

    /**
     * Tailwind v4 `@theme` mapping of the Basecoat CSS variables.
     *
     * @return string
     */
    protected function tailwindTheme(): string
    {
        $tokens = [
            'background', 'foreground', 'card', 'card-foreground', 'popover', 'popover-foreground',
            'primary', 'primary-foreground', 'secondary', 'secondary-foreground', 'muted', 'muted-foreground',
            'accent', 'accent-foreground', 'destructive', 'border', 'input', 'ring',
            'chart-1', 'chart-2', 'chart-3', 'chart-4', 'chart-5',
            'sidebar', 'sidebar-foreground', 'sidebar-primary', 'sidebar-primary-foreground',
            'sidebar-accent', 'sidebar-accent-foreground', 'sidebar-border', 'sidebar-ring',
        ];
        $colors = implode('', array_map(fn($t) => "--color-{$t}: var(--{$t});", $tokens));
        $font = (string)$this->getConfig('fontFamily');

        return '@custom-variant dark (&:where(.dark, .dark *));'
            . '@theme inline {--font-sans: ' . $font . ';' . $colors . '}'
            . '@layer base {*, ::before, ::after { border-color: var(--border); } '
            // Tailwind v4 preflight leaves buttons with the default cursor
            . 'button:not(:disabled), [role="button"]:not([aria-disabled="true"]), summary, select, label[for], input[type="checkbox"]:not(:disabled), input[type="radio"]:not(:disabled), input[type="file"]:not(:disabled) { cursor: pointer; } :disabled, [aria-disabled="true"] { cursor: not-allowed; }}'
            // Basecoat menus, comboboxes and selects use cursor: default (components layer, so same layer here)
            . '@layer components {.dropdown-menu :is([role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]):not([aria-disabled="true"]), .command [role="menuitem"]:not([aria-disabled="true"]), .combobox [role="option"]:not([aria-disabled="true"]), .select:not(select) [role="option"]:not([aria-disabled="true"]), .combobox [data-clear]:not(:disabled) { cursor: pointer; }}';
    }
}
