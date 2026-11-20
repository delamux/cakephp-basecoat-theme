<?php
/**
 * @title Icon-only and collapsing buttons
 * @description icon() takes an accessible label, rendered as visually hidden text inside the button; a size-11 class gives a 44px touch target; collapse hides the text below a breakpoint.
 */
echo $this->Button->icon('panel-left', 'Toggle sidebar', ['variant' => 'ghost', 'size' => 'icon-sm']);
echo $this->Button->icon('plus', 'Add item', ['variant' => 'outline']);
echo $this->Button->icon('bell', 'Notifications', ['variant' => 'outline', 'class' => 'size-11']);
echo $this->Button->render('New invoice', ['icon' => 'plus', 'collapse' => 'md']);
