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
