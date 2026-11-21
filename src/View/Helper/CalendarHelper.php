<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use DateTimeImmutable;
use InvalidArgumentException;

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
        $this->events($events, $opts['slot']);
        $this->weekDates($opts['date'], $opts['days']);

        return '';
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
        $date = $options['date'] ?? null;
        if (!is_string($date)) {
            throw new InvalidArgumentException('Calendar option "date" (Y-m-d) is required.');
        }
        $this->date($date, 'date');
        $today = $options['today'] ?? null;
        if ($today !== null) {
            $this->date(is_string($today) ? $today : '', 'today');
        }
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
            $date = $event['date'] ?? null;
            $this->date(is_string($date) ? $date : '', $where . ' "date"');
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
     * Minutes since midnight of an `H:i` time (`24:00` allowed).
     *
     * @param mixed $value Time.
     * @param string $field Name for the error message.
     * @return int
     */
    protected function minutes(mixed $value, string $field): int
    {
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
