<?php
/**
 * Search and filter bar of a list: the controls of BasecoatTheme\Listing\ListFilter::controls()
 * (the table's friendsofcake/search filters).
 *
 * A plain GET form: works without JavaScript (Filter button) and, with htmx, refreshes only the
 * results (`$target`) while typing or changing a filter, keeping the URL shareable (`hx-push-url`).
 * The bar stays outside `$target` so the search box keeps the focus while results change.
 *
 * @var \Cake\View\View $this
 * @var \BasecoatTheme\Listing\ListFilter $list
 * @var string|null $target Id of the results container (default `list-results`).
 */
$controls = $list->controls();
if ($controls === []) {
    return;
}
$target ??= 'list-results';
$query = $this->getRequest()->getQueryParams();

echo $this->Form->create(null, [
    'type' => 'get',
    'valueSources' => ['query'],
    'role' => 'search',
    'class' => 'flex flex-wrap items-end gap-3',
    'hx-get' => $this->getRequest()->getPath(),
    'hx-target' => '#' . $target,
    'hx-select' => '#' . $target,
    'hx-swap' => 'outerHTML',
    'hx-push-url' => 'true',
    'hx-trigger' => 'input changed delay:400ms from:find input[type=search], change, submit',
]);
// The list order survives a new search (the page does not: the results change)
foreach (['sort', 'direction'] as $param) {
    if (isset($query[$param]) && is_string($query[$param])) {
        echo $this->Form->hidden($param, ['value' => $query[$param]]);
    }
}

foreach ($controls as $name => $control) {
    // Each control keeps its own width in the row (Basecoat's `.field` would take it all)
    echo '<div class="' . ($control['type'] === 'search' ? 'w-full sm:w-64' : 'w-full sm:w-auto') . '">';
    if ($control['type'] === 'search') {
        echo $this->Form->control($name, [
            'type' => 'search',
            'label' => ['text' => $control['label'], 'class' => 'sr-only'],
            'placeholder' => __d('basecoat_theme', 'Search…'),
            'autocomplete' => 'off',
            'maxlength' => 100,
            'required' => false,
        ]);
    } elseif ($control['type'] === 'dateRange') {
        echo '<fieldset class="grid grid-cols-2 items-end gap-2 sm:flex">'
            . '<legend class="sr-only">' . h($control['label']) . '</legend>';
        echo $this->Form->control($name . '.from', [
            'type' => 'date',
            'label' => __d('basecoat_theme', '{0} from', $control['label']),
            'required' => false,
        ]);
        echo $this->Form->control($name . '.to', [
            'type' => 'date',
            'label' => __d('basecoat_theme', 'to'),
            'required' => false,
        ]);
        echo '</fieldset>';
    } elseif ($control['type'] === 'select') {
        echo $this->Form->control($name, [
            'type' => 'select',
            'label' => $control['label'],
            'options' => $control['options'] ?? [],
            'empty' => __d('basecoat_theme', 'All'),
            'required' => false,
        ]);
    } else {
        echo $this->Form->control($name, ['type' => 'text', 'label' => $control['label'], 'required' => false]);
    }
    echo '</div>';
}

echo '<div class="flex gap-2">';
echo $this->Form->button(__d('basecoat_theme', 'Filter'), ['type' => 'submit', 'variant' => 'secondary']);
if ($list->isFiltered()) {
    echo $this->Html->link(
        __d('basecoat_theme', 'Clear'),
        ['?' => array_intersect_key($query, array_flip(['sort', 'direction']))],
        ['class' => 'btn', 'data-variant' => 'ghost'],
    );
}
echo '</div>';
echo $this->Form->end();
