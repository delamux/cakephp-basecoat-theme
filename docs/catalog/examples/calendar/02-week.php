<?php
/**
 * @title Week
 * @description Monday to Friday (`days` 1–5; 0 is Sunday) from 08:00 to 20:00. Hours are rows, each day a column of the same width; the grid scrolls sideways when the columns do not fit. Overlapping events sit side by side, the 07:30 standup is cut at 08:00 and all-day events get their own row.
 */
$events = [
    ['id' => 1, 'title' => 'Support standup', 'date' => '2026-11-16', 'time' => '07:30', 'end' => '09:00', 'resource' => 'noor', 'category' => 'team'],
    ['id' => 2, 'title' => 'Invoice run', 'date' => '2026-11-17', 'time' => '13:00', 'end' => '14:30', 'resource' => 'ines', 'category' => 'deadline'],
    ['id' => 3, 'title' => 'Billing sync, Quillon Studio', 'date' => '2026-11-18', 'time' => '10:00', 'end' => '11:00', 'resource' => 'kofi', 'category' => 'meeting'],
    ['id' => 4, 'title' => 'Roadmap workshop', 'date' => '2026-11-18', 'time' => '10:30', 'end' => '12:00', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 5, 'title' => 'Fernway all-hands', 'date' => '2026-11-19', 'time' => null, 'category' => 'team'],
    ['id' => 6, 'title' => 'Release notes for 4.2', 'date' => '2026-11-19', 'time' => '14:00', 'end' => '16:00', 'resource' => 'pavel', 'category' => 'release'],
    ['id' => 7, 'title' => 'Renewal quote', 'date' => '2026-11-20', 'time' => '17:00', 'end' => '18:00', 'resource' => 'kofi', 'category' => 'deadline'],
];
// ---
echo $this->Calendar->week($events, [
    'date' => '2026-11-18',
    'today' => '2026-11-18',
    'days' => [1, 2, 3, 4, 5],
    'start' => '08:00',
    'end' => '20:00',
    'columnWidth' => 'sm',
    'resources' => ['elena' => 'Elena Sarto', 'kofi' => 'Kofi Mensah', 'ines' => 'Ines Duarte', 'pavel' => 'Pavel Novak', 'noor' => 'Noor Haddad'],
    'categories' => ['meeting' => 'Customer meeting', 'deadline' => 'Deadline', 'release' => 'Release', 'team' => 'Team'],
    'dayUrl' => fn (string $date): string => '#day-' . $date,
]);
