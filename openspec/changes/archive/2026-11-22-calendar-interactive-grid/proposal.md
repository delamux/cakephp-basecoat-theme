## Why

`CalendarHelper::week()` and `day()` render read-only time grids: each day (week) or resource (day) is its own CSS
grid, so `basecoat-dnd.js`, which already moves and resizes items inside **one** 2D slot grid, cannot drive them.
Apps that schedule things (the new-crossfit gym moves, stretches and re-assigns classes, and creates new ones in empty
slots) need the week and day views to be interactive, with the same keyboard model, announcements and cancelable
events the month view and the baked screens already have. They also need finer steps than 15 minutes.

## What Changes

- New `interactive` option for `week()` and `day()` (off by default):
  - `interactive.move`: every timed event becomes a `data-dnd-item` of a single 2D grid (rows = slots, columns =
    days or resources), so it can be moved to another slot and another column with the pointer or the keyboard;
  - `interactive.resize`: every timed event gets a bottom `data-dnd-resize` handle to change its end;
  - `interactive.slots`: with a `slotUrl` callback (`fn (string $date, string $time, ?string $resource): string|array`),
    every empty slot becomes a `data-dnd-slot` link ("create here"), which is also a drop target;
  - per-event opt-out: an event with `'locked' => true` is rendered without drag or resize hooks (e.g. past events).
- `basecoat:move` / `basecoat:resize` on these grids carry the target date, start, end and resource as well as the
  row/column indexes (`detail.slot = {date, start, end, resource}`), so apps don't map indexes themselves.
- `slot` accepts 5, 10, 15, 20, 30 and 60 minutes (today 15, 30, 60), with row classes for up to 288 rows (24 h at
  5 minutes) and background lines for each step.
- Event markup hooks for consumers: an `eventAttributes` callback (`fn (array $event): array`) adds attributes to each
  event's link (e.g. `hx-get`, `data-*` for a menu trigger) without overriding `eventLink()`.

Backwards compatible: with `interactive` unset the rendered HTML is byte-for-byte what the current release renders
(new slots only add classes; the existing 15/30/60 output is unchanged).

Consumers: new-crossfit adopts it by updating the package and passing `interactive` (plus `slotUrl` and `locked`) in
its class calendar; its `ClassCalendarHelper` keeps working (it overrides `eventLink()` and `day()`, both kept).

## Non-goals

- Saving anything: the plugin only moves the DOM and fires events (and POSTs to `data-endpoint` when the app sets
  one); confirmation dialogs and persistence are the app's job.
- Changes to the month view (it already supports drag and drop) and dragging all-day events.
- Recurring events, multi-day events, time zones.
- A JS dependency or a build step.

## Capabilities

### New Capabilities
- `calendar`: the Calendar helper's interactive week and day grids (drag, resize, slot links, locked events, finer
  steps, event attribute callback) and the events they fire.

### Modified Capabilities
<!-- none: the plugin has no specs yet -->

## Impact

- `src/View/Helper/CalendarHelper.php` (options, single-grid renderer for interactive mode, slot validation),
  `webroot/css/basecoat-calendar.css` (one-grid layout, row classes up to 288, 5/10/20-minute backgrounds, lane width
  classes, handles), `webroot/js/basecoat-dnd.js` (slot values in event details).
- Tests: `tests/TestCase/View/Helper/CalendarHelperTest.php`, `tests/js/dnd.test.js`.
- Docs: README (Calendar section, drag and drop hooks), component catalog, locales (new strings).
