<?php
declare(strict_types=1);

/**
 * Sample data of the `calendar` screen: events of the fictional Fernway workspace in November 2026.
 *
 * View variables: `year` and `month` (the month shown), `today` (`Y-m-d`, the current day: it comes
 * from your action, the template never reads the clock), `categories` (key => label; keys `meeting`,
 * `deadline`, `release` and `team` have a colour, any other key a neutral one) and `events` (id, title,
 * `date` as `Y-m-d`, optional `time` as `H:i`, category). The template builds the grid from `year`/`month`.
 */
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
    'events' => [
        ['id' => 1, 'title' => 'Quarterly review with Lantern Books', 'date' => '2026-10-29', 'time' => '10:00', 'category' => 'meeting'],
        ['id' => 2, 'title' => 'Team offsite planning', 'date' => '2026-11-02', 'time' => '09:30', 'category' => 'team'],
        ['id' => 3, 'title' => 'Kickoff with Quillon Studio', 'date' => '2026-11-04', 'time' => '14:00', 'category' => 'meeting'],
        ['id' => 4, 'title' => 'Usage report due', 'date' => '2026-11-06', 'time' => null, 'category' => 'deadline'],
        ['id' => 5, 'title' => 'Release 4.2 to staging', 'date' => '2026-11-10', 'time' => '11:00', 'category' => 'release'],
        ['id' => 6, 'title' => 'Retro with Elena and Kofi', 'date' => '2026-11-12', 'time' => '16:00', 'category' => 'team'],
        ['id' => 7, 'title' => 'Field mapping review, Harbor & Pine', 'date' => '2026-11-18', 'time' => '15:30', 'category' => 'meeting'],
        ['id' => 8, 'title' => 'Support rota handover', 'date' => '2026-11-20', 'time' => '09:00', 'category' => 'team'],
        ['id' => 9, 'title' => 'Renewal quote for Lantern Books', 'date' => '2026-11-20', 'time' => '17:00', 'category' => 'deadline'],
        ['id' => 10, 'title' => 'Churn review, Brightmoor Clinic', 'date' => '2026-11-24', 'time' => '10:30', 'category' => 'meeting'],
        ['id' => 11, 'title' => 'Release 4.2 to production', 'date' => '2026-11-24', 'time' => '13:00', 'category' => 'release'],
        ['id' => 12, 'title' => 'Seat count check, Ostrava Freight', 'date' => '2026-11-24', 'time' => '14:30', 'category' => 'deadline'],
        ['id' => 13, 'title' => 'Pavel and Noor pairing session', 'date' => '2026-11-24', 'time' => '16:00', 'category' => 'team'],
        ['id' => 14, 'title' => 'Onboarding call, Quillon Studio', 'date' => '2026-11-25', 'time' => '11:30', 'category' => 'meeting'],
        ['id' => 15, 'title' => 'Billing export freeze', 'date' => '2026-11-27', 'time' => null, 'category' => 'deadline'],
        ['id' => 16, 'title' => 'Q1 customer success plan', 'date' => '2026-12-03', 'time' => '10:00', 'category' => 'team'],
    ],
];
