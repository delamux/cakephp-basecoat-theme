<?php
/**
 * @title Metric card with trend
 * @description A KPI value with its change versus the previous period and a short footer.
 */
$label = 'Active users';
// ---
echo $this->MetricCard->render($label, '1,284', [
    'change' => '+12.4%',
    'trend' => 'up',
    'description' => 'vs. last month',
    'icon' => 'users',
]);
