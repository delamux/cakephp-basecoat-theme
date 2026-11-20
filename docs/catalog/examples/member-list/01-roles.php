<?php
/**
 * @title Members with editable roles
 * @description A team list where each member's role is changed through a select.
 */
$members = [
    ['id' => 1, 'name' => 'Ana Ruiz', 'email' => 'ana.ruiz@example.com', 'role' => 'owner'],
    ['id' => 2, 'name' => 'Bruno Costa', 'email' => 'bruno.costa@example.com', 'role' => 'editor'],
    ['id' => 3, 'name' => 'Chloe Martin', 'email' => 'chloe.martin@example.com', 'role' => 'viewer'],
];
// ---
echo $this->MemberList->render($members, [
    'roles' => ['owner' => 'Owner', 'editor' => 'Editor', 'viewer' => 'Viewer'],
    'class' => 'max-w-xl',
]);
