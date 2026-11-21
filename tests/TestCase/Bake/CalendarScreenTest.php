<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use BasecoatTheme\View\BasecoatHelpers;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Renders the baked `calendar` screen template with its sample data (or custom data).
 */
class CalendarScreenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
    }

    protected function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Bakes the template of the screen and renders it with `$vars` (default: the sample data).
     *
     * @param array<string, mixed>|null $vars View variables.
     * @param array<string, mixed> $query Query string of the request.
     */
    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('calendar', 'Calendar');
        $file = TMP . 'calendar_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Calendar/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/calendar',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Calendar', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $view = new View($request);
        foreach (BasecoatHelpers::MAP as $alias => $class) {
            if ($alias !== 'Form') {
                $view->loadHelper($alias, ['className' => $class]);
            }
        }
        $view->loadHelper('Form', ['className' => 'BasecoatTheme.Form']);
        $vars ??= ScreenRegistry::data('calendar');

        try {
            return (function () use ($file, $vars): string {
                extract($vars);
                ob_start();
                include $file;

                return (string)ob_get_clean();
            })->call($view);
        } finally {
            unlink($file);
        }
    }

    private function lint(string $file): int
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

        return $code;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('calendar');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('calendar');

        $this->assertSame(['year', 'month', 'today', 'categories', 'resources', 'days', 'dayStart', 'dayEnd', 'events'], array_keys($data));
        $this->assertSame([2026, 11, '2026-11-20'], [$data['year'], $data['month'], $data['today']]);
        $this->assertSame([[1, 2, 3, 4, 5], '08:00', '20:00'], [$data['days'], $data['dayStart'], $data['dayEnd']]);
        $this->assertCount(5, $data['resources']);
        foreach ($data['events'] as $event) {
            $this->assertSame(['id', 'title', 'date', 'time', 'end', 'category', 'resource'], array_keys($event));
            $this->assertTrue($event['resource'] === null || isset($data['resources'][$event['resource']]), 'Known resource');
            $this->assertSame($event['time'] === null, $event['end'] === null, 'Timed events have an end');
        }
        // The week of today (Mon 16 - Fri 20) has enough to show: an overlap, an all-day event and one before 08:00.
        $week = array_filter($data['events'], fn (array $e): bool => $e['date'] >= '2026-11-16' && $e['date'] <= '2026-11-20');
        $this->assertGreaterThanOrEqual(8, count($week));
        $this->assertContains(null, array_column($week, 'time'));
        $this->assertContains('07:30', array_column($week, 'time'));
        $this->assertGreaterThanOrEqual(12, count($data['events']));
        $this->assertEqualsCanonicalizing(array_keys($data['categories']), array_unique(array_column($data['events'], 'category')));
        $perDay = array_count_values(array_column($data['events'], 'date'));
        $this->assertGreaterThan(3, max($perDay), 'One day overflows the chips');
        $this->assertContains(null, array_column($data['events'], 'time'), 'An all-day event');
    }

    public function testTableSemanticsAndWeekdayHeaders(): void
    {
        $html = $this->render();

        $this->assertSame(1, substr_count($html, '<table'));
        $this->assertMatchesRegularExpression('/<caption class="sr-only">November 2026<\/caption>/', $html);
        $this->assertSame(7, substr_count($html, '<th scope="col"'));
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $this->assertStringContainsString('<span class="sr-only">' . $day . '</span>', $html);
        }
        $this->assertLessThan(strpos($html, 'Sunday'), strpos($html, 'Monday'), 'Monday first');
        $this->assertStringContainsString('<h3 class="ml-1 text-lg font-semibold">November 2026</h3>', $html);
        $this->assertStringNotContainsString('calendar-month', $html, 'No unused id');
    }

    public function testGridIsBuiltFromYearAndMonth(): void
    {
        $html = $this->render();

        // November 2026 starts on a Sunday and ends on a Monday: six weeks, 26 Oct to 6 Dec.
        $this->assertSame(42, substr_count($html, '<td '));
        $this->assertSame(6, substr_count($html, '<tr>') - 1);
        $this->assertStringContainsString('data-date="2026-10-26"', $html);
        $this->assertStringContainsString('data-date="2026-12-06"', $html);
        $this->assertStringNotContainsString('data-date="2026-10-25"', $html);
        $this->assertStringNotContainsString('data-date="2026-12-07"', $html);
        // Leading and trailing days are muted.
        $this->assertSame(12, substr_count($html, 'bg-muted/40'));

        // February 2027 starts on a Monday and has 28 days.
        $feb = $this->render($this->data(['year' => 2027, 'month' => 2, 'events' => []]));
        $this->assertSame(28, substr_count($feb, '<td '), 'February 2027 fits exactly four weeks');
        $this->assertStringContainsString('February 2027', $feb);
    }

    public function testTodayIsMarkedWithText(): void
    {
        $html = $this->render();

        $this->assertSame(1, substr_count($html, 'aria-current="date"'));
        // One sr-only text, no stray space before the comma.
        $this->assertStringContainsString('<span class="sr-only">20 November, Today</span>', $html);
        $this->assertMatchesRegularExpression('/<time datetime="2026-11-20"[^>]*aria-current="date"/', $html);

        $other = $this->render($this->data(['today' => '2027-01-05']));
        $this->assertStringNotContainsString('aria-current="date"', $other);
        $this->assertStringNotContainsString('Today</span>', $other);
    }

    public function testEventsAreChipsWithTimeTitleAndTextCategory(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<a href="#"[^>]*data-event="3"[^>]*data-category="meeting"[^>]*>\s*<span class="shrink-0 font-medium">14:00<\/span>\s*<span class="truncate">Kickoff with Quillon Studio<\/span>\s*<span class="sr-only">, Customer meeting<\/span>/', $html);
        // Chips are truncated at narrow widths: the full title is in a tooltip.
        $this->assertMatchesRegularExpression('/data-event="3"[^>]*title="14:00 Kickoff with Quillon Studio"/', $html);
        // All-day event: no time.
        $this->assertMatchesRegularExpression('/data-event="4"[^>]*>\s*<span class="truncate">Usage report due<\/span>\s*<span class="sr-only">, Deadline<\/span>/', $html);
        // Events outside the month appear on the leading/trailing days.
        $this->assertStringContainsString('data-event="1"', $html);
        $this->assertStringContainsString('data-event="16"', $html);
        // Chips of a day are sorted by time.
        $this->assertLessThan(strpos($html, 'data-event="9"'), strpos($html, 'data-event="8"'));
    }

    public function testOverflowShowsMoreLink(): void
    {
        $html = $this->render();

        // 24 Nov has four events: three chips and "+1 more".
        $this->assertStringContainsString('data-more="2026-11-24"', $html);
        $this->assertStringContainsString('<span aria-hidden="true">+1 more</span><span class="sr-only">+1 more event on 24 November</span>', $html);
        $two = $this->render($this->data(['events' => array_merge(ScreenRegistry::data('calendar')['events'], [
            ['id' => 90, 'date' => '2026-11-24', 'time' => '18:00', 'title' => 'Extra', 'category' => 'team'],
        ])]));
        $this->assertStringContainsString('<span class="sr-only">+2 more events on 24 November</span>', $two);
        $this->assertSame(1, substr_count($html, 'data-more='));
        // The fourth chip is on the page but hidden; "+N more" reveals it in place with JavaScript.
        $this->assertMatchesRegularExpression('/<li data-dnd-item data-id="13"[^>]*data-toggle-for="expanded" data-toggle-hidden="off" hidden>/', $html, 'The fourth chip is hidden');
        $this->assertMatchesRegularExpression('/<li data-toggle-for="expanded" data-toggle-hidden="on">\s*(<\?php.*?\?>)?\s*<a href="\/calendar\?view=day&amp;date=2026-11-24"[^>]*data-more="2026-11-24" data-toggle="expanded">/s', $html);
    }

    public function testDaysAreDropTargetsAndChipsAreDraggable(): void
    {
        $html = $this->render();

        // November 2026 (starts on a Sunday): six weeks, every day (also empty or outside the month) is a drop target.
        $this->assertSame(42, substr_count($html, 'data-dnd-container data-dnd-group="calendar-days"'));
        $this->assertMatchesRegularExpression('/<ul class="flex min-h-16 flex-col gap-0.5" data-dnd-container data-dnd-group="calendar-days" data-dnd-touch="press" data-id="2026-11-24" data-dnd-label="Tuesday 24 November" data-dnd-var-short="Tue 24 Nov" data-dnd-var-date="2026-11-24">/', $html);
        // The Upcoming entries mirror the chips: date text and order follow a move
        $this->assertMatchesRegularExpression('/<li data-dnd-item data-id="8"[^>]*data-dnd-mirror="\[data-upcoming=&quot;8&quot;\]"/', $html);
        $this->assertMatchesRegularExpression('/data-upcoming="\d+" data-dnd-sort="\{container-id\}\|[^"]*" data-dnd-sort-key="2026-11-\d\d\|/', $html);
        // ... and so does the machine-readable date of the entry (data-dnd-attr + data-dnd-var-date)
        $this->assertMatchesRegularExpression('/<time datetime="2026-11-\d\d" data-dnd-attr="datetime:\{date\}" data-dnd-text="short">/', $html);
        // The <li> moves, the chip link is its handle (so it stays a link and takes focus).
        $this->assertMatchesRegularExpression('/<li data-dnd-item data-id="8" data-dnd-label="09:00 Support rota handover"[^>]*>\s*<a href="#"[^>]*data-dnd-handle data-event="8"/', $html);
        $this->assertMatchesRegularExpression('/<li data-dnd-item data-id="4" data-dnd-label="Usage report due"[^>]*>/', $html);
        $this->assertStringContainsString('data-dnd-i18n-dropped="Dropped {label}. {container}, position {position} of {total}."', $html);
        $this->assertMatchesRegularExpression('/<td [^>]*data-date="2026-11-24" data-toggle-item data-id="2026-11-24">/', $html);

        $this->assertStringNotContainsString('data-endpoint', $html);
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/calendar/template.twig');
        $this->assertStringContainsString("TODO: add 'attributes' => ['data-endpoint' =>", $template);
        $this->assertStringContainsString("\$this->Html->script('BasecoatTheme.basecoat-dnd', ['block' => 'script', 'defer' => true]);", $template);
        $this->assertStringContainsString("\$this->Html->script('BasecoatTheme.basecoat-screens', ['block' => 'script', 'defer' => true]);", $template);
        $this->assertStringContainsString("\$this->Html->css('BasecoatTheme.interactivity', ['block' => 'css']);", $template);
    }

    public function testNavigationIsGetAndKeepsTheMonth(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('href="/calendar?view=month&amp;date=2026-10-01"', $html);
        $this->assertStringContainsString('href="/calendar?view=month&amp;date=2026-12-01"', $html);
        $this->assertStringContainsString('<span class="sr-only">Previous month</span>', $html);
        $this->assertStringContainsString('<span class="sr-only">Next month</span>', $html);
        $this->assertMatchesRegularExpression('/<a href="\/calendar\?view=month"[^>]*data-nav="today"[^>]*>Today<\/a>/', $html);

        $dec = $this->render($this->data(['month' => 12]));
        $this->assertStringContainsString('href="/calendar?view=month&amp;date=2026-11-01"', $dec);
        $this->assertStringContainsString('href="/calendar?view=month&amp;date=2027-01-01"', $dec);

        $old = $this->render(null, ['month' => '2026-12']);
        $this->assertStringContainsString('<caption class="sr-only">December 2026</caption>', $old, 'Old ?month= links still work');
        $byDate = $this->render(null, ['view' => 'month', 'date' => '2027-03-15']);
        $this->assertStringContainsString('<caption class="sr-only">March 2027</caption>', $byDate);
    }

    public function testViewSwitchLinks(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<nav class="flex items-center gap-1" aria-label="Calendar view">', $html);
        $this->assertMatchesRegularExpression('/<a href="\/calendar\?view=month&amp;date=2026-11-20"[^>]*aria-current="true"[^>]*data-view="month"[^>]*>Month<\/a>/', $html);
        $this->assertMatchesRegularExpression('/<a href="\/calendar\?view=week&amp;date=2026-11-20"[^>]*data-view="week"[^>]*>Week<\/a>/', $html);
        $this->assertMatchesRegularExpression('/<a href="\/calendar\?view=day&amp;date=2026-11-20"[^>]*data-view="day"[^>]*>Day<\/a>/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertStringContainsString('New event', $html);
    }

    public function testWeekView(): void
    {
        $html = $this->render(null, ['view' => 'week', 'date' => '2026-11-18']);

        $this->assertStringNotContainsString('<table', $html);
        $this->assertStringContainsString('data-calendar-view="week"', $html);
        $this->assertSame(5, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertStringContainsString('<h3 class="ml-1 text-lg font-semibold">16 Nov – 20 Nov 2026</h3>', $html);
        foreach (['16', '17', '18', '19', '20'] as $day) {
            $this->assertStringContainsString('href="/calendar?view=day&amp;date=2026-11-' . $day . '"', $html);
        }
        $this->assertStringContainsString('href="/calendar?view=week&amp;date=2026-11-11"', $html);
        $this->assertStringContainsString('href="/calendar?view=week&amp;date=2026-11-25"', $html);
        $this->assertStringContainsString('<span class="sr-only">Previous week</span>', $html);
        $this->assertMatchesRegularExpression('/<a href="\/calendar\?view=week"[^>]*data-nav="today"/', $html);
        $this->assertStringContainsString('bc-cal-cut-start', $html, 'The 07:30 standup is clipped at 08:00');
        $this->assertStringContainsString('<ul class="bc-cal-allday">', $html);
        $this->assertStringContainsString('<span class="bc-cal-meta">Kofi Mensah</span>', $html);
        $this->assertStringContainsString('Upcoming', $html, 'The side panel stays');
        $grid = substr($html, (int)strpos($html, '<section class="bc-cal'), (int)strpos($html, '<aside') - (int)strpos($html, '<section class="bc-cal'));
        $this->assertStringNotContainsString('data-dnd', $grid, 'Read-only');
    }

    public function testDayView(): void
    {
        $html = $this->render(null, ['view' => 'day', 'date' => '2026-11-18']);

        $this->assertStringContainsString('data-calendar-view="day"', $html);
        $this->assertSame(5, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertMatchesRegularExpression('/Elena Sarto.*Kofi Mensah.*Ines Duarte.*Pavel Novak.*Noor Haddad/s', $html);
        $this->assertStringContainsString('<h3 class="ml-1 text-lg font-semibold">Wednesday 18 November 2026</h3>', $html);
        $this->assertStringContainsString('href="/calendar?view=day&amp;date=2026-11-17"', $html);
        $this->assertStringContainsString('href="/calendar?view=day&amp;date=2026-11-19"', $html);
        $this->assertStringContainsString('bc-cal-lanes-1', $html);
        $this->assertStringNotContainsString('Unassigned', $html);

        $allHands = $this->render(null, ['view' => 'day', 'date' => '2026-11-19']);
        $this->assertStringContainsString('Unassigned', $allHands, 'The all-hands has no resource');
    }

    public function testInvalidQueryFallsBack(): void
    {
        $html = $this->render(null, ['view' => 'year', 'date' => 'nope', 'month' => '2026-13']);

        $this->assertStringContainsString('<caption class="sr-only">November 2026</caption>', $html);
        $this->assertMatchesRegularExpression('/aria-current="true"[^>]*data-view="month"/', $html);
    }

    public function testOldDataStillRenders(): void
    {
        $data = ScreenRegistry::data('calendar');
        unset($data['resources'], $data['days'], $data['dayStart'], $data['dayEnd']);
        $data['events'] = array_map(fn (array $e): array => array_diff_key($e, ['end' => true, 'resource' => true]), $data['events']);

        $month = $this->render($data);
        $this->assertStringContainsString('<caption class="sr-only">November 2026</caption>', $month);
        $week = $this->render($data, ['view' => 'week', 'date' => '2026-11-18']);
        $this->assertSame(5, substr_count($week, '<div class="bc-cal-col" role="group"'), 'Monday to Friday by default');
        $day = $this->render($data, ['view' => 'day', 'date' => '2026-11-18']);
        $this->assertSame(1, substr_count($day, '<div class="bc-cal-col" role="group"'), 'No resources: one column');
    }

    public function testSidePanelListsUpcomingAndLegend(): void
    {
        $html = $this->render();
        $aside = substr($html, (int)strpos($html, '<aside'));

        $this->assertStringContainsString('id="calendar-upcoming"', $aside);
        $this->assertSame(5, substr_count($aside, 'data-upcoming='));
        // From today onwards, in order, past events excluded.
        $this->assertStringContainsString('data-upcoming="8"', $aside);
        $this->assertStringNotContainsString('data-upcoming="7"', $aside);
        $this->assertLessThan(strpos($aside, 'data-upcoming="9"'), strpos($aside, 'data-upcoming="8"'));
        $this->assertStringContainsString('Fri 20 Nov', $aside);
        $this->assertStringContainsString('id="calendar-legend"', $aside);
        foreach (['Customer meeting', 'Deadline', 'Release', 'Team'] as $label) {
            $this->assertStringContainsString($label, $aside);
        }
        $this->assertSame(4, preg_match_all('/<span class="size-2\.5[^>]*aria-hidden="true"><\/span>/', $aside));
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['events' => [], 'categories' => []]));

        $this->assertStringContainsString('<table', $html);
        $this->assertSame(42, substr_count($html, '<td '));
        $this->assertStringNotContainsString('data-event=', $html);
        $this->assertStringContainsString('No upcoming events.', $html);
        $this->assertStringNotContainsString('id="calendar-legend"', $html);
    }

    public function testUnknownCategoryGetsANeutralChip(): void
    {
        $html = $this->render($this->data([
            'categories' => ['holiday' => 'Holiday'],
            'events' => [['id' => 1, 'title' => 'Bank holiday', 'date' => '2026-11-26', 'time' => null, 'category' => 'holiday']],
        ]));

        $this->assertStringContainsString('bg-muted text-foreground', $html);
        $this->assertStringContainsString(', Holiday</span>', $html);
    }

    public function testPageHeadingLevel(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<h2[^>]*>Calendar<\/h2>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<h1/', $html);
    }

    public function testGridScrollsInsideItsOwnContainer(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<div class="relative min-w-0 overflow-x-auto rounded-lg border">', $html);
        $this->assertStringContainsString('min-w-[38rem]', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'categories' => ['<b>cat</b>' => '<i>Label</i>'],
            'events' => [
                ['id' => 1, 'title' => '<script>alert(1)</script>', 'date' => '2026-11-20', 'time' => '09:00', 'category' => '<b>cat</b>', 'resource' => 'r'],
            ],
            'resources' => ['r' => '<u>Res</u>'],
        ]));
        $html .= $this->render($this->data([
            'categories' => ['<b>cat</b>' => '<i>Label</i>'],
            'events' => [
                ['id' => 1, 'title' => '<script>alert(1)</script>', 'date' => '2026-11-20', 'time' => '09:00', 'category' => '<b>cat</b>', 'resource' => 'r'],
            ],
            'resources' => ['r' => '<u>Res</u>'],
        ]), ['view' => 'week', 'date' => '2026-11-20']);

        foreach (['<script>alert', '<i>Label', '<b>cat', '<u>Res'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);
    }

    public function testUniqueIds(): void
    {
        $html = $this->render();

        preg_match_all('/(?<![\w-])id="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testNoLinksToMissingActionsAndNoForms(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        foreach ($matches[1] as $href) {
            $this->assertMatchesRegularExpression('/^(#|\/calendar\?view=(month|week|day)(&amp;date=\d{4}-\d{2}-\d{2})?)$/', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form', $html);
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString(' style=', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testDoesNotReadTheClock(): void
    {
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/calendar/template.twig');

        $this->assertDoesNotMatchRegularExpression('/\bdate\(|\bnew DateTime\(\s*\)|\'now\'|\bnow\(|time\(\)/', $template);
    }

    public function testStringsAreTranslatableAndNoThirdPartyNames(): void
    {
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/calendar/template.twig');
        $source = $template . (string)file_get_contents(ROOT . '/templates/bake/Screen/calendar/data.php');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
        $this->assertStringContainsString("{{ t|raw }}'New event')", $template);
        $this->assertStringContainsString("{{ t|raw }}'Today')", $template);

        $files = (new BakeScreenCommand())->renderFiles('calendar', 'Calendar', 'Blog');
        $this->assertStringContainsString("__d('Blog', 'New event')", $files['templates/Calendar/index.php']);
        $this->assertStringContainsString("__d('Blog', 'Next week')", $files['templates/Calendar/index.php']);
        $this->assertStringContainsString("__d('Blog', 'Previous day')", $files['templates/Calendar/index.php']);
    }
}
