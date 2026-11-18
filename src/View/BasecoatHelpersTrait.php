<?php
declare(strict_types=1);

namespace BasecoatTheme\View;

/**
 * Loads every Basecoat helper in a View.
 *
 * ```
 * // src/View/AppView.php
 * use BasecoatTheme\View\BasecoatHelpersTrait;
 *
 * class AppView extends View
 * {
 *     use BasecoatHelpersTrait;
 *
 *     public function initialize(): void
 *     {
 *         parent::initialize();
 *         $this->loadBasecoatHelpers([
 *             'Button' => ['size' => 'sm'],        // per helper config
 *             'Table' => ['class' => 'text-sm'],
 *         ]);
 *     }
 * }
 * ```
 *
 * @mixin \Cake\View\View
 */
trait BasecoatHelpersTrait
{
    /**
     * Loads all Basecoat helpers.
     *
     * @param array<string, array<string, mixed>|false> $config Config per alias. `false` skips a helper
     *   (e.g. `['Form' => false]` keeps the core FormHelper).
     * @return void
     */
    public function loadBasecoatHelpers(array $config = []): void
    {
        foreach (BasecoatHelpers::MAP as $alias => $className) {
            if (($config[$alias] ?? []) === false) {
                continue;
            }
            $this->addHelper($alias, ['className' => $className] + (array)($config[$alias] ?? []));
        }
    }
}
