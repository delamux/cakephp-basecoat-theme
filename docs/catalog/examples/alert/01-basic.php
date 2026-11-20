<?php
/**
 * @title Alert with icon
 * @description A title, a description and an optional icon.
 */
echo $this->Alert->render('Account updated', 'Your profile has been saved.', ['icon' => 'circle-check']);
