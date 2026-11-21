<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use Cake\I18n\Date;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use function Cake\Core\h;

/**
 * Calendar views rendered on the server: `month()`, `week()` and `day()`.
 *
 * Events (one format for the three views):
 *
 * - `id` (scalar), `title`, `date` (`Y-m-d`);
 * - `time` (`H:i` start, or null for an all-day event), `end` (`H:i`, optional, `24:00` allowed;
 *   defaults to one slot after `time`);
 * - `resource` (key of the `resources` option), `category` (key of `categories`), `url` (string or
 *   routing array; `#` when absent).
 *
 * Week and day draw the hours as rows (`start` to `end`, one row per `slot` minutes). The week has a
 * column per weekday of `days` (0 = Sunday … 6 = Saturday, in that order), the day a column per
 * resource. Layout classes come from `webroot/css/basecoat-calendar.css` (no inline styles).
 */
class CalendarHelper extends AbstractComponentHelper
{
    /**
     * Slot lengths, in minutes.
     *
     * @var array<int>
     */
    public const SLOTS = [15, 30, 60];

    /**
     * Column widths.
     *
     * @var array<string>
     */
    public const WIDTHS = ['sm', 'md', 'lg'];

    /**
     * Chips shown per day in the month before "+N more".
     *
     * @var int
     */
    public const MONTH_CHIPS = 3;

    /**
     * Month chip colours per category key (Tailwind classes, written out for compiled builds).
     *
     * @var array<string, string>
     */
    protected const MONTH_CHIP = [
        'meeting' => 'bg-blue-100 text-blue-900 dark:bg-blue-950 dark:text-blue-200',
        'deadline' => 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200',
        'release' => 'bg-green-100 text-green-900 dark:bg-green-950 dark:text-green-200',
        'team' => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    ];

    /**
     * Most lanes side by side in a column; the other overlapping events go to "+N more".
     *
     * @var int
     */
    public const MAX_LANES = 4;

    /**
     * Option keys that are not HTML attributes.
     *
     * @var array<string>
     */
    protected const OPTION_KEYS = [
        'date', 'today', 'days', 'start', 'end', 'slot', 'resources', 'categories', 'columnWidth', 'dayUrl',
        'label', 'assets', 'attributes', 'templates', 'classes',
    ];

    /**
     * Layout classes of `basecoat-calendar.css`, written out so any CSS scanner finds them.
     *
     * @var array<int, string>
     */
    protected const ROW = [
        1 => 'bc-cal-row-1', 2 => 'bc-cal-row-2', 3 => 'bc-cal-row-3', 4 => 'bc-cal-row-4', 5 => 'bc-cal-row-5',
        6 => 'bc-cal-row-6', 7 => 'bc-cal-row-7', 8 => 'bc-cal-row-8', 9 => 'bc-cal-row-9', 10 => 'bc-cal-row-10',
        11 => 'bc-cal-row-11', 12 => 'bc-cal-row-12', 13 => 'bc-cal-row-13', 14 => 'bc-cal-row-14',
        15 => 'bc-cal-row-15', 16 => 'bc-cal-row-16', 17 => 'bc-cal-row-17', 18 => 'bc-cal-row-18',
        19 => 'bc-cal-row-19', 20 => 'bc-cal-row-20', 21 => 'bc-cal-row-21', 22 => 'bc-cal-row-22',
        23 => 'bc-cal-row-23', 24 => 'bc-cal-row-24', 25 => 'bc-cal-row-25', 26 => 'bc-cal-row-26',
        27 => 'bc-cal-row-27', 28 => 'bc-cal-row-28', 29 => 'bc-cal-row-29', 30 => 'bc-cal-row-30',
        31 => 'bc-cal-row-31', 32 => 'bc-cal-row-32', 33 => 'bc-cal-row-33', 34 => 'bc-cal-row-34',
        35 => 'bc-cal-row-35', 36 => 'bc-cal-row-36', 37 => 'bc-cal-row-37', 38 => 'bc-cal-row-38',
        39 => 'bc-cal-row-39', 40 => 'bc-cal-row-40', 41 => 'bc-cal-row-41', 42 => 'bc-cal-row-42',
        43 => 'bc-cal-row-43', 44 => 'bc-cal-row-44', 45 => 'bc-cal-row-45', 46 => 'bc-cal-row-46',
        47 => 'bc-cal-row-47', 48 => 'bc-cal-row-48', 49 => 'bc-cal-row-49', 50 => 'bc-cal-row-50',
        51 => 'bc-cal-row-51', 52 => 'bc-cal-row-52', 53 => 'bc-cal-row-53', 54 => 'bc-cal-row-54',
        55 => 'bc-cal-row-55', 56 => 'bc-cal-row-56', 57 => 'bc-cal-row-57', 58 => 'bc-cal-row-58',
        59 => 'bc-cal-row-59', 60 => 'bc-cal-row-60', 61 => 'bc-cal-row-61', 62 => 'bc-cal-row-62',
        63 => 'bc-cal-row-63', 64 => 'bc-cal-row-64', 65 => 'bc-cal-row-65', 66 => 'bc-cal-row-66',
        67 => 'bc-cal-row-67', 68 => 'bc-cal-row-68', 69 => 'bc-cal-row-69', 70 => 'bc-cal-row-70',
        71 => 'bc-cal-row-71', 72 => 'bc-cal-row-72', 73 => 'bc-cal-row-73', 74 => 'bc-cal-row-74',
        75 => 'bc-cal-row-75', 76 => 'bc-cal-row-76', 77 => 'bc-cal-row-77', 78 => 'bc-cal-row-78',
        79 => 'bc-cal-row-79', 80 => 'bc-cal-row-80', 81 => 'bc-cal-row-81', 82 => 'bc-cal-row-82',
        83 => 'bc-cal-row-83', 84 => 'bc-cal-row-84', 85 => 'bc-cal-row-85', 86 => 'bc-cal-row-86',
        87 => 'bc-cal-row-87', 88 => 'bc-cal-row-88', 89 => 'bc-cal-row-89', 90 => 'bc-cal-row-90',
        91 => 'bc-cal-row-91', 92 => 'bc-cal-row-92', 93 => 'bc-cal-row-93', 94 => 'bc-cal-row-94',
        95 => 'bc-cal-row-95', 96 => 'bc-cal-row-96',
    ];

    /**
     * @var array<int, string>
     */
    protected const SPAN = [
        1 => 'bc-cal-span-1', 2 => 'bc-cal-span-2', 3 => 'bc-cal-span-3', 4 => 'bc-cal-span-4', 5 => 'bc-cal-span-5',
        6 => 'bc-cal-span-6', 7 => 'bc-cal-span-7', 8 => 'bc-cal-span-8', 9 => 'bc-cal-span-9',
        10 => 'bc-cal-span-10', 11 => 'bc-cal-span-11', 12 => 'bc-cal-span-12', 13 => 'bc-cal-span-13',
        14 => 'bc-cal-span-14', 15 => 'bc-cal-span-15', 16 => 'bc-cal-span-16', 17 => 'bc-cal-span-17',
        18 => 'bc-cal-span-18', 19 => 'bc-cal-span-19', 20 => 'bc-cal-span-20', 21 => 'bc-cal-span-21',
        22 => 'bc-cal-span-22', 23 => 'bc-cal-span-23', 24 => 'bc-cal-span-24', 25 => 'bc-cal-span-25',
        26 => 'bc-cal-span-26', 27 => 'bc-cal-span-27', 28 => 'bc-cal-span-28', 29 => 'bc-cal-span-29',
        30 => 'bc-cal-span-30', 31 => 'bc-cal-span-31', 32 => 'bc-cal-span-32', 33 => 'bc-cal-span-33',
        34 => 'bc-cal-span-34', 35 => 'bc-cal-span-35', 36 => 'bc-cal-span-36', 37 => 'bc-cal-span-37',
        38 => 'bc-cal-span-38', 39 => 'bc-cal-span-39', 40 => 'bc-cal-span-40', 41 => 'bc-cal-span-41',
        42 => 'bc-cal-span-42', 43 => 'bc-cal-span-43', 44 => 'bc-cal-span-44', 45 => 'bc-cal-span-45',
        46 => 'bc-cal-span-46', 47 => 'bc-cal-span-47', 48 => 'bc-cal-span-48', 49 => 'bc-cal-span-49',
        50 => 'bc-cal-span-50', 51 => 'bc-cal-span-51', 52 => 'bc-cal-span-52', 53 => 'bc-cal-span-53',
        54 => 'bc-cal-span-54', 55 => 'bc-cal-span-55', 56 => 'bc-cal-span-56', 57 => 'bc-cal-span-57',
        58 => 'bc-cal-span-58', 59 => 'bc-cal-span-59', 60 => 'bc-cal-span-60', 61 => 'bc-cal-span-61',
        62 => 'bc-cal-span-62', 63 => 'bc-cal-span-63', 64 => 'bc-cal-span-64', 65 => 'bc-cal-span-65',
        66 => 'bc-cal-span-66', 67 => 'bc-cal-span-67', 68 => 'bc-cal-span-68', 69 => 'bc-cal-span-69',
        70 => 'bc-cal-span-70', 71 => 'bc-cal-span-71', 72 => 'bc-cal-span-72', 73 => 'bc-cal-span-73',
        74 => 'bc-cal-span-74', 75 => 'bc-cal-span-75', 76 => 'bc-cal-span-76', 77 => 'bc-cal-span-77',
        78 => 'bc-cal-span-78', 79 => 'bc-cal-span-79', 80 => 'bc-cal-span-80', 81 => 'bc-cal-span-81',
        82 => 'bc-cal-span-82', 83 => 'bc-cal-span-83', 84 => 'bc-cal-span-84', 85 => 'bc-cal-span-85',
        86 => 'bc-cal-span-86', 87 => 'bc-cal-span-87', 88 => 'bc-cal-span-88', 89 => 'bc-cal-span-89',
        90 => 'bc-cal-span-90', 91 => 'bc-cal-span-91', 92 => 'bc-cal-span-92', 93 => 'bc-cal-span-93',
        94 => 'bc-cal-span-94', 95 => 'bc-cal-span-95', 96 => 'bc-cal-span-96',
    ];

    /**
     * @var array<int, string>
     */
    protected const ROWS = [
        1 => 'bc-cal-rows-1', 2 => 'bc-cal-rows-2', 3 => 'bc-cal-rows-3', 4 => 'bc-cal-rows-4', 5 => 'bc-cal-rows-5',
        6 => 'bc-cal-rows-6', 7 => 'bc-cal-rows-7', 8 => 'bc-cal-rows-8', 9 => 'bc-cal-rows-9',
        10 => 'bc-cal-rows-10', 11 => 'bc-cal-rows-11', 12 => 'bc-cal-rows-12', 13 => 'bc-cal-rows-13',
        14 => 'bc-cal-rows-14', 15 => 'bc-cal-rows-15', 16 => 'bc-cal-rows-16', 17 => 'bc-cal-rows-17',
        18 => 'bc-cal-rows-18', 19 => 'bc-cal-rows-19', 20 => 'bc-cal-rows-20', 21 => 'bc-cal-rows-21',
        22 => 'bc-cal-rows-22', 23 => 'bc-cal-rows-23', 24 => 'bc-cal-rows-24', 25 => 'bc-cal-rows-25',
        26 => 'bc-cal-rows-26', 27 => 'bc-cal-rows-27', 28 => 'bc-cal-rows-28', 29 => 'bc-cal-rows-29',
        30 => 'bc-cal-rows-30', 31 => 'bc-cal-rows-31', 32 => 'bc-cal-rows-32', 33 => 'bc-cal-rows-33',
        34 => 'bc-cal-rows-34', 35 => 'bc-cal-rows-35', 36 => 'bc-cal-rows-36', 37 => 'bc-cal-rows-37',
        38 => 'bc-cal-rows-38', 39 => 'bc-cal-rows-39', 40 => 'bc-cal-rows-40', 41 => 'bc-cal-rows-41',
        42 => 'bc-cal-rows-42', 43 => 'bc-cal-rows-43', 44 => 'bc-cal-rows-44', 45 => 'bc-cal-rows-45',
        46 => 'bc-cal-rows-46', 47 => 'bc-cal-rows-47', 48 => 'bc-cal-rows-48', 49 => 'bc-cal-rows-49',
        50 => 'bc-cal-rows-50', 51 => 'bc-cal-rows-51', 52 => 'bc-cal-rows-52', 53 => 'bc-cal-rows-53',
        54 => 'bc-cal-rows-54', 55 => 'bc-cal-rows-55', 56 => 'bc-cal-rows-56', 57 => 'bc-cal-rows-57',
        58 => 'bc-cal-rows-58', 59 => 'bc-cal-rows-59', 60 => 'bc-cal-rows-60', 61 => 'bc-cal-rows-61',
        62 => 'bc-cal-rows-62', 63 => 'bc-cal-rows-63', 64 => 'bc-cal-rows-64', 65 => 'bc-cal-rows-65',
        66 => 'bc-cal-rows-66', 67 => 'bc-cal-rows-67', 68 => 'bc-cal-rows-68', 69 => 'bc-cal-rows-69',
        70 => 'bc-cal-rows-70', 71 => 'bc-cal-rows-71', 72 => 'bc-cal-rows-72', 73 => 'bc-cal-rows-73',
        74 => 'bc-cal-rows-74', 75 => 'bc-cal-rows-75', 76 => 'bc-cal-rows-76', 77 => 'bc-cal-rows-77',
        78 => 'bc-cal-rows-78', 79 => 'bc-cal-rows-79', 80 => 'bc-cal-rows-80', 81 => 'bc-cal-rows-81',
        82 => 'bc-cal-rows-82', 83 => 'bc-cal-rows-83', 84 => 'bc-cal-rows-84', 85 => 'bc-cal-rows-85',
        86 => 'bc-cal-rows-86', 87 => 'bc-cal-rows-87', 88 => 'bc-cal-rows-88', 89 => 'bc-cal-rows-89',
        90 => 'bc-cal-rows-90', 91 => 'bc-cal-rows-91', 92 => 'bc-cal-rows-92', 93 => 'bc-cal-rows-93',
        94 => 'bc-cal-rows-94', 95 => 'bc-cal-rows-95', 96 => 'bc-cal-rows-96',
    ];

    /**
     * @var array<int, string>
     */
    protected const LANES = [1 => 'bc-cal-lanes-1', 2 => 'bc-cal-lanes-2', 3 => 'bc-cal-lanes-3', 4 => 'bc-cal-lanes-4'];

    /**
     * @var array<int, string>
     */
    protected const LANE = [1 => 'bc-cal-lane-1', 2 => 'bc-cal-lane-2', 3 => 'bc-cal-lane-3', 4 => 'bc-cal-lane-4'];

    /**
     * @var array<int, string>
     */
    protected const SLOT_CLASS = [15 => 'bc-cal-slot-15', 30 => 'bc-cal-slot-30', 60 => 'bc-cal-slot-60'];

    /**
     * @var array<string, string>
     */
    protected const VIEW_CLASS = ['week' => 'bc-cal-week', 'day' => 'bc-cal-day'];

    /**
     * @var array<string, string>
     */
    protected const WIDTH_CLASS = ['sm' => 'bc-cal-w-sm', 'md' => 'bc-cal-w-md', 'lg' => 'bc-cal-w-lg'];

    /**
     * Category keys with a colour; any other key is neutral (`bc-cal-c-none`).
     *
     * @var array<string, string>
     */
    protected const COLORS = [
        'meeting' => 'bc-cal-c-meeting',
        'deadline' => 'bc-cal-c-deadline',
        'release' => 'bc-cal-c-release',
        'team' => 'bc-cal-c-team',
    ];

    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Html', 'Url'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'days' => [1, 2, 3, 4, 5, 6, 0],
        'start' => '08:00',
        'end' => '20:00',
        'slot' => 30,
        'columnWidth' => 'md',
        'assets' => true,
    ];

    /**
     * Week view: a column per weekday of `days`, hours as rows.
     *
     * @param array<int, array<string, mixed>> $events Events.
     * @param array<string, mixed> $options Options.
     * @return string
     * @throws \InvalidArgumentException For an invalid option or event.
     */
    public function week(array $events, array $options = []): string
    {
        $opts = $this->gridOptions($options);
        $events = $this->events($events, $opts['slot']);
        $dates = $this->weekDates($opts['date'], $opts['days']);

        $columns = [];
        foreach ($dates as $date) {
            $day = new Date($date);
            $head = '<span aria-hidden="true">' . h($day->i18nFormat('EEE d')) . '</span>'
                . '<span class="sr-only">' . h($day->i18nFormat('EEEE d MMMM')) . '</span>';
            if ($opts['dayUrl'] !== null) {
                $head = '<a href="' . h($this->link(($opts['dayUrl'])($date))) . '">' . $head . '</a>';
            }
            $columns[] = [
                'id' => $date,
                'label' => $head,
                'current' => $date === $opts['today'],
                'events' => array_values(array_filter($events, fn (array $e): bool => $e['date'] === $date)),
            ];
        }
        $label = $opts['label'] ?? __d('basecoat_theme', 'Week of {0}', (new Date($dates[0]))->i18nFormat('EEEE d MMMM y'));

        return $this->timeGrid($columns, $opts, 'week', $label, $options);
    }

    /**
     * Week and day grid: an hour column and one column per entry of `$columns`.
     *
     * @param array<int, array<string, mixed>> $columns `id`, `label` (HTML), `current`, `events` (normalized).
     * @param array<string, mixed> $opts Validated options (`gridOptions()`).
     * @param string $view `week` or `day`.
     * @param string $label Accessible name.
     * @param array<string, mixed> $options Options as given (for `class`, `attributes` and extra attributes).
     * @return string
     */
    protected function timeGrid(array $columns, array $opts, string $view, string $label, array $options): string
    {
        if ($opts['assets']) {
            $this->Html->css('BasecoatTheme.basecoat-calendar', ['block' => true]);
        }
        $rows = intdiv($opts['end'] - $opts['start'], $opts['slot']);
        $allDay = false;
        foreach ($columns as $column) {
            foreach ($column['events'] as $event) {
                $allDay = $allDay || $event['allDay'];
            }
        }
        $prefix = $this->uniqueId('bc-cal');

        $hours = '';
        for ($m = $opts['start']; $m < $opts['end']; $m += $opts['slot']) {
            if ($m % 60 === 0) {
                $row = intdiv($m - $opts['start'], $opts['slot']) + 1;
                $hours .= '<span class="bc-cal-hour ' . self::ROW[$row] . '">' . $this->clock($m) . '</span>';
            }
        }
        $html = '<div class="bc-cal-times" aria-hidden="true"><div class="bc-cal-corner"></div>'
            . ($allDay ? '<div class="bc-cal-allday-label">' . h(__d('basecoat_theme', 'All day')) . '</div>' : '')
            . '<div class="bc-cal-hours ' . self::ROWS[$rows] . '">' . $hours . '</div></div>';

        foreach ($columns as $column) {
            $id = $prefix . '-' . $column['id'];
            $placed = [];
            $outside = [];
            $allDayItems = '';
            foreach ($column['events'] as $event) {
                if ($event['allDay']) {
                    $allDayItems .= '<li class="bc-cal-event ' . $this->colorClass($event) . '">' . $this->eventLink($event, $opts, $view) . '</li>';
                } elseif ($event['to'] <= $opts['start'] || $event['from'] >= $opts['end']) {
                    $outside[] = $event;
                } else {
                    $placed[] = $event;
                }
            }
            $overflow = [];
            $items = '';
            foreach ($this->lanes($placed, $opts) as $event) {
                if ($event['lane'] > self::MAX_LANES) {
                    $overflow[] = $event;
                    continue;
                }
                $classes = ['bc-cal-event', self::ROW[$event['row']], self::SPAN[$event['span']], self::LANES[$event['lanes']], self::LANE[$event['lane']], $this->colorClass($event)];
                if ($event['from'] < $opts['start']) {
                    $classes[] = 'bc-cal-cut-start';
                }
                if ($event['to'] > $opts['end']) {
                    $classes[] = 'bc-cal-cut-end';
                }
                $items .= '<li class="' . implode(' ', $classes) . '">' . $this->eventLink($event, $opts, $view) . '</li>';
            }

            $head = '<span class="bc-cal-name" id="' . h($id) . '">' . $column['label'] . '</span>';
            if ($overflow) {
                $head .= $this->disclosure('bc-cal-more', __d('basecoat_theme', '+{0} more', count($overflow)), $overflow, $opts, $view);
            }
            if ($outside) {
                $head .= $this->disclosure('bc-cal-outside', __dn('basecoat_theme', '{0} event outside hours', '{0} events outside hours', count($outside), count($outside)), $outside, $opts, $view);
            }
            $html .= '<div class="bc-cal-col" role="group" aria-labelledby="' . h($id) . '">'
                . '<div class="bc-cal-head' . ($column['current'] ? ' bc-cal-today' : '') . '"' . ($column['current'] ? ' aria-current="date"' : '') . '>' . $head . '</div>'
                . ($allDay ? '<ul class="bc-cal-allday">' . $allDayItems . '</ul>' : '')
                . '<ol class="bc-cal-body ' . self::ROWS[$rows] . ' ' . self::SLOT_CLASS[$opts['slot']] . '">' . $items . '</ol>'
                . '</div>';
        }

        // The scroll region inside carries the accessible name (once)
        $root = $this->rootAttributes(['bc-cal', self::VIEW_CLASS[$view], self::WIDTH_CLASS[$opts['columnWidth']]], [
            'data-calendar-view' => $view,
            'data-calendar-date' => $opts['date'],
        ] + (array)($options['attributes'] ?? []) + $options, self::OPTION_KEYS);
        $root = ['class' => $root['class']] + $root;

        return '<section' . $this->formatAttributes($root) . '>'
            . '<div class="bc-cal-scroll" role="region" tabindex="0" aria-label="' . h($label) . '"><div class="bc-cal-grid">' . $html . '</div></div>'
            . '</section>';
    }

    /**
     * Places the events of one column: rows (start floored and end ceiled to the slot, clipped to the
     * visible hours) and lanes (greedy: an event takes the first lane free at its start; `lanes` is the
     * number of lanes of its overlap cluster, at most `MAX_LANES`).
     *
     * @param array<int, array<string, mixed>> $events Normalized timed events of the column.
     * @param array<string, mixed> $opts Validated options.
     * @return array<int, array<string, mixed>> Events with `row`, `span`, `lane` and `lanes`, in placement order.
     */
    protected function lanes(array $events, array $opts): array
    {
        $slot = $opts['slot'];
        foreach ($events as &$event) {
            $from = max($opts['start'], intdiv($event['from'], $slot) * $slot);
            $to = min($opts['end'], (int)ceil($event['to'] / $slot) * $slot);
            $event['top'] = $from;
            $event['bottom'] = $to;
            $event['row'] = intdiv($from - $opts['start'], $slot) + 1;
            $event['span'] = max(1, intdiv($to - $from, $slot));
        }
        unset($event);
        usort($events, fn (array $a, array $b): int => [$a['top'], -$a['bottom'], $a['title']] <=> [$b['top'], -$b['bottom'], $b['title']]);

        $laneEnds = [];
        $cluster = [];
        $clusterEnd = null;
        $close = function () use (&$events, &$cluster, &$laneEnds): void {
            $count = min(self::MAX_LANES, count($laneEnds));
            foreach ($cluster as $index) {
                $events[$index]['lanes'] = $count;
            }
            $cluster = [];
            $laneEnds = [];
        };
        foreach ($events as $index => $event) {
            if ($clusterEnd !== null && $event['top'] >= $clusterEnd) {
                $close();
                $clusterEnd = null;
            }
            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $event['top']) {
                $lane++;
            }
            $laneEnds[$lane] = $event['bottom'];
            $events[$index]['lane'] = $lane + 1;
            $cluster[] = $index;
            $clusterEnd = max($clusterEnd ?? 0, $event['bottom']);
        }
        $close();

        return $events;
    }

    /**
     * Link of an event: visible time, title and meta (resource in the week, category in the day), and
     * the rest of its description for screen readers.
     *
     * @param array<string, mixed> $event Normalized event.
     * @param array<string, mixed> $opts Validated options.
     * @param string $view `week` or `day`.
     * @return string
     */
    protected function eventLink(array $event, array $opts, string $view): string
    {
        $resource = $event['resource'] !== null ? ($opts['resources'][$event['resource']] ?? null) : null;
        $category = $event['category'] !== null ? ($opts['categories'][$event['category']] ?? $event['category']) : null;
        $meta = $view === 'week' ? $resource : $category;
        $hidden = [(new Date($event['date']))->i18nFormat('EEEE d MMMM')];
        $hidden[] = $view === 'week' ? $category : $resource;

        $time = $event['allDay'] ? __d('basecoat_theme', 'All day') : $event['fromText'] . '–' . $event['toText'];

        return '<a href="' . h($this->link($event['url'])) . '">'
            . '<span class="bc-cal-time">' . h($time) . '</span> '
            . '<span class="bc-cal-title">' . h($event['title']) . '</span>'
            . ($meta !== null && $meta !== '' ? ' <span class="bc-cal-meta">' . h($meta) . '</span>' : '')
            . '<span class="sr-only">, ' . h(implode(', ', array_filter($hidden, fn ($v) => $v !== null && $v !== ''))) . '</span>'
            . '</a>';
    }

    /**
     * A `<details>` listing events that are not placed on the grid.
     *
     * @param string $class Class of the details element.
     * @param string $summary Summary text.
     * @param array<int, array<string, mixed>> $events Events.
     * @param array<string, mixed> $opts Validated options.
     * @param string $view `week` or `day`.
     * @return string
     */
    protected function disclosure(string $class, string $summary, array $events, array $opts, string $view): string
    {
        $items = '';
        foreach ($events as $event) {
            $items .= '<li class="bc-cal-event ' . $this->colorClass($event) . '">' . $this->eventLink($event, $opts, $view) . '</li>';
        }

        return '<details class="' . $class . '"><summary>' . h($summary) . '</summary><ul>' . $items . '</ul></details>';
    }

    /**
     * Colour class of an event's category (`bc-cal-c-none` for any other key).
     *
     * @param array<string, mixed> $event Normalized event.
     * @return string
     */
    protected function colorClass(array $event): string
    {
        return self::COLORS[$event['category'] ?? ''] ?? 'bc-cal-c-none';
    }

    /**
     * URL of a string or routing array (not escaped: attributes are escaped when written).
     *
     * @param mixed $url URL.
     * @return string
     */
    protected function link(mixed $url): string
    {
        if (is_string($url) && ($url === '' || $url[0] === '#' || str_contains($url, '://') || str_starts_with($url, '/'))) {
            return $url === '' ? '#' : $url;
        }

        return $this->Url->build(is_array($url) ? $url : (string)$url, ['escape' => false]);
    }

    /**
     * Month view: a table of whole weeks (Monday first) with event chips; days are drop targets and
     * chips are draggable with `basecoat-dnd.js` (load it with `basecoat-screens.js` and
     * `interactivity.css`, as the `calendar` screen does). Up to 3 chips a day, then "+N more".
     *
     * Options: `date` (the month containing it), `today`, `categories`, `dayUrl` (links the day
     * numbers and "+N more" to that day's view), `class` and `attributes` (on the `<table>`, e.g.
     * `data-endpoint`).
     *
     * @param array<int, array<string, mixed>> $events Events.
     * @param array<string, mixed> $options Options.
     * @return string
     * @throws \InvalidArgumentException For an invalid option or event.
     */
    public function month(array $events, array $options = []): string
    {
        $first = new Date($this->date($this->ymd($options['date'] ?? null, 'date'), 'date')->format('Y-m-01'));
        $today = isset($options['today']) ? $this->ymd($options['today'], 'today') : null;
        $dayUrl = $options['dayUrl'] ?? null;
        if ($dayUrl !== null && !is_callable($dayUrl)) {
            throw new InvalidArgumentException('Calendar option "dayUrl" must be a callable or null.');
        }
        $categories = array_map('strval', (array)($options['categories'] ?? []));
        $month = (int)$first->format('n');

        $offset = (int)$first->format('N') - 1;
        $weeks = (int)ceil(($offset + (int)$first->format('t')) / 7);
        $start = $first->subDays($offset);
        $monthLabel = $first->i18nFormat('LLLL y');

        $byDay = [];
        foreach ($this->events($events, 60) as $event) {
            $byDay[$event['date']][] = $event;
        }
        foreach ($byDay as &$dayEvents) {
            usort($dayEvents, fn (array $a, array $b): int => [(string)$a['fromText'], $a['title']] <=> [(string)$b['fromText'], $b['title']]);
        }
        unset($dayEvents);

        $messages = [
            'instructions' => __d('basecoat_theme', 'Press Space to pick up. Use the arrow keys to move (left and right change the day), Space or Enter to drop, Escape to cancel.'),
            'picked-up' => __d('basecoat_theme', 'Picked up {label}. {container}, position {position} of {total}.'),
            'moved' => __d('basecoat_theme', '{container}, position {position} of {total}.'),
            'dropped' => __d('basecoat_theme', 'Dropped {label}. {container}, position {position} of {total}.'),
            'cancelled' => __d('basecoat_theme', 'Cancelled. {label} is back in {container}, position {position} of {total}.'),
            'failed' => __d('basecoat_theme', 'Could not save the change. {label} was moved back.'),
        ];
        $attributes = [
            'class' => $this->mergeClasses('w-full min-w-[38rem] table-fixed border-collapse text-sm', $this->getConfig('class'), $options['class'] ?? null),
            'data-calendar' => $first->format('Y-m'),
        ] + (array)($options['attributes'] ?? []);
        foreach ($messages as $key => $message) {
            $attributes['data-dnd-i18n-' . $key] = $message;
        }

        $html = '<table' . $this->formatAttributes($attributes) . '>'
            . '<caption class="sr-only">' . h($monthLabel) . '</caption><thead><tr>';
        for ($i = 0; $i < 7; $i++) {
            $weekday = $start->addDays($i);
            $html .= '<th scope="col" class="text-muted-foreground border-b px-2 py-2 text-left text-xs font-medium">'
                . '<span aria-hidden="true">' . h($weekday->i18nFormat('EEE')) . '</span>'
                . '<span class="sr-only">' . h($weekday->i18nFormat('EEEE')) . '</span></th>';
        }
        $html .= '</tr></thead><tbody>';
        for ($w = 0; $w < $weeks; $w++) {
            $html .= '<tr>';
            for ($i = 0; $i < 7; $i++) {
                $day = $start->addDays($w * 7 + $i);
                $html .= $this->monthDay($day, $byDay[$day->format('Y-m-d')] ?? [], $month, $today, $categories, $dayUrl);
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    /**
     * One day cell of the month table.
     *
     * @param \Cake\I18n\Date $day Day.
     * @param array<int, array<string, mixed>> $events Normalized events of the day, sorted.
     * @param int $month Month shown (1-12).
     * @param string|null $today Today (`Y-m-d`).
     * @param array<string, string> $categories Category labels.
     * @param callable|null $dayUrl Link builder of a day.
     * @return string
     */
    protected function monthDay(Date $day, array $events, int $month, ?string $today, array $categories, ?callable $dayUrl): string
    {
        $key = $day->format('Y-m-d');
        $inMonth = (int)$day->format('n') === $month;
        $isToday = $key === $today;
        $hidden = max(0, count($events) - self::MONTH_CHIPS);
        $url = $dayUrl !== null ? $this->link($dayUrl($key)) : null;

        $number = '<time datetime="' . h($key) . '" class="inline-flex min-w-6 items-center justify-center rounded-full px-1 text-xs'
            . ($isToday ? ' bg-primary text-primary-foreground font-semibold' : '') . '"' . ($isToday ? ' aria-current="date"' : '') . '>'
            . '<span aria-hidden="true">' . h($day->format('j')) . '</span>'
            . '<span class="sr-only">' . h($day->i18nFormat('d MMMM')) . ($isToday ? ', ' . h(__d('basecoat_theme', 'Today')) : '') . '</span></time>';
        if ($url !== null) {
            $number = '<a href="' . h($url) . '" class="rounded-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">' . $number . '</a>';
        }

        $html = '<td class="h-28 border-t border-l p-1 align-top first:border-l-0' . ($inMonth ? '' : ' bg-muted/40 text-muted-foreground') . '"'
            . ' data-date="' . h($key) . '" data-toggle-item data-id="' . h($key) . '">'
            . '<div class="mb-1 flex justify-end">' . $number . '</div>'
            . '<ul class="flex min-h-16 flex-col gap-0.5" data-dnd-container data-dnd-group="calendar-days" data-dnd-touch="press"'
            . ' data-id="' . h($key) . '" data-dnd-label="' . h($day->i18nFormat('EEEE d MMMM')) . '"'
            . ' data-dnd-var-short="' . h($day->i18nFormat('EEE d MMM')) . '" data-dnd-var-date="' . h($key) . '">';
        foreach ($events as $index => $event) {
            $chip = self::MONTH_CHIP[$event['category'] ?? ''] ?? 'bg-muted text-foreground';
            $label = trim((string)$event['fromText'] . ' ' . $event['title']);
            $html .= '<li data-dnd-item data-id="' . h($event['id']) . '" data-dnd-label="' . h($label) . '"'
                . ' data-dnd-mirror="' . h('[data-upcoming="' . $event['id'] . '"]') . '"'
                . ($index < self::MONTH_CHIPS ? '' : ' data-toggle-for="expanded" data-toggle-hidden="off" hidden') . '>'
                . '<a href="' . h($this->link($event['url'])) . '" class="flex min-w-0 items-center gap-1 rounded px-1.5 py-0.5 text-xs ' . $chip . '"'
                . ' data-dnd-handle data-event="' . h($event['id']) . '" data-category="' . h((string)$event['category']) . '" title="' . h($label) . '">'
                . ($event['fromText'] !== null ? '<span class="shrink-0 font-medium">' . h($event['fromText']) . '</span>' : '')
                . '<span class="truncate">' . h($event['title']) . '</span>'
                . ($event['category'] !== null ? '<span class="sr-only">, ' . h($categories[$event['category']] ?? $event['category']) . '</span>' : '')
                . '</a></li>';
        }
        if ($hidden > 0) {
            $html .= '<li data-toggle-for="expanded" data-toggle-hidden="on">'
                . '<a href="' . h($url ?? '#') . '" class="text-muted-foreground hover:text-foreground block px-1.5 text-xs" data-more="' . h($key) . '" data-toggle="expanded">'
                . '<span aria-hidden="true">' . h(__d('basecoat_theme', '+{0} more', $hidden)) . '</span>'
                . '<span class="sr-only">' . h(__dn('basecoat_theme', '+{0} more event on {1}', '+{0} more events on {1}', $hidden, $hidden, $day->i18nFormat('d MMMM'))) . '</span>'
                . '</a></li>';
        }

        return $html . '</ul></td>';
    }

    /**
     * Day view: a column per resource (plus "Unassigned" when an event has no known resource), hours
     * as rows. Without resources, one column for the day.
     *
     * @param array<int, array<string, mixed>> $events Events.
     * @param array<string, mixed> $options Options.
     * @return string
     * @throws \InvalidArgumentException For an invalid option or event.
     */
    public function day(array $events, array $options = []): string
    {
        $opts = $this->gridOptions($options);
        $date = $opts['date'];
        $events = array_values(array_filter($this->events($events, $opts['slot']), fn (array $e): bool => $e['date'] === $date));
        $current = $date === $opts['today'];
        $day = new Date($date);

        $columns = [];
        if (!$opts['resources']) {
            $columns[] = [
                'id' => $date,
                'label' => h($day->i18nFormat('EEEE d MMMM')),
                'current' => $current,
                'events' => $events,
            ];
        } else {
            $byResource = [];
            $unassigned = [];
            foreach ($events as $event) {
                if ($event['resource'] !== null && isset($opts['resources'][$event['resource']])) {
                    $byResource[$event['resource']][] = $event;
                } else {
                    $unassigned[] = $event;
                }
            }
            $index = 0;
            foreach ($opts['resources'] as $key => $name) {
                $columns[] = [
                    // Resource keys may hold any character: the column id is its position
                    'id' => 'r' . $index++,
                    'label' => h($name),
                    'current' => false,
                    'events' => $byResource[$key] ?? [],
                ];
            }
            if ($unassigned) {
                $columns[] = [
                    'id' => 'unassigned',
                    'label' => h(__d('basecoat_theme', 'Unassigned')),
                    'current' => false,
                    'events' => $unassigned,
                ];
            }
        }
        $label = $opts['label'] ?? __d('basecoat_theme', 'Schedule for {0}', $day->i18nFormat('EEEE d MMMM y'));

        return $this->timeGrid($columns, $opts, 'day', $label, $options);
    }

    /**
     * Dates of the week columns, in `days` order.
     *
     * The week starts on `days[0]`: it is the 7 days from the latest date on or before `$date` that
     * falls on that weekday. Each column is the date of that period on its weekday.
     *
     * @param string $date A date in the week (`Y-m-d`).
     * @param array<int> $days Weekdays, 0 = Sunday … 6 = Saturday.
     * @return array<string> `Y-m-d` dates.
     */
    public function weekDates(string $date, array $days): array
    {
        $day = $this->date($date, 'date');
        $days = $this->days($days);
        $first = $days[0];
        $start = $day->modify(sprintf('-%d days', ((int)$day->format('w') - $first + 7) % 7));

        return array_map(
            fn (int $d): string => $start->modify(sprintf('+%d days', ($d - $first + 7) % 7))->format('Y-m-d'),
            $days,
        );
    }

    /**
     * Validated options of the week and day views.
     *
     * @param array<string, mixed> $options Options.
     * @return array<string, mixed> `start` / `end` in minutes since midnight, the rest as given.
     */
    protected function gridOptions(array $options): array
    {
        $options += $this->getConfig();
        $slot = $options['slot'];
        if (!is_int($slot) || !in_array($slot, self::SLOTS, true)) {
            throw new InvalidArgumentException('Calendar option "slot" must be 15, 30 or 60.');
        }
        $start = $this->minutes($options['start'], 'start');
        $end = $this->minutes($options['end'], 'end');
        foreach (['start' => $start, 'end' => $end] as $name => $value) {
            if ($value % $slot !== 0) {
                throw new InvalidArgumentException(sprintf('Calendar option "%s" must fall on a %d minute slot.', $name, $slot));
            }
        }
        if ($end <= $start) {
            throw new InvalidArgumentException('Calendar option "end" must be after "start".');
        }
        if (!in_array($options['columnWidth'], self::WIDTHS, true)) {
            throw new InvalidArgumentException('Calendar option "columnWidth" must be sm, md or lg.');
        }
        $date = $this->ymd($options['date'] ?? null, 'date');
        $today = isset($options['today']) ? $this->ymd($options['today'], 'today') : null;
        if (isset($options['dayUrl']) && !is_callable($options['dayUrl'])) {
            throw new InvalidArgumentException('Calendar option "dayUrl" must be a callable or null.');
        }

        return [
            'start' => $start,
            'end' => $end,
            'slot' => $slot,
            'days' => $this->days((array)$options['days']),
            'columnWidth' => $options['columnWidth'],
            'date' => $date,
            'today' => $today,
            'resources' => array_map('strval', (array)($options['resources'] ?? [])),
            'categories' => array_map('strval', (array)($options['categories'] ?? [])),
            'dayUrl' => $options['dayUrl'] ?? null,
            'label' => isset($options['label']) ? (string)$options['label'] : null,
            'assets' => (bool)$options['assets'],
        ];
    }

    /**
     * Normalized events.
     *
     * @param array<int, array<string, mixed>> $events Events.
     * @param int $slot Slot length (default length of an event without `end`).
     * @return array<int, array<string, mixed>>
     */
    protected function events(array $events, int $slot): array
    {
        $out = [];
        foreach ($events as $index => $event) {
            if (!is_array($event)) {
                throw new InvalidArgumentException(sprintf('Calendar event #%s must be an array.', $index));
            }
            $where = sprintf('Calendar event %s', is_scalar($event['id'] ?? null) ? '"' . $event['id'] . '"' : '#' . $index);
            if (!is_scalar($event['id'] ?? null) || (string)$event['id'] === '') {
                throw new InvalidArgumentException(sprintf('%s needs an "id".', $where));
            }
            if (!is_scalar($event['title'] ?? null)) {
                throw new InvalidArgumentException(sprintf('%s needs a "title".', $where));
            }
            $date = $this->ymd($event['date'] ?? null, $where . ' "date"');
            $time = $event['time'] ?? null;
            $from = $to = null;
            if ($time !== null) {
                $from = $this->minutes($time, $where . ' "time"');
                if ($from >= 1440) {
                    throw new InvalidArgumentException(sprintf('%s "time" must be before 24:00.', $where));
                }
                $to = isset($event['end']) ? $this->minutes($event['end'], $where . ' "end"') : min(1440, $from + $slot);
                if ($to <= $from) {
                    throw new InvalidArgumentException(sprintf('%s "end" must be after "time".', $where));
                }
            }
            $resource = $event['resource'] ?? null;
            $category = $event['category'] ?? null;
            $out[] = [
                'id' => (string)$event['id'],
                'title' => (string)$event['title'],
                'date' => $date,
                'allDay' => $time === null,
                'from' => $from,
                'to' => $to,
                'fromText' => $from === null ? null : $this->clock($from),
                'toText' => $to === null ? null : $this->clock($to),
                'resource' => is_scalar($resource) ? (string)$resource : null,
                'category' => is_scalar($category) ? (string)$category : null,
                'url' => $event['url'] ?? '#',
            ];
        }

        return $out;
    }

    /**
     * Validated weekdays.
     *
     * @param array<mixed> $days Weekdays.
     * @return array<int>
     */
    protected function days(array $days): array
    {
        $days = array_values($days);
        foreach ($days as $day) {
            if (!is_int($day) || $day < 0 || $day > 6) {
                throw new InvalidArgumentException('Calendar option "days" takes integers 0 (Sunday) to 6 (Saturday).');
            }
        }
        if (!$days || count(array_unique($days)) !== count($days)) {
            throw new InvalidArgumentException('Calendar option "days" needs at least one weekday and no duplicates.');
        }

        return $days;
    }

    /**
     * A date as a validated `Y-m-d` string: strings as given, Cake / PHP date objects formatted.
     *
     * @param mixed $value Date.
     * @param string $field Name for the error message.
     * @return string
     */
    protected function ymd(mixed $value, string $field): string
    {
        if ($value instanceof DateTimeInterface || $value instanceof ChronosDate) {
            return $value->format('Y-m-d');
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be a Y-m-d date, got %s.', $this->field($field), get_debug_type($value)));
        }
        $this->date($value, $field);

        return $value;
    }

    /**
     * Parses a `Y-m-d` date.
     *
     * @param string $value Date.
     * @param string $field Name for the error message.
     * @return \DateTimeImmutable
     */
    protected function date(string $value, string $field): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException(sprintf('%s must be a Y-m-d date, got "%s".', $this->field($field), $value));
        }

        return $date;
    }

    /**
     * Minutes since midnight of an `H:i` time (`24:00` allowed; also `H:i:00` and time objects).
     *
     * @param mixed $value Time.
     * @param string $field Name for the error message.
     * @return int
     */
    protected function minutes(mixed $value, string $field): int
    {
        if ($value instanceof DateTimeInterface || $value instanceof ChronosTime) {
            $value = $value->format('H:i');
        } elseif (is_string($value) && preg_match('/^(\d\d:\d\d):00$/', $value, $m)) {
            // TIME columns come as H:i:s; whole minutes only
            $value = $m[1];
        }
        if (!is_string($value) || !preg_match('/^(?:([01]\d|2[0-3]):([0-5]\d)|24:00)$/', $value, $m)) {
            throw new InvalidArgumentException(sprintf('%s must be an H:i time (00:00 to 24:00).', $this->field($field)));
        }

        return $value === '24:00' ? 1440 : (int)$m[1] * 60 + (int)$m[2];
    }

    /**
     * `H:i` of minutes since midnight.
     *
     * @param int $minutes Minutes since midnight.
     * @return string
     */
    protected function clock(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Field name for error messages: option names get quoted, event fields already are.
     *
     * @param string $field Field.
     * @return string
     */
    private function field(string $field): string
    {
        return str_contains($field, '"') ? $field : sprintf('Calendar option "%s"', $field);
    }
}
