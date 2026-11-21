// Pure data functions of the flow editor (no DOM, no React): converting between the PHP format of
// FlowHelper and React Flow's nodes / edges, ids, positions and step order.
// Tested with `node --test` (tests/js/flow-model.test.js).

export const KINDS = ['trigger', 'action', 'condition'];
export const DEFAULT_OUTPUTS = { yes: 'Yes', no: 'No' };
// Vertical distance between a step and the one added after it, and horizontal shift when taken.
export const STEP_GAP_Y = 160;
export const STEP_GAP_X = 280;

const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
const toText = (value) => (value === null || value === undefined ? '' : String(value));

/** Outputs of a node: `{key: label}`; a condition defaults to yes / no, other kinds have none (one unnamed output). */
export const outputsOf = (node) => {
  const kind = node.data ? node.data.kind : node.type;
  if (kind !== 'condition') return {};
  const outputs = node.data ? node.data.outputs : node.outputs;
  return isObject(outputs) && Object.keys(outputs).length ? { ...outputs } : { ...DEFAULT_OUTPUTS };
};

/** Edge id from its ends (an edge is unique per source output). */
export const edgeId = (source, output, target) => `${source}${output ? `:${output}` : ''}->${target}`;

/** A React Flow edge; condition outputs use the handle id `output`. */
export const toEdge = (source, output, target, id = null) => ({
  id: id || edgeId(source, output, target),
  source,
  target,
  sourceHandle: output || null,
});

/** PHP format → React Flow `{nodes, edges}`. */
export const toReactFlow = (flow) => {
  const nodes = (Array.isArray(flow && flow.nodes) ? flow.nodes : []).map((node) => {
    const kind = KINDS.includes(node.type) ? node.type : 'action';
    const data = {
      kind,
      label: toText(node.label),
      icon: node.icon ? String(node.icon) : null,
      properties: isObject(node.properties) ? { ...node.properties } : {},
    };
    if (kind === 'condition') data.outputs = outputsOf({ type: kind, outputs: node.outputs });
    const position = isObject(node.position) ? node.position : {};
    return {
      id: String(node.id),
      type: kind,
      position: { x: Number(position.x) || 0, y: Number(position.y) || 0 },
      data,
      ariaLabel: data.label,
    };
  });
  const edges = (Array.isArray(flow && flow.edges) ? flow.edges : []).map((edge) => toEdge(
    String(edge.source),
    edge.output ? String(edge.output) : null,
    String(edge.target),
    edge.id ? String(edge.id) : null,
  ));
  return { nodes, edges };
};

/** React Flow `{nodes, edges}` → PHP format (positions rounded, runtime keys dropped). */
export const fromReactFlow = (nodes, edges) => ({
  nodes: nodes.map((node) => {
    const out = { id: node.id, type: node.data.kind, label: node.data.label };
    if (node.data.icon) out.icon = node.data.icon;
    out.position = { x: Math.round(node.position.x), y: Math.round(node.position.y) };
    out.properties = { ...node.data.properties };
    if (node.data.kind === 'condition') out.outputs = outputsOf(node);
    return out;
  }),
  edges: edges.map((edge) => {
    const out = { id: edge.id, source: edge.source };
    if (edge.sourceHandle) out.output = edge.sourceHandle;
    out.target = edge.target;
    return out;
  }),
});

/** A connection is valid when it links two different nodes and does not end on a trigger. */
export const isValidConnection = (connection, nodes) => {
  if (!connection.source || !connection.target || connection.source === connection.target) return false;
  const target = nodes.find((node) => node.id === connection.target);
  return !!target && target.data.kind !== 'trigger';
};

/**
 * Sets where an output leads: one edge per source output (a new target replaces the old one,
 * `target` null removes it). Returns a new edge list.
 */
export const connect = (edges, source, output, target) => {
  const handle = output || null;
  const kept = edges.filter((edge) => !(edge.source === source && (edge.sourceHandle || null) === handle));
  return target ? [...kept, toEdge(source, handle, target)] : kept;
};

/** Target of an output, or null. */
export const targetOf = (edges, source, output) => {
  const edge = edges.find((e) => e.source === source && (e.sourceHandle || null) === (output || null));
  return edge ? edge.target : null;
};

/** Unique node id from a label: `send-email`, `send-email-2`… */
export const uniqueId = (label, existing) => {
  const base = toText(label)
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'step';
  const taken = new Set(existing);
  if (!taken.has(base)) return base;
  let n = 2;
  while (taken.has(`${base}-${n}`)) n++;
  return `${base}-${n}`;
};

/**
 * Position for a step added after `after` (or below the lowest step when null): one gap below,
 * shifted right while another step is already there.
 */
export const nextPosition = (nodes, after) => {
  let x;
  let y;
  if (after) {
    ({ x } = after.position);
    y = after.position.y + STEP_GAP_Y;
  } else if (nodes.length) {
    const lowest = nodes.reduce((a, b) => (b.position.y > a.position.y ? b : a));
    ({ x } = lowest.position);
    y = lowest.position.y + STEP_GAP_Y;
  } else {
    return { x: 0, y: 0 };
  }
  const near = (node) => Math.abs(node.position.x - x) < STEP_GAP_X / 2 && Math.abs(node.position.y - y) < STEP_GAP_Y / 2;
  while (nodes.some(near)) x += STEP_GAP_X;
  return { x, y };
};

/**
 * Reading order of the steps: breadth-first from the triggers (outputs in their declared order),
 * then every unreached step top to bottom, left to right. Same order as FlowHelper's fallback list.
 */
export const stepOrder = (nodes, edges) => {
  const byPosition = [...nodes].sort((a, b) => a.position.y - b.position.y || a.position.x - b.position.x);
  const byId = new Map(nodes.map((node) => [node.id, node]));
  const seen = new Set();
  const order = [];
  const outgoing = (node) => {
    const keys = Object.keys(outputsOf(node));
    const rank = (edge) => {
      const i = keys.indexOf(edge.sourceHandle || '');
      return i < 0 ? keys.length : i;
    };
    return edges.filter((edge) => edge.source === node.id).sort((a, b) => rank(a) - rank(b));
  };
  const visit = (start) => {
    const queue = [start];
    while (queue.length) {
      const node = queue.shift();
      if (seen.has(node.id)) continue;
      seen.add(node.id);
      order.push(node);
      outgoing(node).forEach((edge) => {
        const next = byId.get(edge.target);
        if (next && !seen.has(next.id)) queue.push(next);
      });
    }
  };
  byPosition.filter((node) => node.data.kind === 'trigger').forEach((node) => { if (!seen.has(node.id)) visit(node); });
  byPosition.forEach((node) => { if (!seen.has(node.id)) visit(node); });
  return order;
};

/** Stable JSON of the PHP format, to detect real changes (selecting or measuring is not a change). */
export const serialize = (nodes, edges) => JSON.stringify(fromReactFlow(nodes, edges));
