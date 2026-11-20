<?php
/**
 * @title Variants
 * @description Primary is the default; pick another look with the variant option.
 */
echo $this->Button->render('Primary');
echo $this->Button->render('Secondary', ['variant' => 'secondary']);
echo $this->Button->render('Outline', ['variant' => 'outline']);
echo $this->Button->render('Ghost', ['variant' => 'ghost']);
echo $this->Button->render('Link', ['variant' => 'link']);
echo $this->Button->render('Destructive', ['variant' => 'destructive']);
