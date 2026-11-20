<?php
/**
 * @title Prefilled form with validation errors
 * @description Context data (here an array context, an entity works the same) fills values and shows error messages.
 */
$context = [
    'schema' => ['name' => ['type' => 'string'], 'visibility' => ['type' => 'string'], 'archived' => ['type' => 'boolean']],
    'defaults' => ['name' => 'Acme Website', 'visibility' => 'private', 'archived' => false],
    'errors' => ['name' => ['This name is already taken.']],
];
// ---
echo $this->Form->create($context, ['url' => '/projects/edit/1']);
echo $this->Form->control('name', ['label' => 'Project name']);
echo $this->Form->control('visibility', [
    'type' => 'radio',
    'label' => 'Visibility',
    'options' => ['private' => 'Private', 'public' => 'Public'],
]);
echo $this->Form->control('archived', ['type' => 'checkbox', 'label' => 'Archived']);
echo $this->Form->button('Save changes');
echo $this->Form->end();
