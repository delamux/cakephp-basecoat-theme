<?php
/**
 * Makes sure the Basecoat helpers used by the theme templates are loaded.
 *
 * Helpers are loaded with an explicit `className`, the same config the helpers use
 * for their nested helpers, so the registry never sees conflicting definitions.
 * The app FormHelper is never replaced from here.
 *
 * @var \Cake\View\View $this
 */
use BasecoatTheme\View\BasecoatHelpers;

foreach (BasecoatHelpers::MAP as $basecoatAlias => $basecoatClass) {
    if ($basecoatAlias !== 'Form' && !$this->helpers()->has($basecoatAlias)) {
        $this->loadHelper($basecoatAlias, ['className' => $basecoatClass]);
    }
}
if (!$this->helpers()->has('Flash')) {
    $this->loadHelper('Flash');
}
