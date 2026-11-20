<?php
/**
 * @title Doughnut chart
 * @description Pie-like types work the same way: one row per slice.
 */
$data = [
    ['label' => 'Chrome', 'visitors' => 275],
    ['label' => 'Safari', 'visitors' => 200],
    ['label' => 'Firefox', 'visitors' => 187],
    ['label' => 'Edge', 'visitors' => 173],
];
// ---
echo $this->Chart->render($data, [
    'visitors' => ['label' => 'Visitors', 'color' => 'var(--chart-1)'],
], ['type' => 'doughnut', 'legend' => true, 'class' => 'mx-auto h-[250px]']);
