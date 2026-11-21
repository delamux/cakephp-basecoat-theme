<?php
/**
 * @title Month
 * @description A table of whole weeks, Monday first, with up to three event chips a day and "+N more". With `basecoat-dnd.js` chips can be dragged to another day; `dayUrl` links each day number to its day view.
 */
$events = [
    ['id' => 1, 'title' => 'Planning with Lantern Books', 'date' => '2026-11-03', 'time' => '10:00', 'category' => 'meeting'],
    ['id' => 2, 'title' => 'Usage report due', 'date' => '2026-11-06', 'time' => null, 'category' => 'deadline'],
    ['id' => 3, 'title' => 'Release 4.2 to staging', 'date' => '2026-11-10', 'time' => '11:00', 'category' => 'release'],
    ['id' => 4, 'title' => 'Team retro', 'date' => '2026-11-12', 'time' => '16:00', 'category' => 'team'],
    ['id' => 5, 'title' => 'Review, Harbor & Pine', 'date' => '2026-11-18', 'time' => '09:30', 'category' => 'meeting'],
    ['id' => 6, 'title' => 'Invoice run', 'date' => '2026-11-18', 'time' => '13:00', 'category' => 'deadline'],
    ['id' => 7, 'title' => 'Design crit', 'date' => '2026-11-18', 'time' => '15:00', 'category' => 'team'],
    ['id' => 8, 'title' => 'Release notes', 'date' => '2026-11-18', 'time' => '17:00', 'category' => 'release'],
];
// ---
echo '<div class="overflow-x-auto rounded-lg border">';
echo $this->Calendar->month($events, [
    'date' => '2026-11-01',
    'today' => '2026-11-18',
    'categories' => ['meeting' => 'Customer meeting', 'deadline' => 'Deadline', 'release' => 'Release', 'team' => 'Team'],
    'dayUrl' => fn (string $date): string => '#day-' . $date,
]);
echo '</div>';
