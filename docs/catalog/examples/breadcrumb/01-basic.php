<?php
/**
 * @title Breadcrumb trail
 * @description label => URL pairs; the last crumb is the current page.
 */
echo $this->Breadcrumb->render([
    'Home' => '/',
    'Projects' => ['controller' => 'Projects', 'action' => 'index'],
    'Acme Website',
]);
