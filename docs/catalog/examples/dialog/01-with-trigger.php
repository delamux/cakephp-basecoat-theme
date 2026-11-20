<?php
/**
 * @title Dialog with built-in trigger
 * @description The trigger option renders the opening button right before the dialog.
 */
echo $this->Dialog->render('edit-profile', '<p class="text-sm">Update your display name and email address.</p>', [
    'title' => 'Edit profile',
    'description' => 'Make changes to your profile here.',
    'trigger' => ['text' => 'Edit profile', 'variant' => 'outline'],
    'footer' => $this->Dialog->close('Cancel', ['variant' => 'outline']) . $this->Button->submit('Save changes'),
]);
