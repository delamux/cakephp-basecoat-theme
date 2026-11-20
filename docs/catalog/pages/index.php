<?php
/**
 * Catalog home: demo dashboard of "Fernway", a fictional analytics SaaS.
 *
 * Every component on this page is rendered by the plugin's helpers. Company, people, amounts and
 * metrics are invented. Rendered with the theme's `default` layout (the generator sets `brand`
 * and `sidebarMenu`); root-relative URLs are made relative by the generator.
 *
 * @var \Cake\View\View $this
 */
$this->assign('title', 'Demo dashboard');

// Workspace switcher under the brand in the sidebar
$this->set('sidebarActions', $this->WorkspaceSwitcher->render([
    ['name' => 'Fernway', 'plan' => 'Growth plan', 'icon' => 'chart-column', 'url' => '#fernway'],
    ['name' => 'Fernway Labs', 'plan' => 'Free plan', 'icon' => 'database', 'url' => '#fernway-labs'],
], ['addUrl' => '#add-workspace', 'class' => 'mt-2 w-full']));

// Header: command menu (Ctrl/⌘ + K) and notifications
$this->start('header_actions');
echo $this->CommandMenu->trigger('demo-command', ['class' => 'hidden w-56 sm:flex']);
echo $this->Button->icon('bell', 'Notifications', ['variant' => 'ghost', 'indicator' => 3]);
$this->end();
$commandMenu = $this->CommandMenu->render([
    'Pages' => [
        ['label' => 'Overview', 'url' => '/index.html', 'icon' => 'house'],
        ['label' => 'All components', 'url' => '/components/index.html', 'icon' => 'layout-dashboard'],
        ['label' => 'Metric card', 'url' => '/components/metric-card.html', 'icon' => 'chart-column', 'keywords' => 'kpi stat'],
        ['label' => 'Table toolbar', 'url' => '/components/table-toolbar.html', 'icon' => 'list', 'keywords' => 'filter search'],
    ],
    'Actions' => [
        ['label' => 'Download report', 'url' => '#overview', 'icon' => 'download', 'shortcut' => 'D'],
        ['label' => 'Invite teammate', 'url' => '#team', 'icon' => 'user', 'keywords' => 'member people'],
    ],
], ['id' => 'demo-command']);

$traffic = [
    ['week' => 'Sep 7', 'new' => 1840, 'returning' => 1280],
    ['week' => 'Sep 14', 'new' => 2010, 'returning' => 1470],
    ['week' => 'Sep 21', 'new' => 1930, 'returning' => 1375],
    ['week' => 'Sep 28', 'new' => 2280, 'returning' => 1630],
    ['week' => 'Oct 5', 'new' => 2410, 'returning' => 1740],
    ['week' => 'Oct 12', 'new' => 2290, 'returning' => 1700],
    ['week' => 'Oct 19', 'new' => 2560, 'returning' => 1860],
    ['week' => 'Oct 26', 'new' => 2790, 'returning' => 1990],
    ['week' => 'Nov 2', 'new' => 2650, 'returning' => 1960],
    ['week' => 'Nov 9', 'new' => 2930, 'returning' => 2100],
    ['week' => 'Nov 16', 'new' => 3140, 'returning' => 2250],
];
$orders = [
    ['id' => 'FW-1048', 'customer' => 'Harbor & Pine', 'plan' => 'Growth', 'total' => '$1,280.00', 'status' => 'Paid'],
    ['id' => 'FW-1047', 'customer' => 'Quillon Studio', 'plan' => 'Starter', 'total' => '$96.00', 'status' => 'Pending'],
    ['id' => 'FW-1046', 'customer' => 'Ostrava Freight', 'plan' => 'Scale', 'total' => '$4,560.00', 'status' => 'Paid'],
    ['id' => 'FW-1045', 'customer' => 'Brightmoor Clinic', 'plan' => 'Growth', 'total' => '$1,280.00', 'status' => 'Refunded'],
    ['id' => 'FW-1044', 'customer' => 'Lantern Books', 'plan' => 'Starter', 'total' => '$96.00', 'status' => 'Failed'],
];
$statusVariants = ['Paid' => 'primary', 'Pending' => 'secondary', 'Refunded' => 'outline', 'Failed' => 'destructive'];
$rows = [];
foreach ($orders as $order) {
    $rows[] = [
        'id' => $order['id'],
        'customer' => $order['customer'],
        'plan' => $order['plan'],
        'status' => ['content' => $this->Badge->render($order['status'], ['variant' => $statusVariants[$order['status']]]), 'escape' => false],
        'total' => $order['total'],
        'actions' => ['escape' => false, 'content' => $this->DropdownMenu->render('', [
            ['label' => 'View order', 'url' => '#orders'],
            ['label' => 'Send receipt', 'url' => '#orders'],
            '-',
            ['label' => 'Refund', 'variant' => 'destructive'],
        ], ['trigger' => ['icon' => 'ellipsis-vertical', 'variant' => 'ghost', 'size' => 'icon-sm', 'aria-label' => 'Actions for ' . $order['id']], 'align' => 'end'])],
    ];
}
?>
<div id="overview" class="flex flex-col gap-4 px-4 md:gap-6 lg:px-6">
    <?= $this->PageHeader->render('Overview', [
        'level' => 2,
        'description' => 'How Fernway is doing this month: usage, revenue, orders and support.',
        'actions' => [
            $this->DateRangeField->render('period', [
                'id' => 'overview-period',
                'label' => 'Period',
                'from' => '2026-11-01',
                'to' => '2026-11-30',
                'presets' => [
                    'Last 7 days' => ['2026-11-13', '2026-11-20'],
                    'This month' => ['2026-11-01', '2026-11-30'],
                    'Last quarter' => ['2026-07-01', '2026-09-30'],
                ],
            ]),
            $this->Button->render('Download report', ['icon' => 'download']),
        ],
    ]) ?>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?= $this->MetricCard->render('Active users', '12,486', [
            'change' => '+8.2%', 'trend' => 'up', 'description' => 'vs. October', 'icon' => 'users',
        ]) ?>
        <?= $this->MetricCard->render('Monthly revenue', '$48,920', [
            'change' => '+12.5%', 'trend' => 'up', 'description' => 'vs. October', 'icon' => 'credit-card',
        ]) ?>
        <?= $this->MetricCard->render('Churn rate', '2.4%', [
            'change' => '-0.6 pts', 'trend' => 'down', 'description' => 'Lower is better', 'icon' => 'trending-down',
        ]) ?>
        <?= $this->MetricCard->render('NPS', '61', [
            'change' => '±0 pts', 'trend' => 'flat', 'description' => 'vs. October · 318 responses', 'icon' => 'circle-check',
        ]) ?>
    </div>

    <?= $this->Card->render($this->Chart->render($traffic, [
        'new' => ['label' => 'New visitors', 'color' => 'var(--chart-1)'],
        'returning' => ['label' => 'Returning visitors', 'color' => 'var(--chart-2)'],
    ], [
        'id' => 'overview-traffic',
        'type' => 'line',
        'labelKey' => 'week',
        'legend' => true,
        'canvasOptions' => ['aria-label' => 'Weekly new and returning visitors'],
        'class' => 'h-[260px] w-full',
    ]), [
        'title' => 'Traffic',
        'description' => 'New and returning visitors per week, last 11 weeks',
        'action' => $this->Badge->render('+73% since September', ['variant' => 'outline']),
    ]) ?>

    <section id="orders" class="flex flex-col gap-4" aria-labelledby="orders-title">
        <h2 id="orders-title" class="text-lg font-semibold">Recent orders</h2>
        <?= $this->TableToolbar->render([
            'url' => '#orders',
            'filter' => [
                'q' => ['type' => 'search', 'label' => 'Search orders'],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => [
                    'paid' => 'Paid', 'pending' => 'Pending', 'refunded' => 'Refunded', 'failed' => 'Failed',
                ]],
            ],
            'actions' => $this->Button->render('New order', ['icon' => 'plus', 'variant' => 'outline']),
        ]) ?>
        <?= $this->Table->render([
            'id' => 'Order',
            'customer' => 'Customer',
            'plan' => 'Plan',
            'status' => 'Status',
            'total' => ['label' => 'Total', 'class' => 'text-right'],
            'actions' => ['label' => '', 'class' => 'w-10'],
        ], $rows) ?>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <?= $this->Card->render($this->Conversation->render([
            ['text' => 'Hi! Our weekly export to the data warehouse stopped on Monday.', 'from' => 'them', 'author' => 'Noor Haddad', 'time' => '09:12'],
            ['text' => 'Thanks for the heads-up, Noor. The connector token expired; I have renewed it.', 'from' => 'me', 'time' => '09:20'],
            ['text' => 'Great, the backlog is syncing now. Thank you!', 'from' => 'them', 'author' => 'Noor Haddad', 'time' => '09:31'],
        ], [
            'contact' => ['name' => 'Noor Haddad', 'subtitle' => 'Harbor & Pine · noor@example.com'],
            'composer' => ['placeholder' => 'Reply to Noor…', 'url' => '#support'],
            'class' => 'border-0 shadow-none',
        ]), [
            'id' => 'support',
            'title' => 'Support',
            'description' => 'Latest open conversation',
        ]) ?>

        <?= $this->Card->render($this->MemberList->render([
            ['id' => 1, 'name' => 'Elena Sarto', 'email' => 'elena@example.com', 'role' => 'owner'],
            ['id' => 2, 'name' => 'Kofi Mensah', 'email' => 'kofi@example.com', 'role' => 'admin'],
            ['id' => 3, 'name' => 'Ines Duarte', 'email' => 'ines@example.com', 'role' => 'member'],
            ['id' => 4, 'name' => 'Pavel Novak', 'email' => 'pavel@example.com', 'role' => 'member'],
        ], [
            'roles' => ['owner' => 'Owner', 'admin' => 'Admin', 'member' => 'Member'],
        ]), [
            'id' => 'team',
            'title' => 'Team',
            'description' => 'Who can access the Fernway workspace',
            'action' => $this->Button->render('Invite', ['icon' => 'plus', 'size' => 'sm', 'variant' => 'outline']),
        ]) ?>
    </div>

    <?= $this->Card->render($this->ChoiceCards->render('plan', [
        'starter' => ['title' => 'Starter', 'description' => '$96 / month. 3 dashboards, 10k events.', 'icon' => 'circle'],
        'growth' => ['title' => 'Growth', 'description' => '$320 / month. Unlimited dashboards, 1M events.', 'icon' => 'trending-up'],
        'scale' => ['title' => 'Scale', 'description' => '$760 / month. SSO, audit log, 10M events.', 'icon' => 'database'],
    ], ['legend' => 'Plan', 'value' => 'growth', 'id' => 'billing-plan']), [
        'title' => 'Billing',
        'description' => 'Your workspace is on the Growth plan, renewing on December 1.',
        'footer' => $this->Button->render('Update plan'),
    ]) ?>

    <div class="flex flex-wrap items-center gap-2 border-t pt-6">
        <p class="mr-auto text-sm text-muted-foreground">
            Everything above is rendered by the plugin's View Helpers. All names and figures are invented.
        </p>
        <?= $this->Button->link('View source', 'https://github.com/delamux/cakephp-basecoat-theme/blob/main/docs/catalog/pages/index.php', ['variant' => 'outline']) ?>
        <?= $this->Button->link('Browse components', '/components/index.html', ['icon' => 'arrow-right', 'iconPosition' => 'end']) ?>
    </div>
</div>
<?= $commandMenu ?>
