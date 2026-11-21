'use strict';
// Unit tests for the pure data functions of the flow editor (resources/flow/src/model.js, an ES module
// bundled into webroot/js/basecoat-flow.js). Run: composer test-js.
const test = require('node:test');
const assert = require('node:assert/strict');

const load = () => import('../../resources/flow/src/model.js');

// Same flow as FlowHelperTest: trigger → condition (yes / no), positions out of reading order.
const php = () => ({
  nodes: [
    { id: 'no-path', type: 'action', label: 'Add to list', icon: 'list-plus', position: { x: 160, y: 300 }, properties: {} },
    { id: 'start', type: 'trigger', label: 'Signed up', position: { x: 0, y: 0 }, properties: { event: 'customer.created' } },
    { id: 'plan', type: 'condition', label: 'Plan?', position: { x: 0, y: 150 }, properties: { days: 3 }, outputs: { yes: 'Yes', no: 'No' } },
    { id: 'yes-path', type: 'action', label: 'Assign manager', position: { x: -160, y: 300 }, properties: {} },
  ],
  edges: [
    { id: 'plan:no->no-path', source: 'plan', output: 'no', target: 'no-path' },
    { id: 'start->plan', source: 'start', target: 'plan' },
    { id: 'plan:yes->yes-path', source: 'plan', output: 'yes', target: 'yes-path' },
  ],
});

test('toReactFlow and fromReactFlow round-trip the PHP format', async () => {
  const m = await load();
  const { nodes, edges } = m.toReactFlow(php());
  assert.equal(nodes[2].type, 'condition');
  assert.deepEqual(nodes[2].data.outputs, { yes: 'Yes', no: 'No' });
  assert.equal(edges[0].sourceHandle, 'no');
  assert.equal(edges[1].sourceHandle, null);
  assert.deepEqual(m.fromReactFlow(nodes, edges), php());
});

test('toReactFlow tolerates missing parts and unknown types', async () => {
  const m = await load();
  const { nodes, edges } = m.toReactFlow({ nodes: [{ id: 7, type: 'loop', label: null }] });
  assert.equal(nodes[0].id, '7');
  assert.equal(nodes[0].type, 'action');
  assert.deepEqual(nodes[0].position, { x: 0, y: 0 });
  assert.deepEqual(nodes[0].data.properties, {});
  assert.deepEqual(edges, []);
  assert.deepEqual(m.toReactFlow(null), { nodes: [], edges: [] });
});

test('a condition without outputs gets yes / no', async () => {
  const m = await load();
  assert.deepEqual(m.outputsOf({ type: 'condition' }), m.DEFAULT_OUTPUTS);
  assert.deepEqual(m.outputsOf({ type: 'action', outputs: { a: 'A' } }), {});
});

test('fromReactFlow rounds positions after dragging', async () => {
  const m = await load();
  const { nodes, edges } = m.toReactFlow(php());
  nodes[0].position = { x: 160.4, y: 299.6 };
  assert.deepEqual(m.fromReactFlow(nodes, edges).nodes[0].position, { x: 160, y: 300 });
});

test('connect keeps one edge per output, replaces and removes', async () => {
  const m = await load();
  let { edges } = m.toReactFlow(php());
  edges = m.connect(edges, 'plan', 'yes', 'no-path');
  assert.equal(m.targetOf(edges, 'plan', 'yes'), 'no-path');
  assert.equal(edges.filter((e) => e.source === 'plan' && e.sourceHandle === 'yes').length, 1);
  assert.equal(edges.find((e) => e.sourceHandle === 'yes').id, 'plan:yes->no-path');
  edges = m.connect(edges, 'plan', 'yes', null);
  assert.equal(m.targetOf(edges, 'plan', 'yes'), null);
  assert.equal(m.targetOf(edges, 'plan', 'no'), 'no-path', 'Other outputs are untouched');
  edges = m.connect(edges, 'start', null, 'yes-path');
  assert.equal(m.targetOf(edges, 'start', null), 'yes-path');
  assert.equal(edges.filter((e) => e.source === 'start').length, 1);
});

test('isValidConnection rejects loops, unknown nodes and triggers as targets', async () => {
  const m = await load();
  const { nodes } = m.toReactFlow(php());
  assert.equal(m.isValidConnection({ source: 'plan', target: 'no-path' }, nodes), true);
  assert.equal(m.isValidConnection({ source: 'plan', target: 'plan' }, nodes), false);
  assert.equal(m.isValidConnection({ source: 'plan', target: 'start' }, nodes), false);
  assert.equal(m.isValidConnection({ source: 'plan', target: 'ghost' }, nodes), false);
});

test('uniqueId slugifies labels and avoids taken ids', async () => {
  const m = await load();
  assert.equal(m.uniqueId('Send email', []), 'send-email');
  assert.equal(m.uniqueId('Send email', ['send-email', 'send-email-2']), 'send-email-3');
  assert.equal(m.uniqueId('Añadir a la lista', []), 'anadir-a-la-lista');
  assert.equal(m.uniqueId('  ¿? ', []), 'step');
});

test('nextPosition goes below and shifts right while taken', async () => {
  const m = await load();
  const { nodes } = m.toReactFlow(php());
  const start = nodes.find((n) => n.id === 'start');
  assert.deepEqual(m.nextPosition(nodes, start), { x: 280, y: 160 }, 'plan sits right below start');
  assert.deepEqual(m.nextPosition(nodes, null), { x: 160, y: 460 }, 'below the lowest step');
  assert.deepEqual(m.nextPosition([], null), { x: 0, y: 0 });
});

test('stepOrder is breadth-first from the triggers, outputs in order, then the rest', async () => {
  const m = await load();
  const { nodes, edges } = m.toReactFlow(php());
  assert.deepEqual(m.stepOrder(nodes, edges).map((n) => n.id), ['start', 'plan', 'yes-path', 'no-path']);
  const orphan = { id: 'orphan', type: 'action', position: { x: 0, y: -50 }, data: { kind: 'action', label: 'Orphan', properties: {} } };
  assert.deepEqual(m.stepOrder([...nodes, orphan], edges).map((n) => n.id), ['start', 'plan', 'yes-path', 'no-path', 'orphan']);
});

test('serialize ignores runtime state (selection, measuring)', async () => {
  const m = await load();
  const { nodes, edges } = m.toReactFlow(php());
  const before = m.serialize(nodes, edges);
  const touched = nodes.map((n) => ({ ...n, selected: true, measured: { width: 240, height: 60 } }));
  assert.equal(m.serialize(touched, edges), before);
});

test('toReactFlow remembers which properties are numbers', async () => {
  const m = await load();
  const { nodes } = m.toReactFlow({ nodes: [{ id: 'w', type: 'action', properties: { days: 3, to: 'Ana', n: '4' } }] });
  assert.deepEqual(nodes[0].data.numeric, ['days']);
  assert.equal('numeric' in m.fromReactFlow(nodes, []).nodes[0], false, 'Runtime only, not saved');
});
