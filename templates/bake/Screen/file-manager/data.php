<?php
declare(strict_types=1);

/**
 * Sample data of the `file-manager` screen: the shared drive of the fictional Fernway workspace.
 *
 * View variables: `path` (current folder, "A/B"; empty for the root), `storage` (used / total in `unit`),
 * `types` (value => label, also the toolbar filter), `breakdown` (storage used per type, in `unit`),
 * `folders` (name, files, size) and `files` (id, name, type, owner, size, modified).
 */
return [
    'path' => '',
    'storage' => ['used' => 412, 'total' => 1024, 'unit' => 'GB'],
    'types' => [
        'document' => 'Documents',
        'image' => 'Images',
        'video' => 'Videos',
        'audio' => 'Audio',
        'archive' => 'Archives',
    ],
    'breakdown' => [
        ['type' => 'document', 'used' => 86],
        ['type' => 'image', 'used' => 124],
        ['type' => 'video', 'used' => 158],
        ['type' => 'audio', 'used' => 21],
        ['type' => 'archive', 'used' => 23],
    ],
    'folders' => [
        ['name' => 'Harbor & Pine', 'files' => 142, 'size' => '18.4 GB'],
        ['name' => 'Quillon Studio', 'files' => 87, 'size' => '42.9 GB'],
        ['name' => 'Ostrava Freight', 'files' => 215, 'size' => '9.6 GB'],
        ['name' => 'Brightmoor Clinic', 'files' => 64, 'size' => '3.2 GB'],
        ['name' => 'Lantern Books', 'files' => 301, 'size' => '27.1 GB'],
        ['name' => 'Brand assets', 'files' => 48, 'size' => '6.8 GB'],
    ],
    'files' => [
        ['id' => 1, 'name' => 'Q4 plan.docx', 'type' => 'document', 'owner' => 'Elena Sarto', 'size' => '2.4 MB', 'modified' => '12 min ago'],
        ['id' => 2, 'name' => 'Onboarding walkthrough.mp4', 'type' => 'video', 'owner' => 'Kofi Mensah', 'size' => '684 MB', 'modified' => '2 hours ago'],
        ['id' => 3, 'name' => 'Harbor & Pine logo.png', 'type' => 'image', 'owner' => 'Ines Duarte', 'size' => '418 KB', 'modified' => 'Today'],
        ['id' => 4, 'name' => 'Churn review.pdf', 'type' => 'document', 'owner' => 'Pavel Novak', 'size' => '1.1 MB', 'modified' => 'Yesterday'],
        ['id' => 5, 'name' => 'Support call, Ostrava Freight.mp3', 'type' => 'audio', 'owner' => 'Noor Haddad', 'size' => '38 MB', 'modified' => 'Yesterday'],
        ['id' => 6, 'name' => 'Lantern Books catalogue.zip', 'type' => 'archive', 'owner' => 'Mireia Vidal', 'size' => '212 MB', 'modified' => '2 days ago'],
        ['id' => 7, 'name' => 'Clinic floor plan.jpg', 'type' => 'image', 'owner' => 'Elena Sarto', 'size' => '3.8 MB', 'modified' => '3 days ago'],
        ['id' => 8, 'name' => 'Invoices November.xlsx', 'type' => 'document', 'owner' => 'Kofi Mensah', 'size' => '640 KB', 'modified' => '5 days ago'],
    ],
];
