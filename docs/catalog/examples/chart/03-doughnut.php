<?php
/**
 * @title Doughnut chart
 * @description Pie-like types work the same way: one row per slice.
 */
$data = [
    ['label' => 'Starter', 'workspaces' => 342],
    ['label' => 'Growth', 'workspaces' => 129],
    ['label' => 'Scale', 'workspaces' => 41],
    ['label' => 'Trial', 'workspaces' => 88],
];
// ---
echo $this->Chart->render($data, [
    'workspaces' => ['label' => 'Workspaces', 'color' => 'var(--chart-1)'],
], ['type' => 'doughnut', 'legend' => true, 'class' => 'mx-auto h-[250px]']);
