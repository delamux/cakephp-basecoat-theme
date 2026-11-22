## ADDED Requirements

### Requirement: Read-only output unchanged
The Calendar helper SHALL render `week()` and `day()` exactly as before when the `interactive` option is not given, for every slot accepted before (15, 30, 60).

#### Scenario: Same HTML without the option
- **GIVEN** the events and options of the existing week and day tests
- **WHEN** `week()` and `day()` render without `interactive`
- **THEN** the output is identical to the current release's output

### Requirement: Interactive week and day grid
The Calendar helper SHALL, when `interactive.move` is true, render the body of `week()` and `day()` as a single element with `data-dnd-grid`, `data-dnd-rows` (number of slots), `data-dnd-cols` (days or resources), `data-dnd-classes` (row, column and span class maps), `data-dnd-row-text` (slot boundaries as `HH:MM`, rows + 1 entries), `data-dnd-col-text` (column labels), `data-dnd-col-values` (`Y-m-d` dates in the week, resource keys in the day, empty for "Unassigned") and `data-dnd-resize-axis="row"`, and every timed, unlocked event as a `data-dnd-item` with `data-dnd-row`, `data-dnd-col`, `data-dnd-span` (0-based) and `data-dnd-touch="press"`.

#### Scenario: Week with one event
- **GIVEN** `week()` with `date` 2026-11-18, `start` 07:00, `end` 09:00, `slot` 15, `interactive` `['move' => true]` and one event on 2026-11-17 07:30–08:15
- **WHEN** it renders
- **THEN** the grid has `data-dnd-rows="8"`, `data-dnd-cols="7"`, `data-dnd-col-values` starting with "2026-11-16" and the event has `data-dnd-row="2"`, `data-dnd-col="1"`, `data-dnd-span="3"`

#### Scenario: Day with resources
- **GIVEN** `day()` with resources `['l' => 'Luis A.', 'a' => 'Ana B.']` and an event without resource
- **WHEN** it renders with `interactive.move`
- **THEN** `data-dnd-col-values` is `["l","a",""]` and the event is in column 2 ("Unassigned")

### Requirement: Resize handle
The Calendar helper SHALL, when `interactive.resize` is true, render inside every timed, unlocked event a `data-dnd-resize` handle at its bottom edge, labelled "Change the end of <title>".

#### Scenario: Handle rendered
- **WHEN** `week()` renders an event "Yoga" with `interactive` `['move' => true, 'resize' => true]`
- **THEN** the event contains an element with `data-dnd-resize` and `aria-label="Change the end of Yoga"`

### Requirement: Slot links
The Calendar helper SHALL, when `interactive.slots` is true and `slotUrl` is given, render every slot cell of the grid as a link to `slotUrl(date, time, resource)` with `data-dnd-slot`, `data-dnd-row`, `data-dnd-col` and a `data-dnd-label` naming the day (and the resource in the day view) and the time; a cell whose `slotUrl` returns `null` SHALL be a non-link `data-dnd-slot` element. Slot cells SHALL sit below the events in stacking order.

#### Scenario: Slot link in the day view
- **GIVEN** `day()` of 2026-11-24 with resource `l` => "Luis A." and `slotUrl` returning `/add?date={date}&start={time}&coach={resource}`
- **WHEN** it renders with `interactive.slots`
- **THEN** the 10:00 cell of Luis A.'s column is a link to `/add?date=2026-11-24&start=10:00&coach=l` labelled "Tuesday 24 November 10:00, Luis A."

#### Scenario: Slot without link
- **GIVEN** `slotUrl` returns `null` for times before 12:00
- **WHEN** it renders
- **THEN** the 10:00 cells are `data-dnd-slot` elements without `href`

### Requirement: Locked events
The Calendar helper SHALL render an event with `'locked' => true` as a normal link without `data-dnd-item`, `data-dnd-row/col/span` or resize handle, also in interactive mode.

#### Scenario: Past event locked
- **WHEN** `week()` renders with `interactive.move` an event with `locked` true
- **THEN** that event has no `data-dnd-item` and no `data-dnd-resize`, and the other events have them

### Requirement: Slot values in move and resize events
The drag and drop script SHALL add `detail.slot = {start, end, column}` to `basecoat:move` and `basecoat:resize` when the grid has `data-dnd-row-text` and `data-dnd-col-values`, where `start` and `end` are the row texts at the new row and at row + span, and `column` is the value of the new column.

#### Scenario: Move to Tuesday 08:00
- **GIVEN** an interactive week grid with 15-minute rows starting at 07:00 and an event of span 4
- **WHEN** it is dropped on row 4 of column 1
- **THEN** `basecoat:move` has `detail.slot` = `{start: "08:00", end: "09:00", column: "2026-11-17"}`

#### Scenario: Cancelled move reverts
- **WHEN** a listener calls `preventDefault()` on that `basecoat:move`
- **THEN** the event returns to its original row, column and span and nothing is posted

### Requirement: Finer steps
The Calendar helper SHALL accept `slot` 5, 10, 15, 20, 30 and 60 and SHALL render grids of up to 288 rows with the matching row, span and background classes; any other value SHALL throw `InvalidArgumentException` "Calendar option "slot" must be 5, 10, 15, 20, 30 or 60.".

#### Scenario: Five-minute day
- **WHEN** `day()` renders 06:00–22:00 with `slot` 5
- **THEN** the body has class `bc-cal-rows-192` and `bc-cal-slot-5`

#### Scenario: Invalid step
- **WHEN** `week()` is called with `slot` 7
- **THEN** it throws "Calendar option "slot" must be 5, 10, 15, 20, 30 or 60."

### Requirement: Event attributes callback
The Calendar helper SHALL merge the array returned by `eventAttributes($event)` into the attributes of each event's link in `week()` and `day()`, refusing keys starting with `on` and the `style` key.

#### Scenario: Menu trigger attributes
- **GIVEN** `eventAttributes` returns `['data-menu' => 'class-5', 'onclick' => 'x()']` for event 5
- **WHEN** the week renders
- **THEN** event 5's link has `data-menu="class-5"` and no `onclick`
