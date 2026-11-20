<?php
/**
 * @title Checkbox and radio items
 * @description Toggle and single-choice items with a checked state.
 */
echo $this->DropdownMenu->render('View options', [
    ['type' => 'heading', 'label' => 'Columns'],
    ['type' => 'checkbox', 'label' => 'Status', 'checked' => true],
    ['type' => 'checkbox', 'label' => 'Owner'],
    '-',
    ['type' => 'heading', 'label' => 'Density'],
    ['type' => 'radio', 'label' => 'Comfortable', 'checked' => true],
    ['type' => 'radio', 'label' => 'Compact'],
], ['id' => 'view-options', 'side' => 'bottom', 'trigger' => ['variant' => 'outline', 'icon' => 'columns-2']]);
