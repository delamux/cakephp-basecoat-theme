<?php
declare(strict_types=1);

/**
 * Sample data of the `users` screen: people of the fictional Fernway workspace.
 */
return [
    'users' => [
        ['id' => 1, 'name' => 'Elena Sarto', 'email' => 'elena.sarto@example.com', 'role' => 'Owner', 'plan' => 'Scale', 'location' => 'Lisbon, Portugal', 'status' => 'active'],
        ['id' => 2, 'name' => 'Kofi Mensah', 'email' => 'kofi.mensah@example.com', 'role' => 'Admin', 'plan' => 'Scale', 'location' => 'Accra, Ghana', 'status' => 'active'],
        ['id' => 3, 'name' => 'Ines Duarte', 'email' => 'ines.duarte@example.com', 'role' => 'Analyst', 'plan' => 'Growth', 'location' => 'Porto, Portugal', 'status' => 'active'],
        ['id' => 4, 'name' => 'Pavel Novak', 'email' => 'pavel.novak@example.com', 'role' => 'Support', 'plan' => 'Growth', 'location' => 'Ostrava, Czechia', 'status' => 'invited'],
        ['id' => 5, 'name' => 'Noor Haddad', 'email' => 'noor.haddad@example.com', 'role' => 'Billing', 'plan' => 'Starter', 'location' => 'Amman, Jordan', 'status' => 'active'],
        ['id' => 6, 'name' => 'Marit Solheim', 'email' => 'marit.solheim@example.com', 'role' => 'Analyst', 'plan' => 'Growth', 'location' => 'Bergen, Norway', 'status' => 'active'],
        ['id' => 7, 'name' => 'Tomas Ferreyra', 'email' => 'tomas.ferreyra@example.com', 'role' => 'Admin', 'plan' => 'Growth', 'location' => 'Rosario, Argentina', 'status' => 'suspended'],
        ['id' => 8, 'name' => 'Aiko Tanabe', 'email' => 'aiko.tanabe@example.com', 'role' => 'Viewer', 'plan' => 'Starter', 'location' => 'Kyoto, Japan', 'status' => 'invited'],
        ['id' => 9, 'name' => 'Hugo Lindqvist', 'email' => 'hugo.lindqvist@example.com', 'role' => 'Support', 'plan' => 'Scale', 'location' => 'Gothenburg, Sweden', 'status' => 'active'],
        ['id' => 10, 'name' => 'Selin Aydin', 'email' => 'selin.aydin@example.com', 'role' => 'Billing', 'plan' => 'Growth', 'location' => 'Izmir, Turkey', 'status' => 'active'],
        ['id' => 11, 'name' => 'Rafael Ocampo', 'email' => 'rafael.ocampo@example.com', 'role' => 'Analyst', 'plan' => 'Starter', 'location' => 'Cebu, Philippines', 'status' => 'suspended'],
        ['id' => 12, 'name' => 'Odile Marchetti', 'email' => 'odile.marchetti@example.com', 'role' => 'Viewer', 'plan' => 'Scale', 'location' => 'Lyon, France', 'status' => 'active'],
    ],
    'statuses' => [
        'active' => 'Active',
        'invited' => 'Invited',
        'suspended' => 'Suspended',
    ],
    'total' => 48,
    'page' => 1,
    'perPage' => 12,
];
