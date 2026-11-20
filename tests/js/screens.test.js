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

test('resolveEndpoint refuses cross-origin and non-http endpoints', () => {
  const origin = 'http://localhost:8765';
  assert.equal(screens.resolveEndpoint('/todos/toggle', 'http://localhost:8765/todos', origin), 'http://localhost:8765/todos/toggle');
  assert.equal(screens.resolveEndpoint('https://other.test/toggle', 'http://localhost:8765/', origin), null);
  assert.equal(screens.resolveEndpoint('data:text/plain,x', 'http://localhost:8765/', origin), null);
  assert.equal(screens.resolveEndpoint('http://[bad', 'http://localhost:8765/', origin), null, 'invalid URL');
});

test('matchesFacets: every key must match (AND)', () => {
  const tokens = screens.tokensOf('unread', 'access');
  assert.equal(screens.matchesFacets(tokens, {}), true);
  assert.equal(screens.matchesFacets(tokens, { status: 'unread', type: 'access' }), true);
  assert.equal(screens.matchesFacets(tokens, { status: 'unread', type: 'task' }), false);
  assert.equal(screens.matchesFacets(tokens, { status: '', type: '!task' }), true);
});

test('countValue adds the offset and never goes below zero', () => {
  assert.equal(screens.countValue(3, '2'), 5);
  assert.equal(screens.countValue(3, null), 3);
  assert.equal(screens.countValue(1, '-4'), 0);
  assert.equal(screens.countValue(2, 'x'), 2);
});

test('formatSize', () => {
  assert.equal(screens.formatSize(0), '0 B');
  assert.equal(screens.formatSize(1536), '1.5 KB');
  assert.equal(screens.formatSize(15 * 1024 * 1024), '15 MB');
  assert.equal(screens.formatSize(-3), '0 B');
});

test('joinValues keeps non-empty values, falls back to the empty text', () => {
  assert.equal(screens.joinValues(['Fernway', '', ' Ops '], '—'), 'Fernway, Ops');
  assert.equal(screens.joinValues([], 'Not set'), 'Not set');
  assert.equal(screens.joinValues(['  '], ''), '');
});

test('format accepts hyphenated placeholders', () => {
  assert.equal(screens.format('{container-id}: {label}', { 'container-id': 'x', label: 'Y' }), 'x: Y');
});

// ---------------------------------------------------------------- toggle transaction (DOM-free adapters)
const flush = () => new Promise((resolve) => setImmediate(resolve));
/** A toggle of `name` on a fake item whose state is `state[name]` (true | undefined). */
const change = (state, log, name, on, options = {}) => ({
  name,
  on,
  isOn: () => state[name] === true,
  read: () => state[name],
  write: (value) => { state[name] = value || undefined; log.push(`write ${name}=${value}`); },
  restore: (snapshot) => { state[name] = snapshot; log.push(`restore ${name}`); },
  confirm: () => !options.cancel,
});
const transaction = (overrides) => {
  const calls = { sent: [], busy: [], failed: [], after: 0 };
  const options = Object.assign({
    busy: () => false,
    chained: [],
    after: () => { calls.after += 1; },
    send: (also) => { calls.sent.push(also); return Promise.resolve({ ok: true, status: 200, reason: 'response' }); },
    setBusy: (value) => calls.busy.push(value),
    fail: (result) => calls.failed.push(result),
  }, overrides);
  return { result: screens.toggleTransaction(options), calls };
};

test('toggleTransaction: chained toggles go in the same POST (also) and bypass the busy flag', async () => {
  const state = { read: undefined };
  const log = [];
  const { result, calls } = transaction({
    main: change(state, log, 'accepted', true),
    chained: [change(state, log, 'answered', true), change(state, log, 'read', true)],
  });
  assert.equal(result.status, 'applied');
  assert.deepEqual(result.also, ['answered', 'read']);
  assert.deepEqual(state, { accepted: true, answered: true, read: true });
  assert.deepEqual(calls.sent, [['answered', 'read']], 'one request for the whole chain');
  assert.deepEqual(calls.busy, [true]);
  assert.equal(calls.after, 1);
  await result.pending;
  assert.deepEqual(calls.busy, [true, false]);
  assert.deepEqual(calls.failed, []);
  assert.deepEqual(state, { accepted: true, answered: true, read: true });
});

test('toggleTransaction: a failed POST reverts the toggle and every chained one (last first)', async () => {
  const state = { read: undefined };
  const log = [];
  const { result, calls } = transaction({
    main: change(state, log, 'accepted', true),
    chained: [change(state, log, 'answered', true), change(state, log, 'read', true)],
    send: () => Promise.resolve({ ok: false, status: 500, reason: 'response' }),
  });
  await result.pending;
  assert.deepEqual(state, { accepted: undefined, answered: undefined, read: undefined });
  assert.deepEqual(log.filter((l) => l.startsWith('restore')), ['restore read', 'restore answered', 'restore accepted']);
  assert.deepEqual(calls.busy, [true, false], 'busy cleared before the revert');
  assert.equal(calls.failed.length, 1);
  assert.equal(calls.failed[0].status, 500);
});

test('toggleTransaction: busy item ignores the change and resyncs the control', () => {
  const state = { done: undefined };
  const log = [];
  const { result, calls } = transaction({ busy: () => true, main: change(state, log, 'done', true) });
  assert.equal(result.status, 'busy');
  assert.deepEqual(log, ['write done=false']);
  assert.deepEqual(calls.sent, []);
});

test('toggleTransaction: unchanged, cancelled and no endpoint', async () => {
  const state = { done: true };
  const log = [];
  assert.equal(transaction({ main: change(state, log, 'done', true) }).result.status, 'unchanged');

  const cancelled = transaction({ main: change(state, log, 'done', false, { cancel: true }) });
  assert.equal(cancelled.result.status, 'cancelled');
  assert.equal(state.done, true, 'restored');
  assert.deepEqual(cancelled.calls.sent, []);

  const local = transaction({ main: change(state, log, 'done', false), send: () => null });
  assert.equal(local.result.status, 'applied');
  assert.equal(local.result.pending, null);
  assert.deepEqual(local.calls.busy, [], 'no busy flag without an endpoint');
  assert.equal(state.done, undefined);
});

test('toggleTransaction: chain only when switching on; skips chained toggles already on or cancelled', () => {
  const state = { read: true, answered: undefined };
  const log = [];
  const off = transaction({ main: change({ accepted: true }, log, 'accepted', false), chained: [change(state, log, 'answered', true)] });
  assert.deepEqual(off.result.also, []);
  assert.equal(state.answered, undefined);

  const on = transaction({
    main: change(state, log, 'accepted', true),
    chained: [change(state, log, 'answered', true, { cancel: true }), change(state, log, 'read', true)],
  });
  assert.deepEqual(on.result.also, [], 'read already on, answered cancelled');
  assert.equal(state.answered, undefined, 'cancelled chained toggle restored');
  assert.deepEqual(on.calls.sent, [[]]);
});

test('toggleTransaction: undo() reverts a local (bulk) change', () => {
  const state = {};
  const log = [];
  const { result } = transaction({ main: change(state, log, 'read', true), send: () => null });
  assert.equal(state.read, true);
  result.undo();
  assert.equal(state.read, undefined);
});

// ---------------------------------------------------------------- endpoint and settle (stubbed fetch)
const fetchStub = (outcome) => {
  const calls = [];
  const fn = (url, init) => {
    calls.push({ url, init });
    return outcome instanceof Error ? Promise.reject(outcome) : Promise.resolve(outcome);
  };
  return { fn, calls };
};
const env = (fetch, errors = []) => ({ base: 'https://app.test/todos', origin: 'https://app.test', fetch, error: (m) => errors.push(m) });

test('endpointRequest POSTs JSON with the CSRF token to a same-origin endpoint', async () => {
  const stub = fetchStub({ ok: true, status: 204 });
  const result = await screens.endpointRequest({ raw: '/todos/toggle', token: 'abc' }, { action: 'toggle', id: '7' }, env(stub.fn));
  assert.deepEqual(result, { ok: true, status: 204, reason: 'response' });
  assert.equal(stub.calls.length, 1);
  assert.equal(stub.calls[0].url, 'https://app.test/todos/toggle');
  assert.equal(stub.calls[0].init.method, 'POST');
  assert.equal(stub.calls[0].init.credentials, 'same-origin');
  assert.equal(stub.calls[0].init.headers['X-CSRF-Token'], 'abc');
  assert.equal(stub.calls[0].init.headers['Content-Type'], 'application/json');
  assert.deepEqual(JSON.parse(stub.calls[0].init.body), { action: 'toggle', id: '7' });
});

test('endpointRequest: no endpoint, refused origin, HTTP error and network error', async () => {
  const stub = fetchStub({ ok: true, status: 200 });
  assert.equal(screens.endpointRequest(null, {}, env(stub.fn)), null);
  const errors = [];
  assert.deepEqual(await screens.endpointRequest({ raw: 'https://evil.test/x', token: 'abc' }, {}, env(stub.fn, errors)), { ok: false, status: 0, reason: 'origin' });
  assert.equal(stub.calls.length, 0, 'the token never leaves the site');
  assert.equal(errors.length, 1);
  const noToken = fetchStub({ ok: false, status: 403 });
  assert.deepEqual(await screens.endpointRequest({ raw: '/x', token: '' }, {}, env(noToken.fn)), { ok: false, status: 403, reason: 'response' });
  assert.equal('X-CSRF-Token' in noToken.calls[0].init.headers, false);
  assert.deepEqual(await screens.endpointRequest({ raw: '/x', token: '' }, {}, env(fetchStub(new Error('offline')).fn)), { ok: false, status: 0, reason: 'response' });
});

test('settle: busy while pending, revert only on failure', async () => {
  assert.equal(screens.settle(null, {}), null);
  const log = [];
  const hooks = { busy: (b) => log.push(`busy ${b}`), revert: (r) => log.push(`revert ${r.status}`) };
  await screens.settle(Promise.resolve({ ok: true, status: 200 }), hooks);
  await screens.settle(Promise.resolve({ ok: false, status: 422 }), hooks);
  assert.deepEqual(log, ['busy true', 'busy false', 'busy true', 'busy false', 'revert 422']);
  // remove: revert without a busy hook
  const removed = [];
  await screens.settle(Promise.resolve({ ok: false, status: 0 }), { revert: () => removed.push('put back') });
  assert.deepEqual(removed, ['put back']);
  await flush();
});

test('visibleNeighbour: next visible sibling, else the previous one', () => {
  const row = (name, hidden) => ({ name, hidden, nextElementSibling: null, previousElementSibling: null });
  const rows = [row('a'), row('b', true), row('c'), row('d', true)];
  rows.forEach((r, i) => { r.nextElementSibling = rows[i + 1] || null; r.previousElementSibling = rows[i - 1] || null; });
  assert.equal(screens.visibleNeighbour(rows[0]).name, 'c', 'skips hidden rows');
  assert.equal(screens.visibleNeighbour(rows[2]).name, 'a', 'no visible next: previous');
  assert.equal(screens.visibleNeighbour(row('alone')), null);
});

test('createJsonCache parses once, clears past its limit, logs invalid JSON', () => {
  const errors = [];
  const cache = screens.createJsonCache(2, (e) => errors.push(e));
  const a = cache.parse('{"a":1}');
  assert.equal(cache.parse('{"a":1}'), a, 'cached');
  cache.parse('[1]');
  assert.equal(cache.size(), 2);
  cache.parse('[2]');
  assert.equal(cache.size(), 1, 'cleared when full');
  assert.equal(cache.parse('{bad'), null);
  assert.equal(errors.length, 1);
  assert.equal(cache.parse(''), null);
  cache.clear();
  assert.equal(cache.size(), 0);
});
