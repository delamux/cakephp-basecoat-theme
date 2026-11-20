<?php
/**
 * @title Bar chart
 * @description Rows are keyed arrays; series maps each key to a label and a theme colour.
 */
$data = [
    ['month' => 'Jul', 'web' => 412, 'api' => 158],
    ['month' => 'Aug', 'web' => 468, 'api' => 203],
    ['month' => 'Sep', 'web' => 431, 'api' => 241],
    ['month' => 'Oct', 'web' => 509, 'api' => 276],
];
// ---
echo $this->Chart->render($data, [
    'web' => ['label' => 'Dashboard signups', 'color' => 'var(--chart-1)'],
    'api' => ['label' => 'API signups', 'color' => 'var(--chart-2)'],
], ['type' => 'bar', 'labelKey' => 'month', 'legend' => true, 'class' => 'h-[250px] w-full']);
