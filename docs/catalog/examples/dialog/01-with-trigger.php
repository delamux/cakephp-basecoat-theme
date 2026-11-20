<?php
/**
 * @title Dialog with built-in trigger
 * @description The trigger option renders the opening button right before the dialog.
 */
echo $this->Dialog->render('digest-settings', '<p class="text-sm">Weekly digests go out every Monday at 08:00 in your time zone.</p>', [
    'title' => 'Weekly digest',
    'description' => 'Choose who receives the Fernway summary email.',
    'trigger' => ['text' => 'Digest settings', 'variant' => 'outline'],
    'footer' => $this->Dialog->close('Cancel', ['variant' => 'outline']) . $this->Button->submit('Save changes'),
]);
