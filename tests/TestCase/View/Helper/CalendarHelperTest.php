<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\CalendarHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class CalendarHelperTest extends HelperTestCase
{
    private function helper(): CalendarHelper
    {
        return new CalendarHelper($this->View);
    }

    public function testWeekDatesFollowDaysOrder(): void
    {
        $h = $this->helper();
        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20'], $h->weekDates('2026-11-18', [1, 2, 3, 4, 5]));
        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20'], $h->weekDates('2026-11-21', [1, 2, 3, 4, 5]), 'Saturday outside days: still that week');
        $this->assertSame('2026-11-15', $h->weekDates('2026-11-21', [0, 1, 2, 3, 4, 5, 6])[0]);
        $this->assertSame('2026-11-22', $h->weekDates('2026-11-22', [0, 1, 2, 3, 4, 5, 6])[0]);
        $this->assertSame(['2026-11-13', '2026-11-16'], $h->weekDates('2026-11-18', [5, 1]), 'The week starts on days[0] (Friday 13), columns in days order');
        $this->assertSame(['2026-11-21', '2026-11-22'], $h->weekDates('2026-11-22', [6, 0]), 'A week starting on Saturday');
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('badOptions')]
    public function testBadOptionsThrow(array $options, string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($field, '/') . '/');
        $this->helper()->week([], $options + ['date' => '2026-11-18']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function badOptions(): array
    {
        return [
            'days empty' => [['days' => []], 'days'],
            'days out of range' => [['days' => [1, 7]], 'days'],
            'days duplicate' => [['days' => [1, 1]], 'days'],
            'days not integers' => [['days' => ['1']], 'days'],
            'slot' => [['slot' => 20], 'slot'],
            'start off grid' => [['start' => '08:10'], 'start'],
            'start malformed' => [['start' => '8:00'], 'start'],
            'end before start' => [['start' => '10:00', 'end' => '09:00'], 'end'],
            'end 25:00' => [['end' => '25:00'], 'end'],
            'bad date' => [['date' => '2026-02-30'], 'date'],
            'missing date' => [['date' => null], 'date'],
            'bad today' => [['today' => 'today'], 'today'],
            'column width' => [['columnWidth' => 'xl'], 'columnWidth'],
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    #[DataProvider('badEvents')]
    public function testBadEventsThrow(array $event, string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($field, '/') . '/');
        $this->helper()->week([$event + ['id' => 1, 'title' => 'Demo', 'date' => '2026-11-18', 'time' => '09:00']], ['date' => '2026-11-18']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function badEvents(): array
    {
        return [
            'date' => [['date' => '2026-13-01'], 'date'],
            'time' => [['time' => '9:00'], 'time'],
            'end before time' => [['end' => '08:00'], 'end'],
            'end equal to time' => [['end' => '09:00'], 'end'],
            'end malformed' => [['end' => '10'], 'end'],
            'title' => [['title' => null], 'title'],
            'id' => [['id' => null], 'id'],
        ];
    }

    public function testValidInputDoesNotThrow(): void
    {
        $html = $this->helper()->week([
            ['id' => 1, 'title' => 'Demo', 'date' => '2026-11-18', 'time' => '09:00'],
            ['id' => 2, 'title' => 'Late', 'date' => '2026-11-18', 'time' => '23:00', 'end' => '24:00'],
            ['id' => 3, 'title' => 'Holiday', 'date' => '2026-11-19', 'time' => null],
        ], ['date' => '2026-11-18', 'start' => '00:00', 'end' => '24:00', 'slot' => 15, 'days' => [0, 6]]);
        $this->assertIsString($html);
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function ev(int $id, string $date, ?string $time, ?string $end = null, array $override = []): array
    {
        return $override + ['id' => $id, 'title' => 'Event ' . $id, 'date' => $date, 'time' => $time, 'end' => $end];
    }

    /**
     * The `<li>` of an event placed in the body (its class attribute), or null.
     */
    private function placed(string $html, string $title): ?string
    {
        return preg_match('#<li class="(bc-cal-event bc-cal-row-[^"]*)"><a[^>]*>(?:(?!</li>).)*' . preg_quote($title, '#') . '#s', $html, $m) ? $m[1] : null;
    }

    public function testWeekStructureAndAssets(): void
    {
        $html = $this->helper()->week([], ['date' => '2026-11-18', 'days' => [1, 2, 3, 4, 5]]);

        $this->assertStringStartsWith('<section class="bc-cal bc-cal-week bc-cal-w-md" aria-label="Week of Monday 16 November 2026" data-calendar-view="week" data-calendar-date="2026-11-18">', $html);
        $this->assertStringContainsString('<div class="bc-cal-scroll" role="region" tabindex="0" aria-label="Week of Monday 16 November 2026">', $html);
        $this->assertStringContainsString('<div class="bc-cal-times" aria-hidden="true">', $html);
        $this->assertSame(5, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertStringContainsString('<span class="bc-cal-hour bc-cal-row-1">08:00</span>', $html);
        $this->assertStringContainsString('<span class="bc-cal-hour bc-cal-row-23">19:00</span>', $html);
        $this->assertStringContainsString('<ol class="bc-cal-body bc-cal-rows-24 bc-cal-slot-30">', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('data-dnd', $html, 'Week is read-only');
        $this->assertStringContainsString('basecoat-calendar.css', (string)$this->View->fetch('css'));
    }

    public function testAssetsCanBeSkipped(): void
    {
        $this->helper()->week([], ['date' => '2026-11-18', 'assets' => false]);
        $this->assertStringNotContainsString('basecoat-calendar.css', (string)$this->View->fetch('css'));
    }

    public function testColumnWidthLabelAndClass(): void
    {
        $html = $this->helper()->week([], ['date' => '2026-11-18', 'columnWidth' => 'sm', 'label' => 'Team week', 'class' => 'mt-4', 'data-x' => 'y']);
        $this->assertStringStartsWith('<section class="bc-cal bc-cal-week bc-cal-w-sm mt-4" aria-label="Team week" data-calendar-view="week" data-calendar-date="2026-11-18" data-x="y">', $html);
    }

    public function testEventPlacement(): void
    {
        $html = $this->helper()->week([
            $this->ev(1, '2026-11-18', '09:00', '10:30'),
            $this->ev(2, '2026-11-17', '09:10', '09:40'),
            $this->ev(3, '2026-11-16', '10:00'),
        ], ['date' => '2026-11-18', 'days' => [1, 2, 3, 4, 5]]);

        $this->assertSame('bc-cal-event bc-cal-row-3 bc-cal-span-3 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none', $this->placed($html, 'Event 1'));
        $this->assertSame('bc-cal-event bc-cal-row-3 bc-cal-span-2 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none', $this->placed($html, 'Event 2'));
        $this->assertStringContainsString('<span class="bc-cal-time">09:10–09:40</span>', $html, 'Real times are shown');
        $this->assertSame('bc-cal-event bc-cal-row-5 bc-cal-span-1 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none', $this->placed($html, 'Event 3'), 'No end: one slot');
        // Columns hold their own day's events
        $this->assertMatchesRegularExpression('#id="[^"]*2026-11-16[^"]*".*?Event 3.*?id="[^"]*2026-11-17#s', $html);
    }

    public function testClippingMarksCut(): void
    {
        $html = $this->helper()->week([
            $this->ev(1, '2026-11-18', '07:30', '09:00'),
            $this->ev(2, '2026-11-18', '19:30', '21:00'),
        ], ['date' => '2026-11-18']);

        $this->assertSame('bc-cal-event bc-cal-row-1 bc-cal-span-2 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none bc-cal-cut-start', $this->placed($html, 'Event 1'));
        $this->assertSame('bc-cal-event bc-cal-row-24 bc-cal-span-1 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none bc-cal-cut-end', $this->placed($html, 'Event 2'));
        $this->assertStringContainsString('<span class="bc-cal-time">07:30–09:00</span>', $html);
    }

    public function testFullDayWith24(): void
    {
        $html = $this->helper()->week([$this->ev(1, '2026-11-18', '23:00', '24:00')], ['date' => '2026-11-18', 'start' => '00:00', 'end' => '24:00', 'slot' => 60]);

        $this->assertStringContainsString('bc-cal-rows-24', $html);
        $this->assertSame('bc-cal-event bc-cal-row-24 bc-cal-span-1 bc-cal-lanes-1 bc-cal-lane-1 bc-cal-c-none', $this->placed($html, 'Event 1'));
        $this->assertStringContainsString('<span class="bc-cal-time">23:00–24:00</span>', $html);
    }

    public function testOutsideHoursAndOtherWeeksAreNotPlaced(): void
    {
        $html = $this->helper()->week([
            $this->ev(1, '2026-11-18', '06:00', '07:00'),
            $this->ev(2, '2026-11-18', '20:00', '21:00'),
            $this->ev(3, '2026-11-25', '10:00', '11:00'),
            $this->ev(4, '2026-11-21', '10:00', '11:00'),
        ], ['date' => '2026-11-18', 'days' => [1, 2, 3, 4, 5]]);

        $this->assertNull($this->placed($html, 'Event 1'));
        $this->assertStringContainsString('<details class="bc-cal-outside"><summary>2 events outside hours</summary>', $html);
        $this->assertStringContainsString('Event 1', $html, 'Still reachable in the details');
        $this->assertStringNotContainsString('Event 3', $html, 'Other week');
        $this->assertStringNotContainsString('Event 4', $html, 'Saturday is not shown');
    }

    public function testLanes(): void
    {
        $three = $this->helper()->week([
            $this->ev(1, '2026-11-18', '09:00', '11:00'),
            $this->ev(2, '2026-11-18', '09:30', '10:00'),
            $this->ev(3, '2026-11-18', '10:00', '12:00'),
            $this->ev(4, '2026-11-18', '13:00', '14:00'),
        ], ['date' => '2026-11-18']);
        $this->assertStringContainsString('bc-cal-lanes-2 bc-cal-lane-1', (string)$this->placed($three, 'Event 1'));
        $this->assertStringContainsString('bc-cal-lanes-2 bc-cal-lane-2', (string)$this->placed($three, 'Event 2'));
        $this->assertStringContainsString('bc-cal-lanes-2 bc-cal-lane-2', (string)$this->placed($three, 'Event 3'), 'Lane 2 is free again at 10:00');
        $this->assertStringContainsString('bc-cal-lanes-1 bc-cal-lane-1', (string)$this->placed($three, 'Event 4'), 'Separate cluster');

        $events = [];
        for ($i = 1; $i <= 6; $i++) {
            $events[] = $this->ev($i, '2026-11-18', '09:00', '10:00');
        }
        $six = $this->helper()->week($events, ['date' => '2026-11-18']);
        $this->assertSame(4, preg_match_all('#<li class="bc-cal-event bc-cal-row-#', $six));
        $this->assertStringContainsString('bc-cal-lanes-4 bc-cal-lane-4', (string)$this->placed($six, 'Event 4'));
        $this->assertStringContainsString('<details class="bc-cal-more"><summary>+2 more</summary>', $six);
        $this->assertNull($this->placed($six, 'Event 6'));
        $this->assertStringContainsString('Event 6', $six);
    }

    public function testAllDayRow(): void
    {
        $html = $this->helper()->week([$this->ev(1, '2026-11-18', null)], ['date' => '2026-11-18', 'days' => [1, 2, 3]]);
        $this->assertSame(3, substr_count($html, '<ul class="bc-cal-allday">'), 'One per column');
        $this->assertMatchesRegularExpression('#<ul class="bc-cal-allday"><li class="bc-cal-event bc-cal-c-none"><a href="\#"><span class="bc-cal-time">All day</span>#', $html);

        $none = $this->helper()->week([$this->ev(1, '2026-11-18', '09:00')], ['date' => '2026-11-18']);
        $this->assertStringNotContainsString('bc-cal-allday', $none);
    }

    public function testEventTextAndCategoryClass(): void
    {
        $html = $this->helper()->week([
            $this->ev(1, '2026-11-18', '09:00', '10:00', ['title' => 'Kickoff', 'resource' => 'kofi', 'category' => 'meeting', 'url' => '/events/1']),
            $this->ev(2, '2026-11-18', '11:00', '12:00', ['category' => 'party']),
        ], ['date' => '2026-11-18', 'resources' => ['kofi' => 'Kofi Mensah'], 'categories' => ['meeting' => 'Customer meeting', 'party' => 'Party']]);

        $this->assertStringContainsString('<a href="/events/1"><span class="bc-cal-time">09:00–10:00</span> <span class="bc-cal-title">Kickoff</span> <span class="bc-cal-meta">Kofi Mensah</span><span class="sr-only">, Wednesday 18 November, Customer meeting</span></a>', $html);
        $this->assertStringContainsString('bc-cal-c-meeting', (string)$this->placed($html, 'Kickoff'));
        $this->assertStringContainsString('bc-cal-c-none', (string)$this->placed($html, 'Event 2'), 'Unknown colour key: neutral');
    }

    public function testTodayAndDayUrl(): void
    {
        $html = $this->helper()->week([], [
            'date' => '2026-11-18',
            'today' => '2026-11-18',
            'days' => [1, 2, 3],
            'dayUrl' => fn (string $d): array => ['controller' => 'Calendar', 'action' => 'index', '?' => ['view' => 'day', 'date' => $d]],
        ]);

        $this->assertSame(1, substr_count($html, 'aria-current="date"'));
        $this->assertMatchesRegularExpression('#<div class="bc-cal-head bc-cal-today" id="[^"]+" aria-current="date"><a href="/calendar\?view=day&amp;date=2026-11-18"><span aria-hidden="true">Wed 18</span><span class="sr-only">Wednesday 18 November</span></a>#', $html);
        $this->assertStringContainsString('href="/calendar?view=day&amp;date=2026-11-16"', $html);

        $plain = $this->helper()->week([], ['date' => '2026-11-18', 'days' => [1]]);
        $this->assertMatchesRegularExpression('#<div class="bc-cal-head" id="[^"]+"><span aria-hidden="true">Mon 16</span><span class="sr-only">Monday 16 November</span>#', $plain);
    }

    public function testIdsAreUniqueAcrossCalls(): void
    {
        $h = $this->helper();
        $html = $h->week([], ['date' => '2026-11-18']) . $h->week([], ['date' => '2026-11-18']);
        preg_match_all('/ id="([^"]+)"/', $html, $m);
        $this->assertSame(count($m[1]), count(array_unique($m[1])));
    }

    public function testEscaping(): void
    {
        $html = $this->helper()->week([
            $this->ev(1, '2026-11-18', '09:00', '10:00', ['title' => '<b>&', 'resource' => 'r', 'url' => '/a?b=1&c="x"']),
        ], ['date' => '2026-11-18', 'resources' => ['r' => '<i>R&D</i>'], 'label' => '"Week"']);

        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString('<i>', $html);
        $this->assertStringContainsString('&lt;b&gt;&amp;', $html);
        $this->assertStringContainsString('&lt;i&gt;R&amp;D&lt;/i&gt;', $html);
        $this->assertStringContainsString('href="/a?b=1&amp;c=&quot;x&quot;"', $html);
        $this->assertStringContainsString('aria-label="&quot;Week&quot;"', $html);
    }

    public function testStylesheetCoversAllRowsAndSpans(): void
    {
        $css = (string)file_get_contents(dirname(__DIR__, 4) . '/webroot/css/basecoat-calendar.css');
        foreach (['.bc-cal-row-1{', '.bc-cal-row-96{', '.bc-cal-span-96{', '.bc-cal-rows-96{', '.bc-cal-lanes-4.bc-cal-lane-4{', '.bc-cal-w-lg{', '.bc-cal-c-team{', '.bc-cal-cut-start{'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
        $this->assertStringNotContainsString('.bc-cal-row-97', $css);
        // Columns have a definite width (otherwise no-wrap event text widens them under width:max-content)
        $this->assertMatchesRegularExpression('/\.bc-cal-col\{[^}]*;width:var\(--bc-cal-col\)/', $css);
        // sr-only text (absolute) stays inside the scroll box: events and the box are positioned
        $this->assertMatchesRegularExpression('/\.bc-cal-event\{position:relative;/', $css);
        $this->assertMatchesRegularExpression('/\.bc-cal-scroll\{position:relative;/', $css);
    }

    /**
     * Column (by header text) that contains a title, or null.
     */
    private function columnOf(string $html, string $title): ?string
    {
        foreach (explode('<div class="bc-cal-col"', $html) as $column) {
            if (str_contains($column, $title) && preg_match('#<div class="bc-cal-head[^"]*" id="[^"]+"[^>]*>(.*?)</div>#s', $column, $m)) {
                return strip_tags($m[1]);
            }
        }

        return null;
    }

    public function testDayColumnsAreResources(): void
    {
        $html = $this->helper()->day([
            $this->ev(1, '2026-11-18', '09:00', '10:00', ['resource' => 'kofi', 'category' => 'meeting']),
            $this->ev(2, '2026-11-18', '11:00', '12:00', ['resource' => 'ana']),
        ], [
            'date' => '2026-11-18',
            'resources' => ['ana' => 'Ana Costa', 'kofi' => 'Kofi Mensah', 'noor' => 'Noor Haddad'],
            'categories' => ['meeting' => 'Customer meeting'],
        ]);

        $this->assertStringStartsWith('<section class="bc-cal bc-cal-day bc-cal-w-md" aria-label="Schedule for Wednesday 18 November 2026" data-calendar-view="day" data-calendar-date="2026-11-18">', $html);
        $this->assertSame(3, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertMatchesRegularExpression('#Ana Costa.*Kofi Mensah.*Noor Haddad#s', $html);
        $this->assertSame('Kofi Mensah', $this->columnOf($html, 'Event 1'));
        $this->assertSame('Ana Costa', $this->columnOf($html, 'Event 2'));
        $this->assertStringNotContainsString('<a href="/', strstr($html, '<ol', true) ?: '', 'Resource headers are not links');
        $this->assertStringNotContainsString('data-dnd', $html, 'Day is read-only');
    }

    public function testUnassignedOnlyWhenNeeded(): void
    {
        $options = ['date' => '2026-11-18', 'resources' => ['ana' => 'Ana Costa', 'kofi' => 'Kofi Mensah']];
        $known = $this->helper()->day([$this->ev(1, '2026-11-18', '09:00', null, ['resource' => 'ana'])], $options);
        $this->assertStringNotContainsString('Unassigned', $known);
        $this->assertSame(2, substr_count($known, '<div class="bc-cal-col" role="group"'));

        $html = $this->helper()->day([
            $this->ev(1, '2026-11-18', '09:00', null, ['resource' => 'ghost']),
            $this->ev(2, '2026-11-18', '10:00'),
        ], $options);
        $this->assertSame(3, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertMatchesRegularExpression('#Kofi Mensah.*Unassigned#s', $html, 'Last column');
        $this->assertSame('Unassigned', $this->columnOf($html, 'Event 1'));
        $this->assertSame('Unassigned', $this->columnOf($html, 'Event 2'));
    }

    public function testDayWithoutResources(): void
    {
        $html = $this->helper()->day([$this->ev(1, '2026-11-18', '09:00')], ['date' => '2026-11-18', 'today' => '2026-11-18']);
        $this->assertSame(1, substr_count($html, '<div class="bc-cal-col" role="group"'));
        $this->assertSame('Wednesday 18 November', $this->columnOf($html, 'Event 1'));
        $this->assertSame(1, substr_count($html, 'aria-current="date"'));
    }

    public function testDayShowsOnlyThatDate(): void
    {
        $html = $this->helper()->day([
            $this->ev(1, '2026-11-18', '09:00'),
            $this->ev(2, '2026-11-19', '09:00'),
            $this->ev(3, '2026-11-17', null),
        ], ['date' => '2026-11-18']);
        $this->assertStringContainsString('Event 1', $html);
        $this->assertStringNotContainsString('Event 2', $html);
        $this->assertStringNotContainsString('Event 3', $html);
        $this->assertStringNotContainsString('bc-cal-allday', $html);
    }

    public function testDayMetaIsCategory(): void
    {
        $html = $this->helper()->day([
            $this->ev(1, '2026-11-18', '09:00', '10:00', ['title' => 'Kickoff', 'resource' => 'kofi', 'category' => 'meeting']),
        ], ['date' => '2026-11-18', 'resources' => ['kofi' => 'Kofi <M>'], 'categories' => ['meeting' => 'Customer meeting']]);

        $this->assertStringContainsString('<span class="bc-cal-title">Kickoff</span> <span class="bc-cal-meta">Customer meeting</span><span class="sr-only">, Wednesday 18 November, Kofi &lt;M&gt;</span>', $html);
    }

    public function testDayValidatesLikeTheWeek(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/slot/');
        $this->helper()->day([], ['date' => '2026-11-18', 'slot' => 45]);
    }

    public function testMonthMatchesTheScreenTable(): void
    {
        // Fixture: the month table the `calendar` screen rendered before it moved into the helper.
        $dir = dirname(__DIR__, 3) . '/test_files/';
        $data = json_decode((string)file_get_contents($dir . 'calendar-month.json'), true, 512, JSON_THROW_ON_ERROR);
        $expected = trim((string)file_get_contents($dir . 'calendar-month.html'));

        $html = $this->helper()->month($data['events'], ['date' => '2026-11-01', 'today' => '2026-11-20', 'categories' => $data['categories']]);

        $this->assertSame($expected, $this->squash($html));
        $this->assertSame('', (string)$this->View->fetch('css'), 'The month needs no calendar stylesheet');
    }

    public function testMonthDayUrlLinksTheDayNumber(): void
    {
        $html = $this->helper()->month([], [
            'date' => '2026-11-18',
            'today' => '2026-11-20',
            'dayUrl' => fn (string $d): string => '/calendar?view=day&date=' . $d,
        ]);
        $this->assertStringContainsString('data-calendar="2026-11"', $html);
        $this->assertMatchesRegularExpression('#<a href="/calendar\?view=day&amp;date=2026-11-18"[^>]*><time datetime="2026-11-18"#', $html);
    }

    public function testMonthAttributesGoOnTheTable(): void
    {
        $html = $this->helper()->month([], ['date' => '2026-02-10', 'attributes' => ['data-endpoint' => '/calendar/move'], 'class' => 'mt-2']);
        $this->assertMatchesRegularExpression('#^<table class="w-full min-w-\[38rem\] table-fixed border-collapse text-sm mt-2" data-calendar="2026-02" data-endpoint="/calendar/move"#', $html);
        $this->assertStringContainsString('<caption class="sr-only">February 2026</caption>', $html);
    }

    public function testMonthEventUrl(): void
    {
        $html = $this->helper()->month([['id' => 5, 'title' => 'Demo', 'date' => '2026-11-04', 'time' => '10:00', 'url' => '/events/5']], ['date' => '2026-11-01']);
        $this->assertStringContainsString('<a href="/events/5" class="flex min-w-0 items-center gap-1', $html);
    }
}
