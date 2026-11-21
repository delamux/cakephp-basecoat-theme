<?php
/**
 * @title Day with resources
 * @description One column per resource: people here, but companies, rooms or anything else work the same. Events without a known resource go to a last "Unassigned" column; events outside the hours are listed in the column header.
 */
$events = [
    ['id' => 1, 'title' => 'Onboarding call, Brightmoor Clinic', 'date' => '2026-11-18', 'time' => '09:00', 'end' => '10:00', 'resource' => 'ines', 'category' => 'meeting'],
    ['id' => 2, 'title' => 'Billing sync, Quillon Studio', 'date' => '2026-11-18', 'time' => '10:00', 'end' => '11:00', 'resource' => 'kofi', 'category' => 'meeting'],
    ['id' => 3, 'title' => 'Roadmap workshop', 'date' => '2026-11-18', 'time' => '10:30', 'end' => '12:00', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 4, 'title' => 'Pairing session', 'date' => '2026-11-18', 'time' => '11:00', 'end' => '12:30', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 5, 'title' => 'Release 4.2 to staging', 'date' => '2026-11-18', 'time' => '14:00', 'end' => '15:00', 'resource' => 'pavel', 'category' => 'release'],
    ['id' => 6, 'title' => 'Night maintenance window', 'date' => '2026-11-18', 'time' => '22:00', 'end' => '23:00', 'resource' => 'pavel', 'category' => 'release'],
    ['id' => 7, 'title' => 'Office plants delivery', 'date' => '2026-11-18', 'time' => '13:00', 'end' => '13:30'],
];
// ---
echo $this->Calendar->day($events, [
    'date' => '2026-11-18',
    'today' => '2026-11-18',
    'start' => '08:00',
    'end' => '18:00',
    'resources' => ['elena' => 'Elena Sarto', 'kofi' => 'Kofi Mensah', 'ines' => 'Ines Duarte', 'pavel' => 'Pavel Novak'],
    'categories' => ['meeting' => 'Customer meeting', 'release' => 'Release', 'team' => 'Team'],
]);
