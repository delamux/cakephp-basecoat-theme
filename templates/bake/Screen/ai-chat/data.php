<?php
declare(strict_types=1);

/**
 * Sample data of the `ai-chat` screen: a conversation with "Orrin", an invented assistant of the
 * fictional Fernway workspace.
 *
 * View variables: `assistant` (name, tagline), `suggestions` (prompt shortcuts of the hero),
 * `messages` (from `user` or `assistant`; `paragraphs` are plain text, `code` is an optional
 * `language` + `content` block, both escaped by the template), `models` (value => label, the
 * composer select) and `model` (selected value).
 */
return [
    'assistant' => [
        'name' => 'Orrin',
        'tagline' => 'Ask about your customers, plans and usage. Orrin drafts answers and code you can review.',
    ],
    'suggestions' => [
        'Summarize last week for Harbor & Pine',
        'Draft a renewal email for Lantern Books',
        'List customers with more than 20 seats',
        'Explain why Ostrava Freight usage dropped',
    ],
    'messages' => [
        [
            'id' => 1,
            'from' => 'user',
            'author' => 'Elena Sarto',
            'time' => '10:02',
            'paragraphs' => ['Which customers have more than 20 seats? I need it for the Scale plan review.'],
        ],
        [
            'id' => 2,
            'from' => 'assistant',
            'author' => 'Orrin',
            'time' => '10:02',
            'paragraphs' => [
                'Two customers are above 20 seats: Quillon Studio (25 seats) and Ostrava Freight (32 seats).',
                'To reproduce the list in your controller, filter the customers table by seat count:',
            ],
            'code' => [
                'language' => 'php',
                'content' => "\$customers = \$this->fetchTable('Customers')\n    ->find()\n    ->where(['seats >' => 20])\n    ->orderBy(['seats' => 'DESC'])\n    ->all();",
            ],
        ],
        [
            'id' => 3,
            'from' => 'user',
            'author' => 'Elena Sarto',
            'time' => '10:04',
            'paragraphs' => ['Thanks. Can you also flag the ones renewing this month?'],
        ],
        [
            'id' => 4,
            'from' => 'assistant',
            'author' => 'Orrin',
            'time' => '10:04',
            'paragraphs' => [
                'Quillon Studio renews on 28 November. Ostrava Freight renews in February, so nothing to do there yet.',
                'I can prepare a short renewal note for Quillon Studio if you want one.',
            ],
        ],
    ],
    'models' => [
        'orrin-fast' => 'Orrin Fast',
        'orrin-deep' => 'Orrin Deep',
        'orrin-compact' => 'Orrin Compact',
    ],
    'model' => 'orrin-fast',
];
