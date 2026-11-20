<?php
/**
 * @title Button variants and sizes
 * @description button() and submit() accept variant and size like the Button helper.
 */
echo $this->Form->create(null, ['url' => '/settings']);
echo $this->Form->button('Save', ['variant' => 'primary']);
echo $this->Form->button('Reset', ['type' => 'reset', 'variant' => 'outline']);
echo $this->Form->button('Delete account', ['variant' => 'destructive', 'size' => 'sm']);
echo $this->Form->submit('Update', ['variant' => 'secondary']);
echo $this->Form->end();
