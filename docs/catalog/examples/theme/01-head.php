<?php
/**
 * @title Layout head
 * @description head() renders the dark-mode script, CSS and JS needed by every Basecoat page (code only: it would load the page assets twice).
 * @preview false
 */
echo $this->Theme->head(['charts' => true]);
