'use strict';
// Unit tests for the pure helpers of webroot/js/basecoat-dnd.js (run: composer test-js).
const test = require('node:test');
const assert = require('node:assert/strict');
const dnd = require('../../webroot/js/basecoat-dnd.js');

test('does not touch the DOM when loaded in Node', () => {
  assert.equal(typeof dnd.insertionIndex, 'function');
  assert.equal(typeof globalThis.window, 'undefined');
});

test('insertionIndex counts the midpoints before the pointer', () => {
  assert.equal(dnd.insertionIndex([], 50), 0);
  assert.equal(dnd.insertionIndex([10, 30, 50], 5), 0);
  assert.equal(dnd.insertionIndex([10, 30, 50], 20), 1);
  assert.equal(dnd.insertionIndex([10, 30, 50], 30), 1);
  assert.equal(dnd.insertionIndex([10, 30, 50], 99), 3);
});

test('clamp', () => {
  assert.equal(dnd.clamp(-1, 0, 3), 0);
  assert.equal(dnd.clamp(5, 0, 3), 3);
  assert.equal(dnd.clamp(2, 0, 3), 2);
});

test('format fills known placeholders and keeps unknown ones', () => {
  assert.equal(dnd.format('Picked up {label}. {container}, position {position} of {total}.', { label: 'Card', container: 'To do', position: 2, total: 4 }), 'Picked up Card. To do, position 2 of 4.');
  assert.equal(dnd.format('{a} {b}', { a: 0 }), '0 {b}');
  assert.equal(dnd.format('{container-id}|09:00', { 'container-id': '2026-11-27' }), '2026-11-27|09:00', 'hyphenated keys');
});

test('mapClasses lists every class of an array or object map', () => {
  assert.deepEqual(dnd.mapClasses(['row-start-2', 'row-start-3 extra']), ['row-start-2', 'row-start-3', 'extra']);
  assert.deepEqual(dnd.mapClasses({ 1: 'row-span-1', 2: 'row-span-2' }), ['row-span-1', 'row-span-2']);
  assert.deepEqual(dnd.mapClasses(null), []);
});

test('swapClasses replaces the class of a map and keeps the rest', () => {
  const map = ['col-start-2', 'col-start-3', 'col-start-4'];
  assert.deepEqual(dnd.swapClasses(['card', 'col-start-2', 'p-2'], map, 2), ['card', 'p-2', 'col-start-4']);
  assert.deepEqual(dnd.swapClasses(['card'], { 2: 'row-span-2 h-full' }, '2'), ['card', 'row-span-2', 'h-full']);
  assert.deepEqual(dnd.swapClasses(['card', 'col-start-3'], map, 9), ['card'], 'unknown key: only removes');
});

test('mapSize', () => {
  assert.equal(dnd.mapSize(['a', 'b', 'c']), 3);
  assert.equal(dnd.mapSize({ 0: 'a', 1: 'b' }), 2);
  assert.equal(dnd.mapSize(undefined), 0);
});

test('clampPlacement keeps spans inside the grid (row axis)', () => {
  const grid = { rows: 8, cols: 3, axis: 'row', spanMax: 0 };
  assert.deepEqual(dnd.clampPlacement({ row: 7, col: 1, span: 2 }, grid), { row: 6, col: 1, span: 2 });
  assert.deepEqual(dnd.clampPlacement({ row: -2, col: 5, span: 1 }, grid), { row: 0, col: 2, span: 1 });
  assert.deepEqual(dnd.clampPlacement({ row: 0, col: 0, span: 0 }, grid), { row: 0, col: 0, span: 1 });
  assert.deepEqual(dnd.clampPlacement({ row: 0, col: 0, span: 20 }, grid), { row: 0, col: 0, span: 8 });
  assert.deepEqual(dnd.clampPlacement({ row: 0, col: 0, span: 5 }, Object.assign({}, grid, { spanMax: 3 })), { row: 0, col: 0, span: 3 });
});

test('clampPlacement on the column axis', () => {
  const grid = { rows: 4, cols: 7, axis: 'col', spanMax: 0 };
  assert.deepEqual(dnd.clampPlacement({ row: 9, col: 6, span: 3 }, grid), { row: 3, col: 4, span: 3 });
});

test('overlaps and collisions', () => {
  const a = { row: 2, col: 1, span: 2 };
  assert.equal(dnd.overlaps(a, { row: 3, col: 1, span: 1 }), true);
  assert.equal(dnd.overlaps(a, { row: 4, col: 1, span: 1 }), false, 'touching is not overlapping');
  assert.equal(dnd.overlaps(a, { row: 2, col: 2, span: 2 }), false, 'other column');
  assert.equal(dnd.overlaps({ row: 0, col: 1, span: 2 }, { row: 0, col: 2, span: 1 }, 'col'), true);
  assert.deepEqual(dnd.collisions(a, [{ row: 0, col: 1, span: 2 }, { row: 1, col: 1, span: 2 }, { row: 3, col: 0, span: 1 }]), [1]);
});

test('resolveEndpoint keeps same-origin http(s) URLs only', () => {
  const origin = 'https://app.test';
  const base = 'https://app.test/admin/boards/view/3';
  assert.equal(dnd.resolveEndpoint('/boards/move', base, origin), 'https://app.test/boards/move');
  assert.equal(dnd.resolveEndpoint('move', base, origin), 'https://app.test/admin/boards/view/move', 'relative to baseURI');
  assert.equal(dnd.resolveEndpoint('https://app.test/x', base, origin), 'https://app.test/x');
  assert.equal(dnd.resolveEndpoint('https://evil.test/x', base, origin), null);
  assert.equal(dnd.resolveEndpoint('//evil.test/x', base, origin), null, 'protocol-relative');
  assert.equal(dnd.resolveEndpoint('http://app.test/x', base, origin), null, 'other scheme = other origin');
  assert.equal(dnd.resolveEndpoint('javascript:alert(1)', base, origin), null);
  assert.equal(dnd.resolveEndpoint('', base, origin), null);
  assert.equal(dnd.resolveEndpoint(null, base, origin), null);
});

test('edgeSpeed scrolls only near the edges, faster closer to them', () => {
  assert.equal(dnd.edgeSpeed(200, 0, 400), 0);
  assert.ok(dnd.edgeSpeed(30, 0, 400) < 0);
  assert.ok(dnd.edgeSpeed(390, 0, 400) > 0);
  assert.ok(dnd.edgeSpeed(5, 0, 400) < dnd.edgeSpeed(30, 0, 400), 'closer = faster');
  assert.equal(dnd.edgeSpeed(-100, 0, 400), -20, 'capped outside the box');
  assert.equal(dnd.edgeSpeed(900, 0, 400, 40, 15), 15);
  assert.equal(dnd.edgeSpeed(10, 0, 0), 0, 'empty box');
});

test('placementText reads the boundary and column texts, numbers otherwise', () => {
  const rows = ['06:00', '06:30', '07:00', '07:30'];
  const cols = ['Main floor', 'Rig'];
  assert.deepEqual(dnd.placementText(rows, cols, { row: 1, col: 1, span: 2 }), { start: '06:30', end: '07:30', col: 'Rig', row: '2', span: '2' });
  assert.deepEqual(dnd.placementText(null, undefined, { row: 0, col: 2, span: 1 }), { start: '1', end: '2', col: '3', row: '1', span: '1' });
});

test('insertionIndex2d: vertical lists, wrapping grids and CSS columns', () => {
  const box = (left, top, width = 100, height = 50) => ({ left, top, width, height });
  // Vertical list: compare with y of the nearest item
  const list = [box(0, 0), box(0, 60), box(0, 120)];
  assert.equal(dnd.insertionIndex2d(list, 50, 10), 0);
  assert.equal(dnd.insertionIndex2d(list, 50, 40), 1);
  assert.equal(dnd.insertionIndex2d(list, 50, 400), 3);
  assert.equal(dnd.insertionIndex2d([], 5, 5), 0);
  // Grid of 3 per row: same row neighbours -> compare x
  const grid = [box(0, 0), box(110, 0), box(220, 0), box(0, 60), box(110, 60)];
  assert.equal(dnd.insertionIndex2d(grid, 120, 20), 1, 'left half of the 2nd card');
  assert.equal(dnd.insertionIndex2d(grid, 200, 20), 2, 'right half of the 2nd card');
  assert.equal(dnd.insertionIndex2d(grid, 300, 20), 3, 'right half of the last card of the row');
  assert.equal(dnd.insertionIndex2d(grid, 10, 70), 3, 'left half of the first card of row 2');
  // CSS columns (column-major): 0,1 in the first column, 2,3 in the second
  const columns = [box(0, 0), box(0, 60), box(110, 0), box(110, 60)];
  assert.equal(dnd.insertionIndex2d(columns, 150, 70), 3, 'top half of the last card');
  assert.equal(dnd.insertionIndex2d(columns, 150, 100), 4);
  assert.equal(dnd.insertionIndex2d(columns, 40, 100), 2, 'below the first column');
});

test('compareKeys sorts numbers inside keys naturally', () => {
  assert.ok(dnd.compareKeys('7|2', '10|1') < 0);
  assert.ok(dnd.compareKeys('2026-11-24', '2026-11-03') > 0);
  assert.equal(dnd.compareKeys('a', 'a'), 0);
});

// ---------------------------------------------------------------- commit / revert (DOM-free adapters, stubbed POST)
const commitWith = (overrides) => {
  const log = [];
  const options = Object.assign({
    confirm: () => true,
    endpoint: '/boards/move',
    base: 'https://app.test/boards',
    origin: 'https://app.test',
    post: (url) => { log.push(`post ${url}`); return Promise.resolve({ ok: true, status: 200 }); },
    busy: (b) => log.push(`busy ${b}`),
    revert: () => log.push('revert'),
    fail: (status, reason) => log.push(`fail ${status} ${reason}`),
    refused: () => log.push('refused'),
  }, overrides);
  return { result: dnd.commitChange(options), log };
};

test('commitChange: committed, busy while the POST is pending', async () => {
  const { result, log } = commitWith({});
  assert.equal(result.status, 'committed');
  assert.deepEqual(log, ['busy true', 'post https://app.test/boards/move']);
  await result.pending;
  assert.deepEqual(log, ['busy true', 'post https://app.test/boards/move', 'busy false']);
});

test('commitChange: failed POST reverts and reports, cancelled event reverts, no endpoint just commits', async () => {
  const failed = commitWith({ post: () => Promise.resolve({ ok: false, status: 500 }) });
  await failed.result.pending;
  assert.deepEqual(failed.log, ['busy true', 'busy false', 'revert', 'fail 500 response']);

  const cancelled = commitWith({ confirm: () => false });
  assert.equal(cancelled.result.status, 'reverted');
  assert.deepEqual(cancelled.log, ['revert']);

  const local = commitWith({ endpoint: null });
  assert.equal(local.result.status, 'committed');
  assert.equal(local.result.pending, null);
  assert.deepEqual(local.log, []);
});

test('commitChange: a cross-origin endpoint is refused at once, without a request', () => {
  const { result, log } = commitWith({ endpoint: 'https://evil.test/move' });
  assert.equal(result.status, 'failed');
  assert.deepEqual(log, ['refused', 'revert', 'fail 0 origin']);
});

test('postJson sends JSON with the CSRF token through the injected fetch', async () => {
  const calls = [];
  const fetch = (url, init) => { calls.push({ url, init }); return Promise.resolve({ ok: true, status: 201 }); };
  assert.deepEqual(await dnd.postJson(fetch, 'https://app.test/m', { action: 'move', id: '3' }, 'tok'), { ok: true, status: 201 });
  assert.equal(calls[0].init.headers['X-CSRF-Token'], 'tok');
  assert.equal(calls[0].init.method, 'POST');
  assert.deepEqual(JSON.parse(calls[0].init.body), { action: 'move', id: '3' });
  assert.deepEqual(await dnd.postJson(() => Promise.reject(new Error('offline')), 'https://app.test/m', {}, ''), { ok: false, status: 0 });
});

test('revertIndex puts the item back next to its old neighbours, not at a stale index', () => {
  const [a, b, c, d] = ['a', 'b', 'c', 'd'];
  // Before: [a, X, b, c] (X at 1, prev a, next b). Meanwhile a was moved to the end: [b, c, a]
  assert.equal(dnd.revertIndex([b, c, a], a, b, 1), 3, 'after its old previous neighbour');
  // Previous neighbour gone: before the old next one
  assert.equal(dnd.revertIndex([c, b, d], 'gone', b, 1), 1);
  // Both gone: the old index, clamped
  assert.equal(dnd.revertIndex([c], 'x', 'y', 5), 1);
  assert.equal(dnd.revertIndex([], null, null, 0), 0);
  // First item (no previous neighbour)
  assert.equal(dnd.revertIndex([c, b], null, b, 0), 1);
});

test('parseAttrBindings reads "attr:template" pairs and refuses event handlers and style', () => {
  assert.deepEqual(dnd.parseAttrBindings('datetime:{date}'), [['datetime', '{date}']]);
  assert.deepEqual(dnd.parseAttrBindings('datetime:{date}T{time}; data-day:{container-id}'), [['datetime', '{date}T{time}'], ['data-day', '{container-id}']]);
  assert.deepEqual(dnd.parseAttrBindings('onclick:alert(1); style:color:red; href:{url}; :x; bad name:1'), [['href', '{url}']]);
  assert.deepEqual(dnd.parseAttrBindings(null), []);
});

test('fillTemplate fills every placeholder or returns null', () => {
  assert.equal(dnd.fillTemplate('{date}', { date: '2026-11-27' }), '2026-11-27');
  assert.equal(dnd.fillTemplate('{date}T{time}', { date: '2026-11-27' }), null, 'unknown placeholder');
  assert.equal(dnd.fillTemplate('fixed', {}), 'fixed');
});

test('createJsonCache parses once and clears past its limit', () => {
  const cache = dnd.createJsonCache(1, () => {});
  const value = cache.parse('[1]');
  assert.equal(cache.parse('[1]'), value);
  cache.parse('[2]');
  assert.equal(cache.size(), 1);
  assert.equal(cache.parse('{oops'), null);
});

test('slotValues: start and end from the row texts, column from the column values', () => {
  const rows = ['07:00', '07:15', '07:30', '07:45', '08:00', '08:15', '08:30', '08:45', '09:00'];
  const cols = ['2026-11-16', '2026-11-17', '2026-11-18'];
  assert.deepEqual(dnd.slotValues(rows, cols, { row: 4, col: 1, span: 4 }), { start: '08:00', end: '09:00', column: '2026-11-17' });
  assert.deepEqual(dnd.slotValues(rows, ['l', 'a', ''], { row: 0, col: 2, span: 1 }), { start: '07:00', end: '07:15', column: '' }, '"" = no resource');
  assert.equal(dnd.slotValues(rows, null, { row: 0, col: 0, span: 1 }), null, 'needs column values');
  assert.equal(dnd.slotValues(null, cols, { row: 0, col: 0, span: 1 }), null, 'needs row texts');
  assert.equal(dnd.slotValues(rows, cols, { row: 8, col: 0, span: 1 }), null, 'outside the row texts');
  assert.equal(dnd.slotValues(rows, cols, { row: 0, col: 5, span: 1 }), null, 'outside the column values');
});

test('a cancelled calendar move reverts and posts nothing', () => {
  const { result, log } = commitWith({ confirm: () => false, endpoint: '/classes/move' });
  assert.equal(result.status, 'reverted');
  assert.deepEqual(log, ['revert']);
});
