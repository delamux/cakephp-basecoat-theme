<?php
/**
 * @title Line chart with Chart.js options
 * @description chartOptions is passed to Chart.js as JSON; canvasOptions sets accessible attributes.
 */
$data = [
    ['week' => 'W1', 'signups' => 42],
    ['week' => 'W2', 'signups' => 58],
    ['week' => 'W3', 'signups' => 51],
    ['week' => 'W4', 'signups' => 77],
    ['week' => 'W5', 'signups' => 90],
];
// ---
echo $this->Chart->render($data, [
    'signups' => ['label' => 'Sign-ups', 'color' => 'var(--chart-1)'],
], [
    'type' => 'line',
    'labelKey' => 'week',
    'chartOptions' => ['scales' => ['y' => ['beginAtZero' => true]]],
    'canvasOptions' => ['aria-label' => 'Weekly sign-ups'],
    'class' => 'h-[250px] w-full',
]);
