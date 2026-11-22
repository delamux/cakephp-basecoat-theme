<?php
/**
 * @title Interactive day with resources
 * @description In the day view an event can also move to another resource's column; `detail.slot.column` is then the resource key ("" for Unassigned). `slotUrl` receives the date, the time and the resource of the empty slot, and may return null for slots that cannot take a new event (here, before 10:00). `eventAttributes` adds attributes to each event link, e.g. `hx-get` or a menu trigger (`on*` and `style` are refused).
 */
$events = [
    ['id' => 1, 'title' => 'Onboarding call, Brightmoor Clinic', 'date' => '2026-11-18', 'time' => '09:00', 'end' => '10:00', 'resource' => 'ines', 'category' => 'meeting'],
    ['id' => 2, 'title' => 'Billing sync, Quillon Studio', 'date' => '2026-11-18', 'time' => '10:00', 'end' => '11:00', 'resource' => 'kofi', 'category' => 'meeting'],
    ['id' => 3, 'title' => 'Roadmap workshop', 'date' => '2026-11-18', 'time' => '10:30', 'end' => '12:00', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 4, 'title' => 'Pairing session', 'date' => '2026-11-18', 'time' => '11:00', 'end' => '12:30', 'resource' => 'elena', 'category' => 'team'],
    ['id' => 5, 'title' => 'Office plants delivery', 'date' => '2026-11-18', 'time' => '13:00', 'end' => '13:30'],
];
// ---
$this->Html->script('BasecoatTheme.basecoat-dnd', ['block' => true, 'defer' => true]);
$this->Html->css('BasecoatTheme.interactivity', ['block' => true]);

echo $this->Calendar->day($events, [
    'date' => '2026-11-18',
    'today' => '2026-11-18',
    'start' => '08:00',
    'end' => '16:00',
    'slot' => 30,
    'resources' => ['elena' => 'Elena Sarto', 'kofi' => 'Kofi Mensah', 'ines' => 'Ines Duarte'],
    'categories' => ['meeting' => 'Customer meeting', 'team' => 'Team'],
    'interactive' => ['move' => true, 'resize' => true, 'slots' => true],
    'slotUrl' => fn (string $date, string $time, ?string $resource): ?string => $time < '10:00' ? null : '#new-' . $date . 'T' . $time . '-' . $resource,
    'eventAttributes' => fn (array $event): array => ['data-event-menu' => 'event-' . $event['id']],
]);
