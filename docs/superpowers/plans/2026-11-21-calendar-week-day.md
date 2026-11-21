# Calendar week and day views Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** a `CalendarHelper` with month, week and day views, and the `calendar` bake screen rebuilt on
it with view and date navigation.

**Architecture:**
- The PHP helper renders everything on the server.
- Week and day share one time-grid renderer. Its columns are flex children, and each column body is a
  CSS grid with one row per slot.
- Events are placed with fixed classes from a plugin stylesheet (`bc-cal-*`), with no inline styles and
  no runtime Tailwind.
- `month()` moves the current screen's month table into the helper, unchanged.

**Tech Stack:**
- CakePHP 5 helpers (`AbstractComponentHelper`);
- `Cake\I18n\Date`/`DateTime` `i18nFormat`;
- plain CSS on the Basecoat tokens;
- PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-21-calendar-week-day-design.md`. Read it first; the section
"Calls made after the builder review" is binding.

## Global Constraints

- **Sample data:** all of it is invented, in the Fernway world. Never reference or copy any paid
  template or kit, or FullCalendar.
- **CSP:**
  - no `style=` attributes and no inline `<script>` in any rendered HTML;
  - no Tailwind class built from a variable (`'row-start-' . $n` is forbidden). Every class appears as
    a literal string in `src/` or in the stylesheet.
- **Weekdays:** `0` = Sunday … `6` = Saturday. The order of `days` is the column order.
- **Defaults:**
  - `days` = `[1,2,3,4,5,6,0]`
  - `start` = `'08:00'`, `end` = `'20:00'`; `end` may be `'24:00'`
  - `slot` ∈ {15, 30, 60}, default 30
  - `columnWidth` ∈ {`sm`, `md`, `lg`}, default `md`
- **Clock:** the helper never reads the clock; "today" comes only from the `today` option.
- **Errors:** every bad date, time or option throws `InvalidArgumentException`, with a message that names
  the field.
- **Strings:**
  - helper strings use `__d('basecoat_theme', …)` / `__dn(...)`;
  - every new msgid goes into `resources/locales/basecoat_theme.pot` and
    `resources/locales/es/basecoat_theme.po` (`tests/TestCase/I18nTest.php` enforces this);
  - screen strings use the Twig `t`/`tn` pattern of the bake templates.
- **Assets:** with `assets` (default true), the helper adds `BasecoatTheme.basecoat-calendar` to the
  `css` block (`['block' => true]`), as `FlowHelper::editor()` does.
- **Week and day are read-only:** no `data-dnd-*` attributes in them.

## Review Focus

1. **A `date` whose weekday is not in `days`.** Saturday 2026-11-21 with `[1,2,3,4,5]` still shows
   Mon 16 – Fri 20 (Task 1).
2. **Sunday-first weeks** (Task 1):
   - `[0,1,2,3,4,5,6]` with date Saturday 2026-11-21 shows Sun 15 – Sat 21;
   - with date Sunday 2026-11-22 it shows Sun 22 – Sat 28.
3. **Full day.** `start` `00:00`, `end` `24:00` and `slot` 60 give 24 rows. A 23:00–24:00 event sits in
   row 24 with span 1 (Task 2).
4. **Events dated outside the shown period** never appear, not even in the "outside hours" count
   (Task 2).
5. **`dayUrl` and escaping.** A `dayUrl` that returns a routing array is built through the Url helper.
   Titles, resource labels and URLs are escaped (Tasks 2 and 3).

---

### Task 1: Helper skeleton, event and option validation, week period

**Files:**
- Create: `src/View/Helper/CalendarHelper.php`
- Modify: `src/View/BasecoatHelpers.php`. Add `'Calendar' => 'BasecoatTheme.Calendar'` in alphabetical
  order, after `ButtonGroup`.
- Test: `tests/TestCase/View/Helper/CalendarHelperTest.php` (extends `HelperTestCase`)

**Interfaces:**
- Produces:
  - `public function weekDates(string $date, array $days): array`. Returns a list of `Y-m-d`, one per
    entry of `days`, in `days` order.
  - `protected function events(array $events, int $slot): array`. Normalizes each event to:
    ```php
    ['id' => string, 'title' => string, 'date' => 'Y-m-d', 'allDay' => bool, 'from' => ?int, 'to' => ?int,
     'fromText' => ?string, 'toText' => ?string, 'resource' => ?string, 'category' => ?string, 'url' => string|array]
    ```
    - `from` and `to` are minutes since midnight. A missing `end` gives `to` = `from` + slot.
    - `fromText` and `toText` are `H:i`.
    - `url` defaults to `'#'`.
  - `protected function gridOptions(array $options): array`. Returns the validated options: `start` and
    `end` (as minutes), `slot`, `days`, `columnWidth`, `date`, `today`, `resources`, `categories`,
    `dayUrl`, `label` and `assets`.
- `_defaultConfig`: `['days' => [1,2,3,4,5,6,0], 'start' => '08:00', 'end' => '20:00', 'slot' => 30, 'columnWidth' => 'md', 'assets' => true]`.

- [ ] **Step 1: Write the failing tests**

```php
public function testWeekDatesFollowDaysOrder(): void
{
    $h = new CalendarHelper($this->View);
    $this->assertSame(['2026-11-16','2026-11-17','2026-11-18','2026-11-19','2026-11-20'], $h->weekDates('2026-11-18', [1,2,3,4,5]));
    $this->assertSame(['2026-11-16','2026-11-17','2026-11-18','2026-11-19','2026-11-20'], $h->weekDates('2026-11-21', [1,2,3,4,5]), 'Saturday outside days: still that week');
    $this->assertSame('2026-11-15', $h->weekDates('2026-11-21', [0,1,2,3,4,5,6])[0]);
    $this->assertSame('2026-11-22', $h->weekDates('2026-11-22', [0,1,2,3,4,5,6])[0]);
    $this->assertSame(['2026-11-20','2026-11-16'], $h->weekDates('2026-11-18', [5,1]), 'Column order is days order, within the week starting on days[0]');
}

#[DataProvider('badOptions')]
public function testBadOptionsThrow(array $options, string $field): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/' . preg_quote($field, '/') . '/');
    (new CalendarHelper($this->View))->week([], $options + ['date' => '2026-11-18']);
}

public static function badOptions(): array
{
    return [
        'days empty' => [['days' => []], 'days'],
        'days out of range' => [['days' => [1, 7]], 'days'],
        'days duplicate' => [['days' => [1, 1]], 'days'],
        'slot' => [['slot' => 20], 'slot'],
        'start off grid' => [['start' => '08:10'], 'start'],
        'end before start' => [['start' => '10:00', 'end' => '09:00'], 'end'],
        'end 25:00' => [['end' => '25:00'], 'end'],
        'bad date' => [['date' => '2026-02-30'], 'date'],
        'column width' => [['columnWidth' => 'xl'], 'columnWidth'],
    ];
}
```

Also add `testBadEventsThrow`, one data-provider case each:
- event `date` `'2026-13-01'`;
- `time` `'9:00'`;
- `time` `'09:00'` with `end` `'08:00'`;
- a missing `title`.

The message must name the field.

- [ ] **Step 2: Run the tests and see them fail**

Run: `vendor/bin/phpunit --filter CalendarHelperTest`
Expected: FAIL. `CalendarHelper` does not exist yet.

- [ ] **Step 3: Implement the skeleton.** `week()` validates and may return `''` for now.
  - **Week dates:** take the latest date ≤ `date` whose `format('w')` is `days[0]`. Each column date is
    then `periodStart + ((d - days[0] + 7) % 7)` days.
  - **Time pattern:** `^([01]\d|2[0-3]):[0-5]\d$|^24:00$`.
  - **Dates:** parse with `!Y-m-d` and check that it round-trips.

- [ ] **Step 4: Run the tests and see them pass**

Run: `vendor/bin/phpunit --filter CalendarHelperTest`
Expected: PASS

- [ ] **Step 5: Commit**: `feat(calendar): CalendarHelper skeleton, validation and week period`

### Task 2: Time grid and week view, plus the stylesheet

**Files:**
- Modify: `src/View/Helper/CalendarHelper.php`
- Create: `webroot/css/basecoat-calendar.css`
- Modify: the locales (pot and es po) for the new strings
- Test: `tests/TestCase/View/Helper/CalendarHelperTest.php`

**Interfaces:**
- Consumes: `events()`, `gridOptions()` and `weekDates()` from Task 1.
- Produces:
  - `public function week(array $events, array $options = []): string`
  - `protected function timeGrid(array $columns, array $opts, string $view): string`, where each column is
    `['id' => string, 'label' => string (HTML), 'name' => string (plain, accessible), 'current' => bool, 'events' => list<normalized event>]`
  - `protected function lanes(array $events): array`. Returns the events with `lane` (1…) and `lanes`
    (the size of their overlap cluster).

**Markup contract (the tests assert these literal fragments):**
- **Root:** `<section class="bc-cal bc-cal-week bc-cal-w-md{ class}" aria-label="{label}" data-calendar-view="week" data-calendar-date="{date}">`.
  - The default label is `Week of {0}`, with the first date in long form.
  - It contains `<div class="bc-cal-scroll" role="region" tabindex="0" aria-label="{label}">`.
- **Hours column:** a leading `<div class="bc-cal-times" aria-hidden="true">`, with one
  `<span class="bc-cal-hour bc-cal-row-N">HH:00</span>` per full hour.
- **One column per entry:** `<div class="bc-cal-col" role="group" aria-labelledby="{id}">`, containing:
  - **Header:** `<div class="bc-cal-head" id="{id}"[ aria-current="date"]>`.
    - In the week view it shows `EEE d` visibly and `EEEE d MMMM` in `sr-only`.
    - It is wrapped in `<a href>` when `dayUrl` is set.
    - It also holds the optional overflow and outside-hours `<details>` (below).
  - **All-day row:** `<ul class="bc-cal-allday">`.
    - It is present in every column when any shown column has an all-day event.
    - Items use the `bc-cal-event` link markup, without row classes.
  - **Body:** `<ol class="bc-cal-body bc-cal-rows-{rows}">`.
    - Each placed event is
      `<li class="bc-cal-event bc-cal-row-{r} bc-cal-span-{s} bc-cal-lanes-{k} bc-cal-lane-{l} bc-cal-c-{category|none}[ bc-cal-cut-start][ bc-cal-cut-end]"><a href="…">…</a></li>`.
    - `r` is 1-based (slot index + 1). `s` is the number of rows covered after clipping.
- **Event link content:**
  - visible: `<span class="bc-cal-time">{fromText}–{toText}</span>` (or `All day`),
    `<span class="bc-cal-title">{title}</span>` and `<span class="bc-cal-meta">{resource label}</span>`
    (the category label in the day view);
  - then `<span class="sr-only">, {EEEE d MMMM}[, {resource}][, {category}]</span>`;
  - the real times are always shown, even when the event is clipped.
- **Lanes:**
  - Overlapping events in a column get lanes 1…n, assigned greedily.
  - An event with lane > 4 is not placed. It goes into
    `<details class="bc-cal-more"><summary>+{n} more</summary><ul>links</ul></details>` in the header.
  - `bc-cal-lanes-*` is capped at 4.
- **Outside hours:** an event on a shown date that ends ≤ `start` or starts ≥ `end` goes into
  `<details class="bc-cal-outside"><summary>{n} outside hours</summary><ul>links</ul></details>`
  (`__dn` plural).
- **Rounding:** `from` is floored to the slot and `to` is ceiled. Both are then clipped to
  `start`–`end`, which adds `bc-cal-cut-start` / `bc-cal-cut-end`.
- **Stylesheet:**
  - **Rows:**
    - `.bc-cal-row-1`…`96` → `grid-row-start`;
    - `.bc-cal-span-1`…`96` → `grid-row-end: span N`;
    - `.bc-cal-rows-N` (N = 1…96) → `grid-template-rows: repeat(N, var(--bc-cal-row))`.
  - **Lanes:** the lane pairs `.bc-cal-lanes-K.bc-cal-lane-L` (K = 1…4) sit on a 12-column body grid.
  - **Widths:** `.bc-cal-w-sm|md|lg` set `--bc-cal-col` to 9, 12 and 16rem. Columns use
    `flex: 1 0 var(--bc-cal-col)`.
  - **Scrolling:** `.bc-cal-scroll` uses `overflow: auto` and `max-height: min(75vh, 56rem)`.
  - **Sticky:** `.bc-cal-head` uses `position: sticky; top: 0` and `.bc-cal-times` uses
    `position: sticky; left: 0`, both on the `--background` colour.
  - **Cut events** get a dashed top or bottom border.
  - **Colours:** `.bc-cal-c-meeting|deadline|release|team|none` use Basecoat tokens or `color-mix()` of
    the Tailwind palette variables, in light and dark (a `.dark` ancestor).

- [ ] **Step 1: Write the failing tests**
  - **`testWeekStructureAndAssets`**:
    - the root and scroll fragments are present;
    - 5 `bc-cal-col` for `[1,2,3,4,5]`;
    - no `style=` and no `<script`;
    - `$this->View->fetch('css')` contains `basecoat-calendar.css`, and does not with `assets => false`.
  - **`testEventPlacement`**:
    - with start 08:00 and slot 30, a 09:00–10:30 event gets `bc-cal-row-3 bc-cal-span-3`;
    - a 09:10–09:40 event gets `bc-cal-row-3 bc-cal-span-2`, and its text shows `09:10–09:40`.
  - **`testClippingMarksCut`**: 07:30–09:00 gets `bc-cal-row-1 bc-cal-span-2 … bc-cal-cut-start`, and
    19:30–21:00 gets `bc-cal-cut-end`.
  - **`testFullDayWith24`**: start `00:00`, end `24:00` and slot 60 give `bc-cal-rows-24`. A 23:00–24:00
    event gets `bc-cal-row-24 bc-cal-span-1`.
  - **`testOutsideHoursAndOtherWeeksAreNotPlaced`**: a 06:00–07:00 event on a shown day gives
    `1 event outside hours`. An event on 2026-11-25 is absent everywhere.
  - **`testLanes`**: three overlapping events get `bc-cal-lanes-3` with lanes 1, 2 and 3. Six
    overlapping events give 4 placed and `+2 more` in the details.
  - **`testAllDayRow`**:
    - an event with `time => null` sits in `bc-cal-allday` with the text `All day`;
    - without all-day events there is no `bc-cal-allday`.
  - **`testTodayAndDayUrl`**:
    - `today` puts `aria-current="date"` on exactly one head;
    - a `dayUrl` closure returning `['controller' => 'Calendar', 'action' => 'index', '?' => ['view' => 'day', 'date' => $d]]`
      gives `href="/calendar?view=day&amp;date=2026-11-18"`;
    - this needs the same router setup as `CalendarScreenTest::setUp`.
  - **`testEscaping`**: a title and a resource label containing `<b>&` are escaped.
  - **`testStylesheetCoversAllRowsAndSpans`**: `webroot/css/basecoat-calendar.css` contains
    `.bc-cal-row-96`, `.bc-cal-span-96`, `.bc-cal-rows-96` and `.bc-cal-lanes-4.bc-cal-lane-4`.

- [ ] **Step 2: Run the tests and see the new ones fail.** Run:
  `vendor/bin/phpunit --filter CalendarHelperTest`.

- [ ] **Step 3: Implement `week()`, `timeGrid()` and `lanes()`.**
  - The repetitive rules of the stylesheet may come from a one-off generator script kept in the
    scratchpad, not committed. The committed CSS is static.
  - Add the msgids to the pot and the es po.

- [ ] **Step 4: Run the tests**

Run: `vendor/bin/phpunit --filter 'CalendarHelperTest|I18nTest'`
Expected: PASS

- [ ] **Step 5: Commit**: `feat(calendar): week view on a server-rendered time grid`

### Task 3: Day view with resource columns

**Files:**
- Modify: `src/View/Helper/CalendarHelper.php` and the locales
- Test: `tests/TestCase/View/Helper/CalendarHelperTest.php`

**Interfaces:**
- Consumes: `timeGrid()` from Task 2.
- Produces: `public function day(array $events, array $options = []): string`:
  - **Root:** class `bc-cal bc-cal-day …` and `data-calendar-view="day"`.
  - **Label:** defaults to `Schedule for {0}` (`EEEE d MMMM y`).
  - **Columns:**
    - one column per `resources` entry; the header is the resource label, with no link;
    - each event goes in its resource's column;
    - missing or unknown resources go into a final `Unassigned` column, present only when needed;
    - with empty `resources`, there is a single column headed with the date.
  - **Event meta:** the category label.

- [ ] **Step 1: Write the failing tests**
  - **`testDayColumnsAreResources`**: 3 resources give 3 `bc-cal-col` in the given order, and each event
    sits inside its resource's column.
  - **`testUnassignedOnlyWhenNeeded`**: there is no `Unassigned` column when every resource is known.
    `resource => 'ghost'` adds it as the last column.
  - **`testDayWithoutResources`**: there is one column.
  - **`testDayShowsOnlyThatDate`**: events of other dates are absent.
  - **`testDayMetaIsCategory`**.

- [ ] **Step 2: Run the tests and see them fail.** Run:
  `vendor/bin/phpunit --filter CalendarHelperTest`.
- [ ] **Step 3: Implement `day()`.**
- [ ] **Step 4: Run the tests and see them pass.** Run:
  `vendor/bin/phpunit --filter 'CalendarHelperTest|I18nTest'`.
- [ ] **Step 5: Commit**: `feat(calendar): day view with a column per resource`

### Task 4: `month()` moves into the helper; the screen gets views and navigation

**Files:**
- Modify: `src/View/Helper/CalendarHelper.php` and the locales (the month's drag messages and the
  `+{0} more` strings move to the plugin domain)
- Modify: `templates/bake/Screen/calendar/template.twig`
- Modify: `templates/bake/Screen/calendar/data.php`
- Modify: `src/Bake/ScreenRegistry.php` (the calendar description)
- Test: `tests/TestCase/View/Helper/CalendarHelperTest.php` and `tests/TestCase/Bake/CalendarScreenTest.php`

**Interfaces:**
- Produces: `public function month(array $events, array $options = []): string`.
  - **Output:** the exact `<table … data-calendar="Y-m" …>…</table>` the template renders today. Keep
    the same classes, `data-dnd-*` hooks, chips, `+N more` toggle, 3 chips per day, Monday first and
    the colour palette.
  - **Options:**
    - `date` (the month that contains it);
    - `today`;
    - `categories`;
    - `dayUrl` (wraps the day number in a link, only when set);
    - `label`;
    - `class`;
    - `attributes` (merged onto `<table>`; used for `data-endpoint`);
    - `assets`.
  - The table wrapper and the side panels stay in the screen.
- Screen view variables: `year`, `month`, `today`, `categories`, `events`, and the new ones:
  - `resources` (id ⇒ name), `days`, `dayStart`, `dayEnd`;
  - `events` gain `end`, `resource` and `url`;
  - old data without the new keys must still render the month.

**Screen behaviour:**
- **View:** `$view` = `?view` when it is in `month|week|day`, otherwise `month`.
- **Date.** `$date` takes the first of these that applies, each one validated:
  1. `?date`;
  2. `?month` (`Y-m`) → `Y-m-01`;
  3. in the month view, when `$year`/`$month` are set, `sprintf('%04d-%02d-01')`;
  4. otherwise `$today`.
- **Navigation:**
  - every link is `['action' => 'index', '?' => ['view' => $view, 'date' => …]]`;
  - previous / next move by ±1 month (on the 1st), ±7 days or ±1 day, depending on the view;
  - Today links to `['?' => ['view' => $view]]`.
- **View switch:** links to `['?' => ['view' => X, 'date' => $date]]`, with `aria-current="true"` on the
  active one.
- **Heading:**
  - month: `LLLL y`;
  - week: `{first d MMM} – {last d MMM y}`;
  - day: `EEEE d MMMM y`.
- **Helper calls:**
  - the views use `dayUrl = fn ($d) => ['action' => 'index', '?' => ['view' => 'day', 'date' => $d]]`;
  - the week uses `days => $days ?? [1,2,3,4,5]`, `start => $dayStart ?? '08:00'` and
    `end => $dayEnd ?? '20:00'`.
- **Side panels:** Upcoming and Categories stay in every view.
- **Sample data:**
  - `resources`: `elena` ⇒ Elena Sarto, `kofi` ⇒ Kofi Mensah, `ines` ⇒ Ines Duarte, `pavel` ⇒ Pavel
    Novak, `noor` ⇒ Noor Haddad.
  - Every existing event gets a resource and an `end` one hour after `time`. Deadlines keep
    `time => null`.
  - Add ids 17–23, on Mon 16 – Fri 20 Nov 2026, including:
    - two overlapping events on the 18th;
    - one all-day event;
    - one event from 07:30 to 09:00 (clipped at 08:00);
    - one event on the 20th for a resource that has nothing else that day.
  - Set `days => [1,2,3,4,5]`, `dayStart => '08:00'` and `dayEnd => '20:00'`.
  - The docblock lists every variable.
- **Registry description:** "Calendar with month, week and day views: categorised events, hours as rows,
  a column per person in the day view and an upcoming list."

- [ ] **Step 1: Pin the month output before moving it.**
  - Render the current screen with the sample data.
  - Keep its `<table…</table>` fragment as a fixture string in
    `CalendarHelperTest::testMonthMatchesTheScreenTable`.
  - The test asserts that
    `month($events, ['date' => '2026-11-01', 'today' => '2026-11-20', 'categories' => …])` gives the same
    output, after normalizing whitespace between tags.
- [ ] **Step 2: Update `CalendarScreenTest`.**
  - Replace `testViewSwitchIsInertAndNamed` with `testViewSwitchLinks`.
  - Update `testNavigationIsGetAndKeepsTheMonth` to the `view`/`date` links. It must also assert that
    `?month=2026-12` still renders December.
  - Add:
    - `testWeekView` (`?view=week&date=2026-11-18`): 5 columns Mon 16 – Fri 20, each linking to the day
      view;
    - `testDayView` (`?view=day&date=2026-11-18`): 5 resource columns;
    - `testInvalidQueryFallsBack` (`?view=year&date=nope`): renders the month of `today`;
    - `testOldDataStillRendersMonth`: data without `resources`, `days` or `dayStart`.
  - Keep the CSP, escaping, clock and third-party tests passing.
- [ ] **Step 3: Run the tests and see them fail.** Run:
  `vendor/bin/phpunit --filter 'CalendarHelperTest|CalendarScreenTest'`.
- [ ] **Step 4: Implement.**
  - Move the month logic into `month()` and replace the screen's table with the helper call.
  - Rewrite the toolbar, the view switch and the data.
  - Update `ScreenRegistry`.
- [ ] **Step 5: Run the full suite.**
  - Run `vendor/bin/phpunit`. Expected: OK.
  - If `ScreenRegistryTest` or `ScreenTranslationsTest` assert the old text, update their expectations
    to the new description and strings.
- [ ] **Step 6: Commit**: `feat(bake): calendar screen with month, week and day views`

### Task 5: Catalog, README and upgrade notes

**Files:**
- Create: `docs/catalog/examples/calendar/01-month.php`, `02-week.php`, `03-day.php`:
  - use the `@title` / `@description` docblock and the `// ---` separator, as in
    `docs/catalog/examples/flow/02-readonly.php`;
  - invented Fernway data;
  - the week shows Mon–Fri, 08:00–20:00, with `columnWidth` `sm`;
  - the day shows 4 resources and one unassigned event.
- Modify: `docs/catalog/helpers.php`. Under `'Data display'`, add
  `'Calendar' => ['Calendar', 'Month, week and day calendar views: hours as rows, a column per day in the week and per person or company in the day; no JavaScript needed.']`.
- Modify: `README.md`:
  - **New `### Calendar` helper section:**
    - data format;
    - options with their defaults;
    - the three views;
    - linking week ↔ day with `dayUrl`;
    - overflow and outside-hours events;
    - styling with `basecoat-calendar.css` and the `bc-cal-*` classes;
    - CSP.
  - **Screen list, `calendar` entry:** mention `?view=` and `?date=`; `?month=` is still accepted.
  - **Helpers table, `calendar` row:** `PageHeader`, `Calendar`, `Badge`, `Button`.
  - **Upgrade notes:** re-bake `calendar` to get the new views; old data still works for the month.
  - **Helper count:** 35 → 36.
- Test: `tests/TestCase/Docs/CatalogBuilderTest.php`. Assert that `css/basecoat-calendar.css` is copied,
  as the flow css is.

- [ ] **Step 1: Add the catalog assertion and see it fail.** Run:
  `vendor/bin/phpunit --filter CatalogBuilderTest`.
- [ ] **Step 2: Write the examples, the catalog entry and the README.**
- [ ] **Step 3: Run everything.** Run `vendor/bin/phpunit`, `node --test tests/js/*.test.js` and
  `php bin/build-gh-pages --strict`. Expected: all OK, and the catalog has 81 pages.
- [ ] **Step 4: Commit**: `docs: calendar helper, catalog examples and upgrade notes`

### Task 6 (controller): browser check

Serve the built catalog and a baked screen, then check:
- light and dark, at 390px and 1280px;
- horizontal scroll, with the hour column and the header staying fixed;
- the week headers link to the day view;
- keyboard tabbing through the events;
- the `<details>` overflow;
- a clean console.

Fix anything you find, run the suite again, and commit as `fix(calendar): …`.
