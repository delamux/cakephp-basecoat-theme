<?php
declare(strict_types=1);

/**
 * Sample data of the `notes` screen: a notes workspace of the fictional Fernway team.
 *
 * View variables: `notes` (id, type text|checklist|image, title, body, items [label, done], image
 * [art hills|waves, alt], labels [label ids], updated), `labels` (id, name, color, count), `total`
 * (all notes) and `archivedCount`. Label colours: red, orange, green, blue, purple.
 */
return [
    'notes' => [
        [
            'id' => 1,
            'type' => 'text',
            'title' => 'Harbor & Pine kickoff agenda',
            'body' => "Walk through the data migration plan, confirm the field mapping and agree who owns the cut-over on Thursday. Kofi brings the sandbox credentials.",
            'labels' => [1],
            'updated' => 'Edited 20 min ago',
        ],
        [
            'id' => 2,
            'type' => 'checklist',
            'title' => 'Launch checklist for Lantern Books',
            'items' => [
                ['label' => 'Import the catalogue CSV', 'done' => true],
                ['label' => 'Check seat count on the Starter plan', 'done' => true],
                ['label' => 'Send the welcome email to Noor', 'done' => false],
                ['label' => 'Book the training call', 'done' => false],
            ],
            'labels' => [1, 2],
            'updated' => 'Edited 2 hours ago',
        ],
        [
            'id' => 3,
            'type' => 'image',
            'title' => 'Offsite moodboard',
            'body' => 'Calm colours, long tables and plenty of daylight. Ines is collecting venues near the coast.',
            'image' => ['art' => 'waves', 'alt' => 'Abstract blue waves under a pale sky'],
            'labels' => [3],
            'updated' => 'Edited yesterday',
        ],
        [
            'id' => 4,
            'type' => 'text',
            'title' => 'Ideas for the November newsletter',
            'body' => "Customer story from Quillon Studio.\nShort changelog with three highlights.\nA tip of the month from support.",
            'labels' => [2],
            'updated' => 'Edited yesterday',
        ],
        [
            'id' => 5,
            'type' => 'checklist',
            'title' => 'Packing list: Ostrava trip',
            'items' => [
                ['label' => 'Laptop and charger', 'done' => true],
                ['label' => 'Printed agenda for Pavel', 'done' => false],
                ['label' => 'Travel adapter', 'done' => false],
            ],
            'labels' => [3],
            'updated' => 'Edited 2 days ago',
        ],
        [
            'id' => 6,
            'type' => 'image',
            'title' => 'Brightmoor Clinic booth sketch',
            'body' => '',
            'image' => ['art' => 'hills', 'alt' => ''],
            'labels' => [1, 4],
            'updated' => 'Edited 3 days ago',
        ],
        [
            'id' => 7,
            'type' => 'text',
            'title' => 'Questions for Elena',
            'body' => 'Which churn cohort goes into the November chart? Can we share the draft with customers before Friday?',
            'labels' => [4],
            'updated' => 'Edited 4 days ago',
        ],
        [
            'id' => 8,
            'type' => 'text',
            'title' => 'Reading list',
            'body' => 'Two papers on onboarding flows and the pricing page teardown Mireia recommended.',
            'updated' => 'Edited last week',
        ],
    ],
    'labels' => [
        ['id' => 1, 'name' => 'Customers', 'color' => 'blue', 'count' => 3],
        ['id' => 2, 'name' => 'Marketing', 'color' => 'orange', 'count' => 2],
        ['id' => 3, 'name' => 'Travel', 'color' => 'green', 'count' => 2],
        ['id' => 4, 'name' => 'Ideas', 'color' => 'purple', 'count' => 2],
    ],
    'total' => 8,
    'archivedCount' => 14,
];
