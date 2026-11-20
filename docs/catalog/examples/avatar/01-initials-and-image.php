<?php
/**
 * @title Initials and image
 * @description Without src the initials of the name are shown as fallback.
 */
echo $this->Avatar->render('Jane Doe');
echo $this->Avatar->render('Marta Vega', ['src' => '/assets/avatar-marta.svg']);
