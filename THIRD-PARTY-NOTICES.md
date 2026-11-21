# Third-party notices

This plugin is MIT licensed (see `LICENSE`). It includes, or loads at runtime, the third-party
software listed below. Each remains the property of its authors and is used under its own license.

## Included in this package

### Lucide icons (ISC; Feather-derived icons MIT)

The SVG path data in `src/View/Helper/IconHelper.php` comes from Lucide (https://lucide.dev).

```
ISC License

Copyright (c) 2026 Lucide Icons and Contributors

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

---

The following Lucide icons are derived from the Feather project:

airplay, alert-circle, alert-octagon, alert-triangle, aperture, arrow-down-circle, arrow-down-left, arrow-down-right, arrow-down, arrow-left-circle, arrow-left, arrow-right-circle, arrow-right, arrow-up-circle, arrow-up-left, arrow-up-right, arrow-up, at-sign, calendar, cast, check, chevron-down, chevron-left, chevron-right, chevron-up, chevrons-down, chevrons-left, chevrons-right, chevrons-up, circle, clipboard, clock, code, columns, command, compass, corner-down-left, corner-down-right, corner-left-down, corner-left-up, corner-right-down, corner-right-up, corner-up-left, corner-up-right, crosshair, database, divide-circle, divide-square, dollar-sign, download, external-link, feather, frown, hash, headphones, help-circle, info, italic, key, layout, life-buoy, link-2, link, loader, lock, log-in, log-out, maximize, meh, minimize, minimize-2, minus-circle, minus-square, minus, monitor, moon, more-horizontal, more-vertical, move, music, navigation-2, navigation, octagon, pause-circle, percent, plus-circle, plus-square, plus, power, radio, rss, search, server, share, shopping-bag, sidebar, smartphone, smile, square, table-2, tablet, target, terminal, trash-2, trash, triangle, tv, type, upload, x-circle, x-octagon, x-square, x, zoom-in, zoom-out

The MIT License (MIT) (for the icons listed above)

Copyright (c) 2013-present Cole Bemis

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

### Flow editor bundle (`webroot/js/basecoat-flow.js`, `webroot/css/basecoat-flow.css`)

`FlowHelper` ships a precompiled bundle (sources in `resources/flow/`) that contains these packages:

| Package | License | Copyright |
| --- | --- | --- |
| `@xyflow/react`, `@xyflow/system` (React Flow) | MIT | © 2019-2025 webkid GmbH |
| `react`, `react-dom`, `scheduler`, `use-sync-external-store` | MIT | © Meta Platforms, Inc. and affiliates |
| `zustand` | MIT | © 2019 Paul Henschel |
| `classcat` | MIT | © Jorge Bucaran |
| `d3-color`, `d3-dispatch`, `d3-drag`, `d3-interpolate`, `d3-selection`, `d3-timer`, `d3-transition`, `d3-zoom` | ISC | © 2010-2026 Mike Bostock |
| `d3-ease` | BSD-3-Clause | © 2010-2026 Mike Bostock, © 2001 Robert Penner |

The exact versions and the **full license text of every package** are in
`webroot/js/basecoat-flow.LICENSES.txt`, generated from the bundle by `resources/flow/build.mjs` (it ships
next to the bundle). The minified bundle also keeps the packages' license comments.

## Loaded from a CDN at runtime (not bundled)

These are referenced by URL (jsDelivr / cdnjs) and are not redistributed in this package.

| Software | Used by | License | Copyright |
| --- | --- | --- | --- |
| Basecoat (`basecoat-css`) | `ThemeHelper`, default layout | MIT | © 2025 Ronan Berder |
| Tailwind CSS (`@tailwindcss/browser`) | `ThemeHelper`, default layout | MIT | © Tailwind Labs, Inc. |
| Chart.js | `ChartHelper` | MIT | © 2014-2024 Chart.js Contributors |
| highlight.js | component catalog (docs only) | BSD-3-Clause | © 2006, Ivan Sagalaev |

The full license texts ship with each package and are available at the URLs the plugin loads them from.
