<?php
/**
 * @title Variants
 * @description The six badge variants: primary, secondary, outline, destructive, ghost and link.
 */
echo $this->Badge->render('Primary');
echo $this->Badge->render('Secondary', ['variant' => 'secondary']);
echo $this->Badge->render('Outline', ['variant' => 'outline']);
echo $this->Badge->render('Destructive', ['variant' => 'destructive']);
echo $this->Badge->render('Ghost', ['variant' => 'ghost']);
echo $this->Badge->render('Link', ['variant' => 'link', 'url' => '#']);
