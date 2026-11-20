<?php
declare(strict_types=1);

/**
 * Sample data of the `bookings` screen: the day schedule of an invented training box, "Ironbark Box".
 *
 * View variables: `date` (Y-m-d), `dateLabel` (the date as shown in the header), `view` (`grid` or
 * `list`), `dayStart` / `dayEnd` (HH:MM, on the half hour), `resources` (id => name, one column
 * each, up to 6), `types` (class type => label) and `bookings` (id, resource, type, name, coach,
 * start, end, booked, capacity; start and end on the half hour, a class lasts 30 minutes to 4 hours).
 */
$booking = fn (int $id, string $resource, string $type, string $name, string $coach, string $start, string $end, int $booked, int $capacity): array => compact('id', 'resource', 'type', 'name', 'coach', 'start', 'end', 'booked', 'capacity');

return [
    'date' => '2026-11-20',
    'dateLabel' => 'Friday, 20 November 2026',
    'view' => 'grid',
    'dayStart' => '06:00',
    'dayEnd' => '21:00',
    'resources' => [
        'main' => 'Main floor',
        'rig' => 'Rig',
        'open' => 'Open gym',
        'ana' => 'Coach Ana',
    ],
    'types' => [
        'wod' => 'WOD',
        'lifting' => 'Weightlifting',
        'mobility' => 'Mobility',
        'open' => 'Open gym',
    ],
    'bookings' => [
        $booking(1, 'main', 'wod', 'Sunrise WOD', 'Elena Sarto', '06:00', '07:00', 14, 16),
        $booking(2, 'main', 'wod', 'Morning WOD', 'Kofi Mensah', '07:30', '08:30', 16, 16),
        $booking(3, 'main', 'wod', 'Lunch WOD', 'Ines Duarte', '12:00', '13:00', 9, 16),
        $booking(4, 'main', 'wod', 'Evening WOD', 'Pavel Novak', '17:30', '18:30', 15, 16),
        $booking(5, 'main', 'wod', 'Late WOD', 'Noor Haddad', '19:00', '20:00', 6, 16),
        $booking(6, 'rig', 'lifting', 'Olympic lifting', 'Kofi Mensah', '06:30', '08:00', 5, 8),
        $booking(7, 'rig', 'lifting', 'Strength block', 'Elena Sarto', '09:30', '10:30', 4, 8),
        $booking(8, 'rig', 'lifting', 'Squat clinic', 'Pavel Novak', '17:00', '18:30', 8, 8),
        $booking(9, 'open', 'open', 'Open gym', 'Noor Haddad', '09:00', '12:00', 7, 20),
        $booking(10, 'open', 'open', 'Open gym', 'Ines Duarte', '14:00', '17:00', 3, 20),
        $booking(11, 'open', 'open', 'Open gym', 'Pavel Novak', '18:00', '21:00', 12, 20),
        $booking(12, 'ana', 'mobility', 'Morning mobility', 'Ana Costa', '08:00', '09:00', 8, 12),
        $booking(13, 'ana', 'mobility', 'Desk reset', 'Ana Costa', '12:30', '13:00', 10, 12),
        $booking(14, 'ana', 'mobility', 'Recovery flow', 'Ana Costa', '16:00', '17:00', 5, 12),
        $booking(15, 'ana', 'mobility', 'Cool down', 'Ana Costa', '20:00', '21:00', 2, 12),
    ],
];
