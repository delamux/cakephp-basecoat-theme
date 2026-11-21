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
}
