<?php
/**
 * @title Keyboard keys
 * @description A single key, or an array grouped with a "+" separator.
 */
echo $this->Kbd->render('⌘K');
echo $this->Kbd->render(['Ctrl', 'Shift', 'P']);
echo $this->Kbd->render(['Alt', 'Enter'], ['separator' => ' then ']);
