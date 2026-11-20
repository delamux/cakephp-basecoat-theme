<?php
/**
 * @title Toaster with initial toasts
 * @description Render the toaster once in the layout; toasts given here appear on page load.
 */
echo $this->Toast->toaster([
    ['title' => 'Saved', 'description' => 'Your changes were saved.'],
    ['category' => 'error', 'title' => 'Upload failed', 'description' => 'The file is too large.', 'duration' => -1],
], ['align' => 'end']);
