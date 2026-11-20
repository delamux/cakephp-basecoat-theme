<?php
/**
 * @title Button variants and sizes
 * @description button() and submit() accept variant and size like the Button helper. A form with its own layout class (here a flex row) keeps it.
 */
echo $this->Form->create(null, ['url' => '/settings', 'class' => 'flex flex-wrap items-center gap-2']);
echo $this->Form->button('Save', ['variant' => 'primary']);
echo $this->Form->button('Reset', ['type' => 'reset', 'variant' => 'outline']);
echo $this->Form->button('Delete account', ['variant' => 'destructive', 'size' => 'sm']);
echo $this->Form->submit('Update', ['variant' => 'secondary']);
echo $this->Form->end();
