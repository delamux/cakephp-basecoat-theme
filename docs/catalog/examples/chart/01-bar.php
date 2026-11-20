<?php
/**
 * @title Bar chart
 * @description Rows are keyed arrays; series maps each key to a label and a theme colour.
 */
$data = [
    ['month' => 'Jan', 'desktop' => 186, 'mobile' => 80],
    ['month' => 'Feb', 'desktop' => 305, 'mobile' => 200],
    ['month' => 'Mar', 'desktop' => 237, 'mobile' => 120],
    ['month' => 'Apr', 'desktop' => 173, 'mobile' => 190],
];
// ---
echo $this->Chart->render($data, [
    'desktop' => ['label' => 'Desktop', 'color' => 'var(--chart-1)'],
    'mobile' => ['label' => 'Mobile', 'color' => 'var(--chart-2)'],
], ['type' => 'bar', 'labelKey' => 'month', 'legend' => true, 'class' => 'h-[250px] w-full']);
