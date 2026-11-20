<?php
/**
 * @title Sizes and icons
 * @description icon adds a leading or trailing icon; size scales the button.
 */
echo $this->Button->render('Save', ['icon' => 'check']);
echo $this->Button->render('Next', ['icon' => 'arrow-right', 'iconPosition' => 'end', 'variant' => 'outline']);
echo $this->Button->render('Small', ['size' => 'sm', 'variant' => 'secondary']);
echo $this->Button->render('Large', ['size' => 'lg']);
