<?php
declare(strict_types=1);

/**
 * Sample data of the `calendar` screen: events of the fictional Fernway workspace in November 2026.
 *
 * View variables: `year` and `month` (the month shown by default), `today` (`Y-m-d`, the current day:
 * it comes from your action, the template never reads the clock), `categories` (key => label; keys
 * `meeting`, `deadline`, `release` and `team` have a colour, any other key a neutral one), `resources`
 * (key => name: the columns of the day view), `days` (weekdays of the week view, 0 = Sunday ... 6 =
 * Saturday, in column order), `dayStart` / `dayEnd` (hours shown by the week and day views, H:i) and
 * `events` (id, title, `date` as `Y-m-d`, `time` as `H:i` or null for all day, optional `end` as `H:i`,
 * optional `resource`, category; optional `url`, the link of the event).
 */
$event = fn (int $id, string $title, string $date, ?string $time, ?string $end, string $category, ?string $resource): array => compact('id', 'title', 'date', 'time', 'end', 'category', 'resource');

return [
    'year' => 2026,
    'month' => 11,
    'today' => '2026-11-20',
    'categories' => [
        'meeting' => 'Customer meeting',
        'deadline' => 'Deadline',
        'release' => 'Release',
        'team' => 'Team',
    ],
    'resources' => [
        'elena' => 'Elena Sarto',
        'kofi' => 'Kofi Mensah',
        'ines' => 'Ines Duarte',
        'pavel' => 'Pavel Novak',
        'noor' => 'Noor Haddad',
    ],
    'days' => [1, 2, 3, 4, 5],
    'dayStart' => '08:00',
    'dayEnd' => '20:00',
    'events' => [
        $event(1, 'Quarterly review with Lantern Books', '2026-10-29', '10:00', '11:00', 'meeting', 'elena'),
        $event(2, 'Team offsite planning', '2026-11-02', '09:30', '10:30', 'team', 'noor'),
        $event(3, 'Kickoff with Quillon Studio', '2026-11-04', '14:00', '15:00', 'meeting', 'kofi'),
        $event(4, 'Usage report due', '2026-11-06', null, null, 'deadline', 'ines'),
        $event(5, 'Release 4.2 to staging', '2026-11-10', '11:00', '12:00', 'release', 'pavel'),
        $event(6, 'Retro with Elena and Kofi', '2026-11-12', '16:00', '17:00', 'team', 'elena'),
        $event(7, 'Field mapping review, Harbor & Pine', '2026-11-18', '15:30', '16:30', 'meeting', 'kofi'),
        $event(8, 'Support rota handover', '2026-11-20', '09:00', '10:00', 'team', 'noor'),
        $event(9, 'Renewal quote for Lantern Books', '2026-11-20', '17:00', '18:00', 'deadline', 'kofi'),
        $event(10, 'Churn review, Brightmoor Clinic', '2026-11-24', '10:30', '11:30', 'meeting', 'elena'),
        $event(11, 'Release 4.2 to production', '2026-11-24', '13:00', '14:00', 'release', 'pavel'),
        $event(12, 'Seat count check, Ostrava Freight', '2026-11-24', '14:30', '15:30', 'deadline', 'ines'),
        $event(13, 'Pavel and Noor pairing session', '2026-11-24', '16:00', '17:00', 'team', 'pavel'),
        $event(14, 'Onboarding call, Quillon Studio', '2026-11-25', '11:30', '12:30', 'meeting', 'kofi'),
        $event(15, 'Billing export freeze', '2026-11-27', null, null, 'deadline', 'ines'),
        $event(16, 'Q1 customer success plan', '2026-12-03', '10:00', '11:00', 'team', 'elena'),
        $event(17, 'Early standup, support crew', '2026-11-16', '07:30', '09:00', 'team', 'noor'),
        $event(18, 'Invoice run', '2026-11-17', '13:00', '14:30', 'deadline', 'ines'),
        $event(19, 'Billing sync with Quillon Studio', '2026-11-18', '10:00', '11:00', 'meeting', 'kofi'),
        $event(20, 'Roadmap workshop', '2026-11-18', '10:30', '12:00', 'team', 'elena'),
        $event(21, 'Fernway all-hands', '2026-11-19', null, null, 'team', null),
        $event(22, 'Release notes for 4.2', '2026-11-19', '14:00', '16:00', 'release', 'pavel'),
        $event(23, 'Onboarding prep, Brightmoor Clinic', '2026-11-20', '11:00', '12:00', 'meeting', 'ines'),
    ],
];
