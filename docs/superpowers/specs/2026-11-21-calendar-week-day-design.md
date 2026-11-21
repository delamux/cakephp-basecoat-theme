# Calendar week and day views: design

Date: 2026-11-21
Status: approach approved in conversation ("si"); document pending review.
Extends: the month-only `calendar` screen of `2026-11-20-screens-batch-3-design.md`, whose Week and Day
buttons are inert today.

## Intent

The `calendar` bake screen renders only a month grid. The user wants real week and day views, with the
hours running top to bottom as rows. In the day view the columns are **resources**: employees, companies
or anything the app passes in.

Apps must be able to put these views on any page, not just on the baked screen. That is why the views
become a helper with options ("se le puede pasar…").

## What the user decided

- **Week view.** It shows one week, like the month view but with hour rows.
  - There is one column per selected weekday. Every column has the same fixed width.
  - Each column holds all of that day's events, for every resource. Each event shows its resource name.
  - The weekdays to show are passed as numbers, **0 = Sunday … 6 = Saturday**. For example,
    `[1,2,3,4,5]` means Monday to Friday. The order of the list is the column order.
- **Hours.** The visible time range is configurable, for example 08:00 to 20:00. It applies to both the
  week and the day view.
- **Day view.** Clicking a day header in the week view opens the day view for that date. The hours are
  rows, top to bottom. There is one column per resource, each with the same fixed width.
- **Width.** The grid uses the full available width. When the columns do not fit, it scrolls
  horizontally. The hour column stays fixed on the left and the header row stays fixed at the top.
- **Month view.** It stays as it is, including drag and drop.
- **How it is built.** A PHP helper that renders the HTML on the server. No calendar library is used.
  FullCalendar's resource views are paid, so they are excluded anyway.
- **Phasing.** In this version the week and day views are **read-only**, and each event is a link.
  Dragging an event to change its time or resource comes in a later phase.

## Goals

1. **New `CalendarHelper`** (`BasecoatTheme.Calendar`), with the full helper treatment: tests, a catalog
   page, a README section, translations, and registration in `BasecoatHelpers`.
   - `week(array $events, array $options = [])` and `day(array $events, array $options = [])`.
   - `month(array $events, array $options = [])` renders the screen's current month grid, moved out of
     the template.
     - It must keep the same markup and `data-dnd-*` hooks, so the existing drag and drop keeps working.
     - The "Upcoming" and "Categories" side panels stay in the screen, not in the helper.
2. **Event format.** One array works for all three views. It extends today's screen data:
   - `id`: a scalar.
   - `title`.
   - `date`: `Y-m-d`.
   - `time`: the start, as `H:i`, or null for an all-day event.
   - `end`: optional, `H:i`. Defaults to `time` plus one slot.
   - `resource`: optional, a key of `resources`.
   - `category`: optional, a key of `categories`.
   - `url`: optional, the event's link. When it is missing, the link is `#`.

   A bad date or time format throws an `InvalidArgumentException`, and so does an `end` that is not after
   the start.
3. **Options:**
   - `date`: `Y-m-d`, required for the week and day views. The week shown is the one that contains this
     date.
   - `today`: `Y-m-d`. The helper never reads the clock.
   - `days`: week only, default `[1,2,3,4,5,6,0]`.
     - Integers 0–6, no duplicates, at least one. Anything else throws an `InvalidArgumentException`.
     - The week starts on `days[0]`. The period shown is the 7 days starting on the latest date that is on
       or before `date` and falls on weekday `days[0]`.
     - Each column is the date in that period that falls on its weekday.
   - `start` and `end`: `H:i`, default `08:00` and `20:00`.
     - `end` may be `24:00`, and must be after `start`.
     - Both must fall on a slot boundary.
   - `slot`: minutes per row, 15, 30 or 60. Default 30.
   - `resources`: an `id => label` map. These are the day view's columns, in order.
     - Day events whose resource is missing or unknown go in an extra "Unassigned" column at the end. That
       column only appears when there are such events.
     - With no resources at all, the day view has a single column for the day.
   - `categories`: a `key => label` map.
     - The keys `meeting`, `deadline`, `release` and `team` keep the screen's colours.
     - Any other key gets a neutral colour.
   - `columnWidth`: `sm`, `md` (default, about 12rem) or `lg`. It maps to a class, never to an inline style.
   - `dayUrl`: a callable `fn (string $date): string|array`, or null. It builds the link from each week
     column header to that day's view. With null, the header is plain text.
   - `label`: the accessible name of the grid.
   - `class`, `attributes` and `assets` work as in the other helpers.
4. **Grid behaviour (week and day):**
   - **Rows.**
     - There is one row per slot.
     - Hour labels sit on the full hours, and the slots in between have dashed lines.
     - All-day events sit in an "All day" row under the header.
   - **Placing events.** An event spans the rows from its start to its end. It is placed with classes
     from the plugin stylesheet (`bc-cal-row-N`, `bc-cal-span-N`).
   - **Events outside the hours.**
     - An event that crosses `start` or `end` is clipped and marked as continuing. The mark is a visual
       cue plus screen-reader text with the real times.
     - An event entirely outside the range is not placed. Instead, the column header shows "N outside
       hours".
   - **Overlaps.**
     - Overlapping events in the same column sit side by side in lanes, with classes for lanes 1–4.
     - Beyond four lanes, the rest collapse into a "+N" link to `dayUrl` for that date.
   - **Today.** The header of today's column is marked with `aria-current="date"`.
   - **Event text.**
     - In the week view, each event shows its time, title and resource label.
     - In the day view, each event shows its time, title and category.
   - **Accessibility.**
     - The grid is a scroll region with an accessible name.
     - Each column is a labelled group, named like "Tuesday 24 November" or after the resource.
     - Each event is a link whose accessible name includes the date, the times, the title, the resource
       and the category.
5. **Stylesheet `webroot/css/basecoat-calendar.css`.**
   - It holds the grid layout classes (`bc-cal-*`), built on the Basecoat tokens, in light and dark.
   - It has rows and spans up to 96, which covers 15-minute slots over 24 hours.
   - No Tailwind classes are generated at runtime and no inline `style` attributes are used, so it works
     under a strict CSP. This is the same pattern as `basecoat-flow.css`.
   - Like `FlowHelper`, the helper adds the stylesheet to the `css` view block unless `assets` is false.
6. **`calendar` bake screen:**
   - **Query parameters.** It reads `?view=month|week|day` and `?date=Y-m-d`.
     - The defaults are the month view and `today`. Invalid values fall back to the defaults.
     - The old `?month=Y-m` keeps working and maps onto `date`.
   - **Rendering.** It renders the matching helper call.
   - **Navigation.**
     - The Month, Week and Day buttons become links, with `aria-current` on the active one.
     - Previous, next and Today move by month, week or day, depending on the view.
     - The week column headers link to the day view.
   - **Sample data.** `data.php` keeps the invented Fernway world and adds:
     - `resources`: 5 invented people;
     - `end`, `resource` and `url` on each event;
     - `days`, `start` and `end`, with the screen's own defaults `[1,2,3,4,5]` and 08:00–20:00;
     - a few more events in the week of `today`, so the week and day views are not empty.
7. **Documentation.**
   - **README Calendar helper section.** It covers data, options, the three views, styling and CSP, and
     includes an example that links week ↔ day.
   - **README reference updates.** The screen's entry in the list and the helpers table are updated.
   - **Upgrade notes.** A re-bake is needed to get the new views. Old data keeps working for the month.
   - **Catalog examples.** One each for month, week (Monday to Friday, 08:00–20:00) and day with
     resources.
8. **Tests:**
   - **`CalendarHelperTest`** covers:
     - validating the options;
     - working out the week period from `days`;
     - clipping events and counting the ones outside the hours;
     - lanes;
     - the all-day row;
     - the Unassigned column;
     - `dayUrl` links;
     - escaping.
   - **`CalendarScreenTest`** is updated for the views and the navigation.
   - **Translations.** The strings are added to the pot and the Spanish po.
   - **Catalog.** A strict catalog build passes.
   - **Browser check.**
     - Light and dark, at 390px and 1280px.
     - Horizontal scroll, with the hour column and the header staying fixed.
     - Tabbing through the events with the keyboard.
     - A clean console.

## Non-goals

- Dragging, resizing or moving events with the keyboard in the week and day views (that is the later
  phase).
- A line showing the current time. It would need the clock or JavaScript.
- Recurring events and time zones. Times are taken as given, as wall-clock strings.
- Timed events that span several days. An event belongs to its `date`.
- A week view with a column per resource and day. The user chose days as the week's columns.

## Anti-goals

- Copying a paid template or kit, FullCalendar Premium's resource views included. All the sample data is
  invented.
- Inline styles or Tailwind classes generated at runtime. They would break a strict CSP or a compiled
  Tailwind build.
- Requiring JavaScript to see the week or day view.

## Approaches considered

| Approach | Trade-off | Decision |
| --- | --- | --- |
| PHP helper rendering the grid on the server with plugin CSS classes | We maintain the overlap, clipping and placement logic. It works without JS, under a strict CSP and with no new dependency. | **Chosen** |
| Bundled JS calendar library (EventCalendar, MIT) | Drag and resize come built in. But it does not follow Basecoat, shows nothing without JS and adds another ~100 KB bundle. FullCalendar's resource views are paid. | Rejected |
| Week and day only in the bake template | Fastest, but it cannot be reused on other pages. | Rejected |

## Calls made after the builder review

These were settled by the designer, not by the user. Each one can be changed.

- **What `month()` takes.**
  - Options: `date` (the month shown is the one containing it), `today`, `categories`, `label`,
    `class`, `attributes` and `assets`.
  - Months start on Monday, as today, and `days` is ignored.
  - Resource names are not shown.
  - With `dayUrl`, each day number links to that day's view. Without it, the markup is exactly today's,
    so the drag and drop and the screen test do not change.
- **Which events to pass.** The caller passes every event it has, and the helper keeps the ones in the
  period being shown. "N outside hours" only counts events in a shown day that fall outside
  `start`–`end`.
- **Overflow beyond four lanes.** The remaining events go into a `<details>` labelled "+N more". It
  lists them as links, works without JavaScript and keeps every event reachable by keyboard. The same
  behaviour applies in the week view, the day view, and with or without `dayUrl`.
- **Date names.** Weekday and month names use Cake's `i18nFormat`, as the screen already does (CakePHP
  itself requires intl). No name strings are translated by hand.
- **Events off the slot grid.** An event's start is rounded down to its slot and its end is rounded up.
  A 09:10–09:40 event with 30-minute slots fills 09:00–10:00, and its link text still shows the real
  times.
- **`24:00`.** It is allowed as an event `end`. Events never cross midnight; an event belongs to its
  `date`.

## Left to the builder

- Exact class names, column widths in rem, and how lanes are computed. A greedy interval colouring is
  enough.
