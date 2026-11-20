<?php
/**
 * @title Range selector
 * @description Array buttons share the button option; raw HTML strings are accepted too.
 */
echo $this->ButtonGroup->render([
    ['text' => 'Last 3 months', 'variant' => 'secondary', 'aria-pressed' => 'true'],
    ['text' => 'Last 30 days'],
    $this->Button->render('Last 7 days', ['variant' => 'outline', 'size' => 'sm']),
], ['aria-label' => 'Date range', 'button' => ['variant' => 'outline', 'size' => 'sm']]);
