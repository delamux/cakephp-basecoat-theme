<?php
/**
 * @title Custom icons
 * @description add() registers inner SVG markup (24x24 viewBox) under a new name.
 */
$this->Icon->add(['spark' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>']);
// ---
echo $this->Icon->render('spark', ['class' => 'size-6']);
echo $this->Button->render('Generate', ['icon' => 'spark']);
