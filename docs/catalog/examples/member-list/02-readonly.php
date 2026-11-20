<?php
/**
 * @title Read-only roles
 * @description Roles are displayed as badges when the viewer cannot change them.
 */
$members = [
    ['name' => 'Ana Ruiz', 'email' => 'ana.ruiz@example.com', 'role' => 'owner'],
    ['name' => 'Bruno Costa', 'email' => 'bruno.costa@example.com', 'role' => 'editor'],
    ['name' => 'Chloe Martin', 'email' => 'chloe.martin@example.com', 'role' => 'viewer'],
];
// ---
echo $this->MemberList->render($members, [
    'roles' => ['owner' => 'Owner', 'editor' => 'Editor', 'viewer' => 'Viewer'],
    'readonly' => true,
    'class' => 'max-w-xl',
]);
