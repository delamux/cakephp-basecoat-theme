<?php
/**
 * @title Plan picker
 * @description Three plans as selectable cards, with the middle one preselected.
 */
$plans = [
    'starter' => ['title' => 'Starter', 'description' => 'Up to 3 projects and 1 GB of storage.'],
    'team' => ['title' => 'Team', 'description' => 'Unlimited projects, shared workspaces and priority support.'],
    'enterprise' => ['title' => 'Enterprise', 'description' => 'Single sign-on, audit logs and a dedicated contact.'],
];
// ---
echo $this->ChoiceCards->render('plan', $plans, [
    'legend' => 'Choose a plan',
    'value' => 'team',
    'required' => true,
]);
