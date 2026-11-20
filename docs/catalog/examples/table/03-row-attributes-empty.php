<?php
/**
 * @title Row attributes, colspan and empty message
 * @description _row sets attributes on a row, cells accept colspan, and empty replaces an empty body.
 */
$rows = [
    ['name' => 'Acme Website', 'plan' => 'Pro', '_row' => ['class' => 'font-medium']],
    ['name' => ['content' => 'Trial accounts are listed in the archive', 'colspan' => 2]],
];
// ---
echo $this->Table->render(['name' => 'Account', 'plan' => 'Plan'], $rows);
echo $this->Table->render(['name' => 'Account', 'plan' => 'Plan'], [], ['empty' => 'No accounts found.', 'class' => 'mt-6']);
