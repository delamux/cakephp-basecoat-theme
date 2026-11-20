<?php
/**
 * @title Icon and link badges
 * @description Add an icon at either end, or turn the badge into a link with url.
 */
echo $this->Badge->render('+12.5%', ['variant' => 'outline', 'icon' => 'trending-up']);
echo $this->Badge->render('Verified', ['icon' => 'circle-check', 'iconPosition' => 'end']);
echo $this->Badge->render('Changelog', ['url' => '/changelog', 'variant' => 'link']);
