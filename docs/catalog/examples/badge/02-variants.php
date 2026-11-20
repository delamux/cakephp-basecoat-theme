<?php
/**
 * @title Variants
 * @description Primary by default, with secondary, outline, destructive and ghost looks.
 */
echo $this->Badge->render('Primary');
echo $this->Badge->render('Secondary', ['variant' => 'secondary']);
echo $this->Badge->render('Outline', ['variant' => 'outline']);
echo $this->Badge->render('Destructive', ['variant' => 'destructive']);
echo $this->Badge->render('Ghost', ['variant' => 'ghost']);
