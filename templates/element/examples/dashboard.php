<?php
/**
 * Example: the shadcn "dashboard-01" page rebuilt with the Basecoat helpers.
 *
 * Render it from any template: <?= $this->element('BasecoatTheme.examples/dashboard') ?>
 * (the default layout already provides the sidebar, header and theme toggle).
 *
 * @var \Cake\View\View $this
 */
$this->element('BasecoatTheme.layout/helpers');

$stats = [
    ['Total Revenue', '$1,250.00', '+12.5%', 'trending-up', 'Trending up this month', 'Visitors for the last 6 months'],
    ['New Customers', '1,234', '-20%', 'trending-down', 'Down 20% this period', 'Acquisition needs attention'],
    ['Active Accounts', '45,678', '+12.5%', 'trending-up', 'Strong user retention', 'Engagement exceed targets'],
    ['Growth Rate', '4.5%', '+4.5%', 'trending-up', 'Steady performance increase', 'Meets growth projections'],
];

$visits = [];
$start = new DateTimeImmutable('2026-04-01');
for ($i = 0; $i < 90; $i++) {
    $visits[] = [
        'label' => $start->modify("+{$i} days")->format('M j'),
        'desktop' => 200 + (int)(150 * abs(sin($i / 6))) + ($i * 7) % 90,
        'mobile' => 120 + (int)(90 * abs(cos($i / 5))) + ($i * 11) % 60,
    ];
}

$rows = [
    ['Cover page', 'Cover page', 'In Process', 18, 5, 'Eddie Lake'],
    ['Table of contents', 'Table of contents', 'Done', 29, 24, 'Eddie Lake'],
    ['Executive summary', 'Narrative', 'Done', 10, 13, 'Eddie Lake'],
    ['Technical approach', 'Narrative', 'Done', 27, 23, 'Jamik Tashpulatov'],
    ['Design', 'Narrative', 'In Process', 2, 16, 'Jamik Tashpulatov'],
];
$rowMenu = fn(int $i): string => $this->DropdownMenu->render('', [
    'Edit', 'Make a copy', 'Favorite', '-',
    ['label' => 'Delete', 'variant' => 'destructive'],
], [
    'id' => 'row-menu-' . $i,
    'align' => 'end',
    'trigger' => ['variant' => 'ghost', 'size' => 'icon-sm', 'icon' => 'ellipsis-vertical', 'class' => 'btn text-muted-foreground', 'aria-label' => 'Actions'],
    'popoverOptions' => ['class' => 'min-w-32'],
]);
$tableRows = [];
foreach ($rows as $i => [$header, $type, $status, $target, $limit, $reviewer]) {
    $statusIcon = $status === 'Done'
        ? $this->Icon->render('circle-check', ['class' => 'size-3.5 fill-green-500 text-background dark:fill-green-400', 'replaceClass' => true])
        : $this->Icon->render('loader', ['class' => 'size-3.5 animate-spin', 'replaceClass' => true]);
    $tableRows[] = [
        'header' => $this->Button->render($header, ['variant' => 'link', 'class' => 'h-auto px-0 text-foreground']),
        'type' => $this->Badge->render($type, ['variant' => 'outline', 'class' => 'px-1.5 text-muted-foreground']),
        'status' => $this->Badge->render($status, ['variant' => 'outline', 'class' => 'px-1.5 text-muted-foreground', 'icon' => $statusIcon]),
        'target' => (string)$target,
        'limit' => (string)$limit,
        'reviewer' => h($reviewer),
        'actions' => $rowMenu($i + 1),
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
        $this->Chart->render($visits, [
            'mobile' => ['label' => 'Mobile', 'color' => 'var(--primary)', 'surface' => ['from' => 0.35, 'to' => 0.05], 'dataset' => ['tension' => 0.4, 'pointRadius' => 0, 'fill' => 'origin']],
            'desktop' => ['label' => 'Desktop', 'color' => 'var(--primary)', 'surface' => ['from' => 0.7, 'to' => 0.1], 'dataset' => ['tension' => 0.4, 'pointRadius' => 0, 'fill' => '-1']],
        ], [
            'type' => 'line',
            'class' => 'h-[250px] w-full',
            'canvasOptions' => ['aria-label' => 'Visitors per day'],
            'chartOptions' => ['scales' => ['y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['display' => false]], 'x' => ['grid' => ['display' => false], 'border' => ['display' => false], 'ticks' => ['autoSkip' => true, 'maxTicksLimit' => 12, 'maxRotation' => 0, 'color' => 'var(--muted-foreground)']]]],
        ]),
        [
            'class' => '@container/chart',
            'title' => 'Total Visitors',
            'description' => 'Total for the last 3 months',
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
        'tab' => 'Outline',
        'id' => 'outline',
        'panel' => '<div class="overflow-hidden rounded-lg border">' . $this->Table->render([
            'header' => 'Header',
            'type' => 'Section Type',
            'status' => 'Status',
            'target' => ['label' => 'Target', 'class' => 'text-right'],
            'limit' => ['label' => 'Limit', 'class' => 'text-right'],
            'reviewer' => 'Reviewer',
            'actions' => ['label' => '', 'class' => 'w-8'],
        ], $tableRows, ['escape' => false, 'headOptions' => ['class' => 'sticky top-0 z-10 bg-muted']]) . '</div>',
        'panelOptions' => ['class' => 'flex flex-col gap-4 px-4 lg:px-6'],
    ],
    ['tab' => 'Past Performance ' . $this->Badge->render('3', ['variant' => 'secondary', 'class' => 'size-5 rounded-full px-1']), 'id' => 'performance', 'escape' => false, 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
    ['tab' => 'Key Personnel ' . $this->Badge->render('2', ['variant' => 'secondary', 'class' => 'size-5 rounded-full px-1']), 'id' => 'personnel', 'escape' => false, 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
    ['tab' => 'Focus Documents', 'id' => 'focus', 'panel' => '<div class="aspect-video w-full flex-1 rounded-lg border border-dashed"></div>', 'panelOptions' => ['class' => 'flex flex-col px-4 lg:px-6']],
], ['id' => 'sections-tabs', 'class' => 'relative w-full gap-4', 'listOptions' => ['class' => 'mx-4 lg:mx-6']]) ?>
