<?php
/**
 * @title Basic tabs
 * @description label => panel HTML pairs; the id prefixes the tab and panel ids.
 */
echo $this->Tabs->render([
    'Usage' => '<p class="text-sm">1.2M of 1.5M events used this month.</p>',
    'Exports' => '<p class="text-sm">Daily export to the warehouse at 02:00 UTC.</p>',
    'Alerts' => '<p class="text-sm">3 alert rules, last triggered on November 18.</p>',
], ['id' => 'workspace']);
