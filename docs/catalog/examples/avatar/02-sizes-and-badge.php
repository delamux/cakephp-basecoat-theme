<?php
/**
 * @title Sizes and status badge
 * @description Use size for sm/lg and badge to add a status indicator.
 */
echo $this->Avatar->render('Ana Ruiz', ['size' => 'sm']);
echo $this->Avatar->render('Ana Ruiz');
echo $this->Avatar->render('Ana Ruiz', ['size' => 'lg', 'class' => 'rounded-lg']);
echo $this->Avatar->render('Leo Park', ['badge' => '3']);
