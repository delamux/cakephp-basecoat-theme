<?php
/**
 * @title Icon-only and collapsing buttons
 * @description icon() takes an accessible label; collapse hides the text below a breakpoint.
 */
echo $this->Button->icon('panel-left', 'Toggle sidebar', ['variant' => 'ghost', 'size' => 'icon-sm']);
echo $this->Button->icon('plus', 'Add item', ['variant' => 'outline']);
echo $this->Button->render('New invoice', ['icon' => 'plus', 'collapse' => 'md']);
