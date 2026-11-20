<?php
/**
 * @title Quick presets
 * @description Preset buttons fill both dates at once; the field starts empty and shows its placeholder.
 */
$presets = [
    'Last 7 days' => ['2026-11-13', '2026-11-20'],
    'This month' => ['2026-11-01', '2026-11-30'],
    'Last quarter' => ['2026-07-01', '2026-09-30'],
];
// ---
echo $this->DateRangeField->render('ordered', [
    'label' => 'Ordered',
    'presets' => $presets,
    'max' => '2026-12-31',
]);
