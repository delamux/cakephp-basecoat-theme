<?php
/**
 * @title Line variant, badge label and disabled tab
 * @description Array tabs allow raw HTML labels (escape false) and a disabled state; active is 1-based.
 */
echo $this->Tabs->render([
    ['tab' => 'Overview', 'panel' => '<p class="text-sm">Key figures for this quarter.</p>'],
    ['tab' => 'Reports ' . $this->Badge->render('3', ['variant' => 'secondary']), 'panel' => '<p class="text-sm">Three reports are ready.</p>', 'escape' => false],
    ['tab' => 'Archive', 'panel' => '', 'disabled' => true],
], ['id' => 'reports', 'active' => 2, 'variant' => 'line']);
