'use strict';
// Unit tests for the pure helpers of webroot/js/basecoat-screens.js (run: composer test-js).
const test = require('node:test');
const assert = require('node:assert/strict');
const screens = require('../../webroot/js/basecoat-screens.js');

test('normalize lowercases, strips accents and collapses spaces', () => {
  assert.equal(screens.normalize('  Árbol   Ñandú '), 'arbol nandu');
  assert.equal(screens.normalize(null), '');
});

test('matchesQuery requires every word', () => {
  assert.equal(screens.matchesQuery('Invoice for María (paid)', 'maria paid'), true);
  assert.equal(screens.matchesQuery('Invoice for María', 'maria overdue'), false);
  assert.equal(screens.matchesQuery('anything', ''), true);
  assert.equal(screens.matchesQuery('anything', '   '), true);
});

test('matchesFacet: all, token and negation', () => {
  const tokens = screens.tokensOf('done', 'work urgent');
  assert.deepEqual(tokens, ['done', 'work', 'urgent']);
  assert.equal(screens.matchesFacet(tokens, ''), true);
  assert.equal(screens.matchesFacet(tokens, '*'), true);
  assert.equal(screens.matchesFacet(tokens, 'urgent'), true);
  assert.equal(screens.matchesFacet(tokens, 'home'), false);
  assert.equal(screens.matchesFacet(tokens, '!done'), false);
  assert.equal(screens.matchesFacet(screens.tokensOf('', ''), '!done'), true);
});

test('selectAllState', () => {
  assert.equal(screens.selectAllState(0, 0), 'none');
  assert.equal(screens.selectAllState(0, 3), 'none');
  assert.equal(screens.selectAllState(2, 3), 'some');
  assert.equal(screens.selectAllState(3, 3), 'all');
});

test('stepStates', () => {
  assert.deepEqual(screens.stepStates(1, 4), ['complete', 'current', 'upcoming', 'upcoming']);
  assert.deepEqual(screens.stepStates(0, 1), ['current']);
  assert.deepEqual(screens.stepStates(0, 0), []);
});

test('swapClasses swaps the view classes', () => {
  const map = { grid: 'grid grid-cols-3 gap-4', list: 'flex flex-col gap-4' };
  assert.deepEqual(screens.swapClasses(['p-2', 'grid', 'grid-cols-3', 'gap-4'], map, 'list'), ['p-2', 'flex', 'flex-col', 'gap-4']);
});

test('format and clamp', () => {
  assert.equal(screens.format('{visible} of {total} shown.', { visible: 2, total: 5 }), '2 of 5 shown.');
  assert.equal(screens.clamp(9, 0, 3), 3);
});
