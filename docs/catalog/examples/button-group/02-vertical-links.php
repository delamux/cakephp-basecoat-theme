<?php
/**
 * @title Vertical group with links
 * @description Items with a url render as links; orientation stacks them.
 */
echo $this->ButtonGroup->render([
    ['text' => 'Overview', 'url' => '/overview'],
    ['text' => 'Billing', 'url' => '/billing'],
    ['text' => 'Members', 'url' => '/members'],
], ['orientation' => 'vertical', 'aria-label' => 'Account sections', 'button' => ['variant' => 'outline']]);
