<?php
declare(strict_types=1);

/**
 * Sample data of the `onboarding` screen: setting up a workspace of the fictional Fernway product.
 *
 * View variables: `steps` (the five steps, in order, key => label), `workspace` (name and URL slug),
 * `invites` (emails already listed in the team step), `plans` (value => title, description, icon),
 * `plan` (selected plan), `timezones` and `digests` (select options), `preferences` (defaults of the
 * preferences step). The current step comes from the `step` query parameter, not from these data.
 */
return [
    'steps' => [
        'workspace' => 'Workspace',
        'team' => 'Team',
        'plan' => 'Plan',
        'preferences' => 'Preferences',
        'done' => 'Done',
    ],
    'workspace' => [
        'name' => 'Harbor & Pine',
        'slug' => 'harbor-pine',
    ],
    'invites' => [
        'elena.sarto@example.com',
        'kofi.mensah@example.com',
    ],
    'plans' => [
        'starter' => [
            'title' => 'Starter',
            'description' => 'Up to 5 people, 3 projects and community support. Free.',
            'icon' => 'rocket',
        ],
        'team' => [
            'title' => 'Team',
            'description' => 'Up to 25 people, unlimited projects and email support. 12 per seat a month.',
            'icon' => 'users',
        ],
        'scale' => [
            'title' => 'Scale',
            'description' => 'Unlimited people, audit log and priority support. 29 per seat a month.',
            'icon' => 'building-2',
        ],
    ],
    'plan' => 'team',
    'timezones' => [
        'Europe/Madrid' => 'Madrid (UTC+1)',
        'Europe/Prague' => 'Prague (UTC+1)',
        'Europe/London' => 'London (UTC+0)',
        'America/New_York' => 'New York (UTC-5)',
    ],
    'digests' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'never' => 'Never',
    ],
    'preferences' => [
        'timezone' => 'Europe/Madrid',
        'digest' => 'weekly',
        'mentions' => true,
        'product_updates' => false,
    ],
];
