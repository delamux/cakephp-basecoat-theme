<?php
/**
 * @title Link and submit buttons
 * @description link() builds the URL with UrlHelper; submit() renders a type=submit button.
 */
echo $this->Button->link('View projects', ['controller' => 'Projects', 'action' => 'index'], ['variant' => 'outline']);
echo $this->Button->link('Documentation', '/docs', ['variant' => 'link']);
echo $this->Button->submit('Create account');
