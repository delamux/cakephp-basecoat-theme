<?php
/**
 * Default sidebar of the BasecoatTheme layout.
 *
 * Data sources (view vars win over Configure):
 * - `brand` / `BasecoatTheme.brand`: ['name' => 'Fernway', 'url' => '/', 'icon' => 'circle']
 * - `sidebarMenu` / `BasecoatTheme.sidebar`: menu for SidebarHelper::render()
 * - `sidebarActions` / `BasecoatTheme.sidebarActions`: HTML under the brand (e.g. a "Quick create" button)
 * - `sidebarUser`: ['name' => ..., 'email' => ..., 'avatar' => url, 'menu' => DropdownMenu items]
 *
 * Override it in your app with templates/plugin/BasecoatTheme/element/layout/sidebar.php
 * or with a `sidebar` view block.
 *
 * @var \Cake\View\View $this
 */
use Cake\Core\Configure;

$brand = (array)$this->get('brand', Configure::read('BasecoatTheme.brand', ['name' => 'CakePHP', 'url' => '/', 'icon' => 'circle']));
$menu = (array)$this->get('sidebarMenu', Configure::read('BasecoatTheme.sidebar', []));
$actions = (string)$this->get('sidebarActions', Configure::read('BasecoatTheme.sidebarActions', ''));
$user = $this->get('sidebarUser');

$header = sprintf(
    '<a href="%s" class="flex items-center gap-2 px-2 py-1.5 text-base font-semibold">%s<span>%s</span></a>',
    $this->Url->build($brand['url'] ?? '/'),
    $this->Icon->optional($brand['icon'] ?? null, ['class' => 'size-5']),
    h($brand['name'] ?? ''),
) . $actions;

$footer = $user ? $this->element('BasecoatTheme.layout/user_menu', ['user' => (array)$user]) : null;

echo $this->Sidebar->render($menu, [
    'label' => __d('basecoat_theme', 'Main navigation'),
    'header' => $header,
    'footer' => $footer,
]);
