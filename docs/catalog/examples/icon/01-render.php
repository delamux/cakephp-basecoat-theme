<?php
/**
 * @title Rendering icons
 * @description Lucide icons by name; class adds Tailwind utilities, title makes it accessible.
 */
echo $this->Icon->render('circle-check');
echo $this->Icon->render('loader', ['class' => 'size-5 animate-spin']);
echo $this->Icon->render('bell', ['class' => 'size-6 text-primary', 'title' => 'Notifications']);
