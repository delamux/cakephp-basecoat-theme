<?php
/**
 * @title Collapsed breadcrumb
 * @description With collapse, the middle crumbs become an ellipsis on long trails.
 */
echo $this->Breadcrumb->render([
    'Home' => '/',
    'Projects' => '/projects',
    'Acme Website' => '/projects/1',
    'Design' => '/projects/1/design',
    'Components' => '/projects/1/design/components',
    'Buttons',
], ['collapse' => 4]);
