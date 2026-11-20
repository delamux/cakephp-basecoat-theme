<?php
/**
 * @title Metric card with sparkline
 * @description A KPI card with a seven-day sparkline rendered by the Chart helper.
 */
$revenue = [
    ['day' => 'Mon', 'amount' => 920],
    ['day' => 'Tue', 'amount' => 1180],
    ['day' => 'Wed', 'amount' => 1040],
    ['day' => 'Thu', 'amount' => 1310],
    ['day' => 'Fri', 'amount' => 1490],
    ['day' => 'Sat', 'amount' => 1210],
    ['day' => 'Sun', 'amount' => 1270],
];
// ---
echo $this->MetricCard->render('Monthly revenue', '€8,420', [
    'change' => '-3.1%',
    'trend' => 'down',
    'description' => 'vs. last month',
    'chart' => [
        'data' => $revenue,
        'series' => ['amount' => ['label' => 'Revenue', 'color' => 'var(--chart-1)']],
        'type' => 'line',
        'labelKey' => 'day',
    ],
]);
