## 1. Safety net and steps

- [ ] 1.1 Snapshot test: `week()` and `day()` without `interactive` render byte-for-byte the current output for the existing fixtures (15, 30, 60)
- [ ] 1.2 Failing test → green: `slot` accepts 5, 10, 20 (and keeps 15, 30, 60); any other value throws the new message; a 06:00–22:00 day at 5 minutes renders `bc-cal-rows-192`
- [ ] 1.3 Script in `bin/` that generates the row, span and rows-N classes up to 288 and the `bc-cal-slot-5/10/20` backgrounds into `webroot/css/basecoat-calendar.css`; extend `ROWS` / `ROW` / `SPAN` maps

## 2. Interactive grid (PHP)

- [ ] 2.1 Failing test → green: `interactive.move` renders the single `data-dnd-grid` body with rows, cols, class maps, row texts, column texts and column values (week: dates; day: resource keys plus "" for Unassigned)
- [ ] 2.2 Failing test → green: timed, unlocked events become `data-dnd-item` with 0-based row, col, span and `data-dnd-touch="press"`; all-day, outside-hours and overflow events keep today's header placement
- [ ] 2.3 Failing test → green: overlapping events keep their lane as width classes in the single grid
- [ ] 2.4 Failing test → green: `interactive.resize` adds the labelled `data-dnd-resize` handle
- [ ] 2.5 Failing test → green: `interactive.slots` + `slotUrl` render slot links (label with day, resource and time) and non-link slots when `slotUrl` returns null; slots below events
- [ ] 2.6 Failing test → green: `locked` events render without dnd hooks
- [ ] 2.7 Failing test → green: `eventAttributes` merges attributes into event links, refusing `on*` and `style`

## 3. Script and CSS

- [ ] 3.1 Failing `node --test` → green: `basecoat:move` / `basecoat:resize` carry `detail.slot = {start, end, column}` when the grid has row texts and column values; `preventDefault()` reverts and posts nothing
- [ ] 3.2 CSS for the single grid: `bc-cal-cols-N`, `bc-cal-c-N`, header row aligned with the body columns, slot cells, handle, lane widths, drag states (works with `interactivity.css`)

## 4. Docs and release

- [ ] 4.1 README: Calendar section (`interactive`, `slotUrl`, `locked`, `eventAttributes`, steps, event details) and drag and drop hooks table (`data-dnd-col-values`, `detail.slot`)
- [ ] 4.2 Component catalog example of an interactive week and day (`composer docs`); new locale strings in resources/locales
- [ ] 4.3 Browser check (pointer, keyboard, touch long press, light and dark, without JS)
- [ ] 4.4 `composer test` and `composer test-js` green; tag a minor release and tell the new-crossfit change `interactive-class-calendar` (task 0.3)
