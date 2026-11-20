<?php
/**
 * Example: a dashboard built with the Basecoat helpers (stat cards, area chart, tabs with a data table).
 *
 * The data belongs to "Fernway", the fictional analytics SaaS of the catalog: everything is invented.
 *
 * Render it from any template: <?= $this->element('BasecoatTheme.examples/dashboard') ?>
 * (the default layout already provides the sidebar, header and theme toggle).
 *
 * @var \Cake\View\View $this
 */
$this->element('BasecoatTheme.layout/helpers');

$stats = [
    ['Monthly revenue', '$48,920.00', '+8.2%', 'trending-up', 'Up 8.2% on October', 'Subscriptions and add-ons'],
    ['New customers', '214', '-6%', 'trending-down', 'Down 6% this month', 'Fewer trials started in November'],
    ['Active workspaces', '3,482', '+5.1%', 'trending-up', 'Up 5.1% this month', 'Workspaces with a login in 30 days'],
    ['Net retention', '112%', '+2.4%', 'trending-up', 'Up 2.4 points', 'Expansion outpaces churn'],
];

$sessions = [];
$start = new DateTimeImmutable('2026-04-01');
for ($i = 0; $i < 90; $i++) {
    $sessions[] = [
        'label' => $start->modify("+{$i} days")->format('M j'),
        'web' => 200 + (int)(150 * abs(sin($i / 6))) + ($i * 7) % 90,
        'mobile' => 120 + (int)(90 * abs(cos($i / 5))) + ($i * 11) % 60,
    ];
}

$rows = [
    ['Harbor & Pine', 'Growth', 'Live', 18, 25, 'Elena Sarto'],
    ['Quillon Studio', 'Starter', 'Onboarding', 4, 5, 'Kofi Mensah'],
    ['Ostrava Freight', 'Scale', 'Live', 64, 80, 'Ines Duarte'],
    ['Brightmoor Clinic', 'Growth', 'Onboarding', 12, 25, 'Pavel Novak'],
    ['Lantern Books', 'Starter', 'Live', 3, 5, 'Noor Haddad'],
];
$rowMenu = fn(int $i, string $customer): string => $this->DropdownMenu->render('', [
    'Open account', 'Message owner', 'Pin', '-',
    ['label' => 'Remove', 'variant' => 'destructive'],
], [
    'id' => 'row-menu-' . $i,
    'align' => 'end',
    'trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis', 'class' => 'btn text-muted-foreground', 'label' => 'Actions for ' . $customer],
    'popoverOptions' => ['class' => 'min-w-32'],
]);
$tableRows = [];
foreach ($rows as $i => [$customer, $plan, $status, $seats, $limit, $owner]) {
    $statusIcon = $status === 'Live'
        ? $this->Icon->render('circle-check', ['class' => 'size-3.5 fill-green-500 text-background dark:fill-green-400', 'replaceClass' => true])
        : $this->Icon->render('loader', ['class' => 'size-3.5 animate-spin', 'replaceClass' => true]);
    $tableRows[] = [
        'customer' => $this->Button->render($customer, ['variant' => 'link', 'class' => 'h-auto px-0 text-foreground']),
        'plan' => $this->Badge->render($plan, ['variant' => 'outline', 'class' => 'px-1.5 text-muted-foreground']),
        'status' => $this->Badge->render($status, ['variant' => 'outline', 'class' => 'px-1.5 text-muted-foreground', 'icon' => $statusIcon]),
        'seats' => (string)$seats,
        'limit' => (string)$limit,
        'owner' => h($owner),
        'actions' => $rowMenu($i + 1, $customer),
    ];
}
?>
<div class="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
    <?php foreach ($stats as [$label, $value, $delta, $icon, $trend, $note]) : ?>
        <?= $this->Card->render(null, [
            'class' => '@container/card bg-linear-to-t from-primary/5 to-card shadow-xs',
            'header' => '<p>' . h($label) . '</p><h2 class="!text-2xl !font-semibold tabular-nums @[250px]/card:!text-3xl">' . h($value) . '</h2>'
                . $this->Badge->render($delta, ['variant' => 'outline', 'icon' => $icon, 'class' => 'absolute right-6 top-0']),
            'headerOptions' => ['class' => 'relative'],
            'footer' => '<div class="flex items-center gap-2 font-medium">' . h($trend) . ' ' . $this->Icon->render($icon) . '</div>'
                . '<div class="text-muted-foreground">' . h($note) . '</div>',
            'footerOptions' => ['class' => 'flex flex-col items-start gap-1.5 text-sm'],
        ]) ?>
    <?php endforeach; ?>
</div>

<div class="px-4 lg:px-6">
    <?= $this->Card->render(
        $this->Chart->render($sessions, [
            'mobile' => ['label' => 'Mobile app', 'color' => 'var(--primary)', 'surface' => ['from' => 0.35, 'to' => 0.05], 'dataset' => ['tension' => 0.4, 'pointRadius' => 0, 'fill' => 'origin']],
            'web' => ['label' => 'Web app', 'color' => 'var(--primary)', 'surface' => ['from' => 0.7, 'to' => 0.1], 'dataset' => ['tension' => 0.4, 'pointRadius' => 0, 'fill' => '-1']],
        ], [
            'type' => 'line',
            'class' => 'h-[250px] w-full',
            'canvasOptions' => ['aria-label' => 'Dashboard sessions per day, web and mobile app'],
            'chartOptions' => ['scales' => ['y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['display' => false]], 'x' => ['grid' => ['display' => false], 'border' => ['display' => false], 'ticks' => ['autoSkip' => true, 'maxTicksLimit' => 12, 'maxRotation' => 0, 'color' => 'var(--muted-foreground)']]]],
        ]),
        [
            'class' => '@container/chart',
            'title' => 'Dashboard sessions',
            'description' => 'Sessions per day in Fernway, last 3 months',
            'action' => $this->ButtonGroup->render([
                ['text' => 'Last 3 months', 'variant' => 'secondary', 'aria-pressed' => 'true'],
                ['text' => 'Last 30 days', 'aria-pressed' => 'false'],
                ['text' => 'Last 7 days', 'aria-pressed' => 'false'],
            ], ['aria-label' => 'Range', 'button' => ['size' => 'sm'], 'class' => 'hidden @[767px]/chart:flex']),
            'bodyOptions' => ['class' => 'px-2 pt-4 sm:px-6 sm:pt-6'],
        ],
    ) ?>
</div>

<?= $this->Tabs->render([
    [
        'tab' => 'Accounts',
        'id' => 'accounts',
        'panel' => '<div class="overflow-hidden rounded-lg border">' . $this->Table->render([
            'customer' => 'Customer',
            'plan' => 'Plan',
            'status' => 'Status',
            'seats' => ['label' => 'Seats', 'class' => 'text-right'],
            'limit' => ['label' => 'Seat limit', 'class' => 'text-right'],
            'owner' => 'Account owner',
            'actions' => ['label' => '', 'class' => 'w-8'],
        ], $tableRows, ['escape' => false, 'headOptions' => ['class' => 'sticky top-0 z-10 bg-muted']]) . '</div>',
        'panelOptions' => ['class' => 'flex flex-col gap-4 px-4 lg:px-6'],
    ],
    ['tab' => 'Renewals ' . $this->Badge->render('3', ['variant' => 'secondary', 'class' => 'size-5 rounded-full px-1']), 'id' => 'renewals', 'escape' => false, 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
    ['tab' => 'Support tickets ' . $this->Badge->render('2', ['variant' => 'secondary', 'class' => 'size-5 rounded-full px-1']), 'id' => 'support', 'escape' => false, 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
    ['tab' => 'Usage reports', 'id' => 'reports', 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
], ['id' => 'sections-tabs', 'class' => 'relative w-full gap-4', 'listOptions' => ['class' => 'mx-4 lg:mx-6']]) ?>
