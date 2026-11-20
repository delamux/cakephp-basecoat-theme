<?php
/**
 * @title Progress bars
 * @description A percentage by default; max sets another scale and aria attributes pass through.
 */
echo $this->Progress->render(60, ['aria-label' => 'Profile completion']);
echo $this->Progress->render(3, ['max' => 10, 'aria-label' => 'Files uploaded', 'class' => 'mt-4']);
