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
 * Renders the baked `bookings` screen template with its sample data (or custom data).
 */
class BookingsScreenTest extends TestCase
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
     * @param array<string, mixed>|null $vars View variables (default: the sample data).
     * @param array<string, mixed> $query Query string of the request.
     */
    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('bookings', 'Bookings');
        $file = TMP . 'bookings_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Bookings/index.php']);
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        $this->assertSame(0, $code, 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/bookings',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Bookings', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('bookings');

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

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('bookings');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('bookings');

        $this->assertSame(['date', 'dateLabel', 'view', 'dayStart', 'dayEnd', 'resources', 'types', 'bookings'], array_keys($data));
        $this->assertLessThanOrEqual(6, count($data['resources']));
        $this->assertGreaterThanOrEqual(12, count($data['bookings']));
        $byResource = [];
        foreach ($data['bookings'] as $booking) {
            $this->assertArrayHasKey($booking['resource'], $data['resources']);
            $this->assertArrayHasKey($booking['type'], $data['types']);
            $this->assertLessThanOrEqual($booking['capacity'], $booking['booked']);
            $this->assertGreaterThanOrEqual($data['dayStart'], $booking['start']);
            $this->assertLessThanOrEqual($data['dayEnd'], $booking['end']);
            $byResource[$booking['resource']][] = [$booking['start'], $booking['end']];
        }
        foreach ($byResource as $ranges) {
            sort($ranges);
            for ($i = 1; $i < count($ranges); $i++) {
                $this->assertGreaterThanOrEqual($ranges[$i - 1][1], $ranges[$i][0], 'Classes of a resource do not overlap');
            }
        }
    }

    public function testGridRendersEveryBookingPlacedWithUtilityClasses(): void
    {
        $data = ScreenRegistry::data('bookings');
        $html = $this->render();

        $this->assertSame(count($data['bookings']), substr_count($html, 'data-booking-id='));
        foreach ($data['resources'] as $id => $name) {
            $this->assertMatchesRegularExpression('/data-resource="' . $id . '"[^>]*>' . preg_quote($name, '/') . '</', $html);
        }
        // 06:00-07:00 on the first resource: rows 2 and 3, two slots, first column
        $this->assertMatchesRegularExpression('/data-booking-id="1"[^>]*class="[^"]*col-start-2 row-start-2 row-span-2/', $html);
        // 09:00-12:00 open gym on the third resource: starts at slot 6 (row 8), six slots
        $this->assertMatchesRegularExpression('/data-booking-id="9"[^>]*class="[^"]*col-start-4 row-start-8 row-span-6/', $html);
        $this->assertStringContainsString('grid-cols-[3.5rem_repeat(4,minmax(9rem,1fr))]', $html);
        $this->assertStringContainsString('grid-rows-[auto] auto-rows-[2.5rem]', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringContainsString('relative min-w-0 overflow-x-auto', $html);
    }

    public function testBlockShowsNameCoachTimeCapacityAndTypeText(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/data-booking-id="1"[^>]*>.*?Sunrise WOD.*?<span data-type-label>WOD<\/span> · 06:00–07:00.*?Elena Sarto · <span>14\/16<\/span><span class="sr-only"> booked<\/span>/s', $html);
        $this->assertMatchesRegularExpression('/data-booking-type="lifting"/', $html);
        $this->assertStringContainsString('<span data-type-label>Weightlifting</span>', $html);
        $this->assertStringContainsString('<span data-type-label>Mobility</span>', $html);
        $this->assertStringContainsString('<span data-type-label>Open gym</span>', $html);
        $this->assertStringContainsString('aria-label="Sunrise WOD, 06:00–07:00, Main floor"', $html);
    }

    public function testGridClassesAreLiteralForCompiledTailwind(): void
    {
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/bookings/template.twig');

        // Tailwind only generates classes it finds written out in full in the sources.
        $this->assertDoesNotMatchRegularExpression('/(row|col)-(start|span)-<\?=|repeat\(<\?=|grid-rows-\[[^\]]*<\?=/', $template);
        foreach ([2, 31, 49] as $row) {
            $this->assertStringContainsString("'row-start-" . $row . "'", $template);
        }
        $this->assertStringNotContainsString("'{0}, {1}, {2}'", $template, 'The block label is data, not a sentence');
    }

    public function testBlocksDoNotClipTheirMenu(): void
    {
        $html = $this->render();

        preg_match_all('/<div role="group"[^>]*class="([^"]*)"/', $html, $blocks);
        $this->assertNotEmpty($blocks[1]);
        foreach ($blocks[1] as $class) {
            $this->assertStringNotContainsString('overflow-hidden', $class);
        }
        // Menus of blocks near the end of the day open upwards so the scroll area does not clip them.
        $this->assertMatchesRegularExpression('/data-booking-id="15".*?data-popover[^>]*data-side="top"/s', $html);
        preg_match('/data-booking-id="1".*?data-popover[^>]*>/s', $html, $first);
        $this->assertStringNotContainsString('data-side="top"', $first[0]);
    }

    public function testOneSlotBlockUsesASingleLine(): void
    {
        $html = $this->render();

        // Desk reset lasts 30 minutes: name, type and time on one line, coach and capacity for screen readers.
        preg_match('/data-booking-id="13".*?(?=data-booking-id=|<\/section>)/s', $html, $block);
        $this->assertMatchesRegularExpression('/<p class="truncate"><span class="font-semibold">Desk reset<\/span> · <span data-type-label>Mobility<\/span> · 12:30–13:00<\/p>/', $block[0]);
        $this->assertMatchesRegularExpression('/<p class="sr-only">Ana Costa · /', $block[0]);
    }

    public function testBookingsOutsideTheDayAreClippedOrSkipped(): void
    {
        $base = ScreenRegistry::data('bookings')['bookings'][0];
        $html = $this->render($this->data(['bookings' => [
            ['id' => 1, 'start' => '05:00', 'end' => '06:30'] + $base,
            ['id' => 2, 'start' => '20:30', 'end' => '22:00'] + $base,
            ['id' => 3, 'start' => '04:00', 'end' => '05:30'] + $base,
            ['id' => 4, 'start' => '21:00', 'end' => '22:00'] + $base,
        ]]));

        $this->assertMatchesRegularExpression('/data-booking-id="1"[^>]*class="[^"]*row-start-2 row-span-1/', $html);
        $this->assertMatchesRegularExpression('/data-booking-id="2"[^>]*class="[^"]*row-start-31 row-span-1/', $html);
        $this->assertStringNotContainsString('data-booking-id="3"', $html);
        $this->assertStringNotContainsString('data-booking-id="4"', $html);
    }

    public function testInvalidDateDoesNotBreakThePage(): void
    {
        $html = $this->render($this->data(['date' => 'not-a-date']));

        $this->assertStringContainsString('data-schedule-grid', $html);
        $this->assertStringNotContainsString('rel="prev"', $html);
        $this->assertStringNotContainsString('datetime=', $html);
    }

    public function testRowMenusAreNamedEllipsisButtons(): void
    {
        $data = ScreenRegistry::data('bookings');
        $html = $this->render();

        $this->assertSame(count($data['bookings']), substr_count($html, 'aria-haspopup="menu"'));
        $this->assertStringContainsString('<span class="sr-only">Actions for Sunrise WOD, 06:00–07:00</span>', $html);
        // Three "Open gym" blocks: every menu name is unique.
        preg_match_all('/<span class="sr-only">(Actions for [^<]*)<\/span>/', $html, $names);
        $this->assertSame(count($data['bookings']), count(array_unique($names[1])));
        $this->assertStringContainsString('Edit booking', $html);
        $this->assertStringContainsString('Cancel booking', $html);
    }

    public function testDateNavigationUsesGetLinks(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('Friday, 20 November 2026', $html);
        $this->assertStringContainsString('datetime="2026-11-20"', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*href="\/bookings\?date=2026-11-19"[^>]*>.*?Previous day<\/span>/s', $html);
        $this->assertMatchesRegularExpression('/<a [^>]*href="\/bookings\?date=2026-11-21"[^>]*>.*?Next day<\/span>/s', $html);
    }

    public function testViewSwitchAndListView(): void
    {
        $grid = $this->render();
        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="true"[^>]*>\s*<svg.*?<\/svg>\s*Grid/s', $grid);
        $this->assertStringContainsString('href="/bookings?view=list"', $grid);

        $data = ScreenRegistry::data('bookings');
        $html = $this->render(null, ['view' => 'list']);
        $this->assertStringNotContainsString('data-schedule-grid', $html);
        $this->assertSame(count($data['bookings']), substr_count($html, '<tr') - 1, 'One body row per booking');
        $this->assertStringContainsString('<th', $html);
        $this->assertMatchesRegularExpression('/<span[^>]*data-booking-type="wod"[^>]*>WOD<\/span>/', $html);
        $this->assertStringContainsString('06:00–07:00', $html);
        $this->assertStringContainsString('href="/bookings?view=grid"', $html);
        $this->assertStringContainsString('<span>14/16</span>', $html);
    }

    public function testNewBookingIsAnInertButton(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<button(?![^>]*\bhref)[^>]*>\s*<svg.*?<\/svg>\s*New booking\s*<\/button>/s', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['bookings' => []]));

        $this->assertStringContainsString('No classes scheduled', $html);
        $this->assertStringNotContainsString('data-schedule-grid', $html);
        $this->assertStringNotContainsString('data-booking-id', $html);
        $this->assertStringContainsString('Previous day', $html);
    }

    public function testBookingOfUnknownResourceIsSkipped(): void
    {
        $data = ScreenRegistry::data('bookings');
        $html = $this->render($this->data(['bookings' => [['resource' => 'ghost'] + $data['bookings'][0]]]));

        $this->assertStringContainsString('No classes scheduled', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'dateLabel' => '<b>today</b>',
            'resources' => ['main' => '<i>Floor</i>'],
            'types' => ['wod' => '<u>WOD</u>'],
            'bookings' => [[
                'id' => 1,
                'resource' => 'main',
                'type' => 'wod',
                'name' => '<script>alert(1)</script>',
                'coach' => '<img src=x onerror=alert(2)>',
                'start' => '06:00',
                'end' => '07:00',
                'booked' => 1,
                'capacity' => 2,
            ]],
        ]));

        foreach (['<script>alert', '<img src=x', '<b>today', '<i>Floor', '<u>WOD'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);

        $list = $this->render($this->data(['bookings' => [['name' => '<script>alert(1)</script>'] + ScreenRegistry::data('bookings')['bookings'][0]]]), ['view' => 'list']);
        $this->assertStringNotContainsString('<script>alert', $list);
    }

    public function testUniqueIds(): void
    {
        foreach ([[], ['view' => 'list']] as $query) {
            $html = $this->render(null, $query);

            preg_match_all('/\bid="([^"]+)"/', $html, $matches);
            $this->assertNotEmpty($matches[1]);
            $this->assertSame($matches[1], array_values(array_unique($matches[1])));
        }
    }

    public function testNoLinksToMissingActions(): void
    {
        foreach ([[], ['view' => 'list']] as $query) {
            $html = $this->render(null, $query);

            preg_match_all('/href="([^"]*)"/', $html, $matches);
            foreach ($matches[1] as $href) {
                $this->assertMatchesRegularExpression('/^(#|\/bookings\?)/', $href, 'Unexpected link ' . $href);
            }
            $this->assertStringNotContainsString('<form', $html);
        }
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/bookings/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/bookings/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe|Fernway Fit/', $source);
    }
}
