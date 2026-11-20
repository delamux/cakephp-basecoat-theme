<?php
/**
 * @title Separate trigger
 * @description trigger() can be placed anywhere in the page and targets the dialog by id.
 */
echo $this->Dialog->trigger('invite-member', 'Invite member', ['icon' => 'plus']);
echo $this->Dialog->render('invite-member', '<p class="text-sm">They will receive an email with a link to join.</p>', [
    'title' => 'Invite a teammate',
    'description' => 'Send an invitation to ines@example.com.',
    'footer' => $this->Dialog->close('Not now', ['variant' => 'outline']) . $this->Button->render('Send invite'),
]);
