<?php
/**
 * @title Interactive week
 * @description With `interactive` and `basecoat-dnd.js` the week is one 2D grid: drag an event to another time or day (or focus it and press Space, then the arrows), drag its bottom edge (or Shift + arrows) to change its end, and click an empty slot to create an event there (`slotUrl`). Locked events (`'locked' => true`, e.g. past ones) stay put. `basecoat:move` / `basecoat:resize` carry `detail.slot = {start, end, column}`; call `preventDefault()` to undo, or set `data-endpoint` to POST the change.
 */
$events = [
    ['id' => 1, 'title' => 'Support standup', 'date' => '2026-11-16', 'time' => '08:00', 'end' => '08:30', 'resource' => 'noor', 'category' => 'team', 'locked' => true],
    ['id' => 2, 'title' => 'Invoice run', 'date' => '2026-11-17', 'time' => '13:00', 'end' => '14:30', 'resource' => 'ines', 'category' => 'deadline'],
    ['id' => 3, 'title' => 'Billing sync, Quillon Studio', 'date' => '2026-11-18', 'time' => '10:00', 'end' => '11:00', 'resource' => 'kofi', 'category' => 'meeting'],
    ['id' => 4, 'title' => 'Roadmap workshop', 'date' => '2026-11-18', 'time' => '10:30', 'end' => '12:00', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 5, 'title' => 'Release notes for 4.2', 'date' => '2026-11-19', 'time' => '14:00', 'end' => '16:00', 'resource' => 'pavel', 'category' => 'release'],
    ['id' => 6, 'title' => 'Renewal quote', 'date' => '2026-11-20', 'time' => '17:00', 'end' => '18:00', 'resource' => 'kofi', 'category' => 'deadline'],
];
// ---
$this->Html->script('BasecoatTheme.basecoat-dnd', ['block' => true, 'defer' => true]);
$this->Html->css('BasecoatTheme.interactivity', ['block' => true]);

echo $this->Calendar->week($events, [
    'date' => '2026-11-18',
    'today' => '2026-11-18',
    'days' => [1, 2, 3, 4, 5],
    'start' => '08:00',
    'end' => '19:00',
    'slot' => 15,
    'columnWidth' => 'sm',
    'resources' => ['elena' => 'Elena Sarto', 'kofi' => 'Kofi Mensah', 'ines' => 'Ines Duarte', 'pavel' => 'Pavel Novak', 'noor' => 'Noor Haddad'],
    'categories' => ['meeting' => 'Customer meeting', 'deadline' => 'Deadline', 'release' => 'Release', 'team' => 'Team'],
    'interactive' => ['move' => true, 'resize' => true, 'slots' => true],
    'slotUrl' => fn (string $date, string $time, ?string $resource): string => '#new-' . $date . 'T' . $time,
]);
