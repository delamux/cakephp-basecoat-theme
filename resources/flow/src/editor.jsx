// The flow editor: React Flow canvas, step palette and side panel. Mounted by index.jsx on every
// [data-flow-editor] rendered by FlowHelper. Styled by flow.css (plugin classes and Basecoat
// component classes only: no Tailwind utilities are generated at runtime).
import {
  createContext, useCallback, useContext, useEffect, useId, useLayoutEffect, useMemo, useRef, useState,
} from 'react';
import {
  ReactFlow, Background, Controls, MiniMap, Handle, Position,
  applyEdgeChanges, applyNodeChanges, useReactFlow,
} from '@xyflow/react';
import {
  connect, isValidConnection, nextPosition, outputsOf, serialize, stepOrder, targetOf, toReactFlow, uniqueId,
} from './model.js';

const DRAG_TYPE = 'application/x-basecoat-flow-step';
const KIND_ICONS = { trigger: 'zap', action: 'circle-check', condition: 'split' };

const DEFAULT_TEXTS = {
  'kind.trigger': 'Trigger',
  'kind.action': 'Action',
  'kind.condition': 'Condition',
  'palette.title': 'Add a step',
  'palette.hint': 'Drag a step onto the canvas, or press it to add it after the selected step.',
  'panel.steps': 'Steps',
  'panel.stepsHint': 'Choose a step to edit it.',
  'panel.empty': 'No steps yet. Add one from the palette.',
  'panel.name': 'Name',
  'panel.next': 'Next step',
  'panel.nextFor': 'Next step: {output}',
  'panel.none': 'None',
  'panel.delete': 'Delete step',
  'panel.back': 'All steps',
  'status.added': '{label} added.',
  'status.deleted': '{label} deleted.',
  'status.connected': '{source} now leads to {target}.',
  'status.disconnected': '{source} no longer leads to another step.',
  'canvas.label': 'Flow canvas',
  'canvas.edge': 'Connection from {source} to {target}',
  'canvas.edgeOutput': 'Connection from {source}, {output}, to {target}',
};

const Ctx = createContext(null);

const translator = (texts) => (key, vars = {}) => {
  const text = (texts && typeof texts[key] === 'string' ? texts[key] : DEFAULT_TEXTS[key]) || key;
  return text.replace(/\{(\w+)\}/g, (match, name) => (name in vars ? String(vars[name]) : match));
};

// Follows the `.dark` class of <html> (Basecoat's theme switch).
const useColorMode = () => {
  const read = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  const [mode, setMode] = useState(read);
  useEffect(() => {
    const observer = new MutationObserver(() => setMode(read()));
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    return () => observer.disconnect();
  }, []);
  return mode;
};

// Clones the server-rendered IconHelper SVG (template[data-flow-icons]); never parses HTML.
function Icon({ name }) {
  const { icons } = useContext(Ctx);
  const ref = useRef(null);
  useLayoutEffect(() => {
    const svg = icons.get(name);
    if (ref.current) ref.current.replaceChildren(...(svg ? [svg.cloneNode(true)] : []));
  }, [name, icons]);
  return <span ref={ref} className="bc-flow-icon" aria-hidden="true" />;
}

function StepNode({ data, selected }) {
  const { t, readonly } = useContext(Ctx);
  const outputs = Object.entries(outputsOf({ data }));
  return (
    <div className="bc-flow-node" data-kind={data.kind} data-selected={selected ? 'true' : undefined}>
      {data.kind !== 'trigger' && <Handle type="target" position={Position.Top} isConnectable={!readonly} />}
      <span className="bc-flow-node-icon"><Icon name={data.icon || KIND_ICONS[data.kind]} /></span>
      <span className="bc-flow-node-text">
        <span className="bc-flow-node-kind">{t(`kind.${data.kind}`)}</span>
        <span className="bc-flow-node-label">{data.label}</span>
      </span>
      {outputs.length
        ? outputs.map(([key, label], index) => (
          <Handle
            key={key}
            id={key}
            type="source"
            position={Position.Bottom}
            isConnectable={!readonly}
            style={{ left: `${((index + 1) / (outputs.length + 1)) * 100}%` }}
          >
            <span className="bc-flow-handle-label">{label}</span>
          </Handle>
        ))
        : <Handle type="source" position={Position.Bottom} isConnectable={!readonly} />}
    </div>
  );
}

const NODE_TYPES = { trigger: StepNode, action: StepNode, condition: StepNode };

function Palette({ templates, onAdd }) {
  const { t } = useContext(Ctx);
  const id = useId();
  const groups = [];
  templates.forEach((item) => {
    let group = groups.find((g) => g.label === item.group);
    if (!group) groups.push(group = { label: item.group, items: [] });
    group.items.push(item);
  });
  return (
    <section className="bc-flow-palette" aria-labelledby={`${id}-title`}>
      <h3 id={`${id}-title`} className="bc-flow-title">{t('palette.title')}</h3>
      <p className="bc-flow-hint">{t('palette.hint')}</p>
      {groups.map((group, g) => (
        <div key={g} className="bc-flow-palette-group" role="group" aria-labelledby={group.label ? `${id}-g${g}` : undefined}>
          {group.label && <p id={`${id}-g${g}`} className="bc-flow-kicker">{group.label}</p>}
          <ul className="bc-flow-palette-items">
            {group.items.map((item) => (
              <li key={item.index}>
                <button
                  type="button"
                  className="btn bc-flow-palette-item"
                  data-variant="outline"
                  data-size="sm"
                  draggable
                  onDragStart={(event) => {
                    event.dataTransfer.setData(DRAG_TYPE, String(item.index));
                    event.dataTransfer.effectAllowed = 'copy';
                  }}
                  onClick={() => onAdd(item)}
                >
                  <Icon name={item.icon || KIND_ICONS[item.type] || KIND_ICONS.action} />
                  <span>{item.label}</span>
                </button>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </section>
  );
}

function Panel({ nodes, edges, selected, onSelect, onDeselect, onEdit, onConnectOutput, onDelete, headingRef }) {
  const { t, readonly, fields } = useContext(Ctx);
  const id = useId();
  const ordered = useMemo(() => stepOrder(nodes, edges), [nodes, edges]);

  if (!selected) {
    return (
      <section className="bc-flow-panel" aria-labelledby={`${id}-title`}>
        <h3 id={`${id}-title`} ref={headingRef} tabIndex={-1} className="bc-flow-title">{t('panel.steps')}</h3>
        {ordered.length ? (
          <>
            <p className="bc-flow-hint">{t('panel.stepsHint')}</p>
            <ol className="bc-flow-steps">
              {ordered.map((node) => (
                <li key={node.id}>
                  <button type="button" className="btn bc-flow-step" data-variant="ghost" onClick={() => onSelect(node.id)}>
                    <Icon name={node.data.icon || KIND_ICONS[node.data.kind]} />
                    <span className="bc-flow-step-text">
                      <span className="bc-flow-step-label">{node.data.label}</span>
                      <span className="bc-flow-kicker">{t(`kind.${node.data.kind}`)}</span>
                    </span>
                  </button>
                </li>
              ))}
            </ol>
          </>
        ) : <p className="bc-flow-hint">{t('panel.empty')}</p>}
      </section>
    );
  }

  const { data } = selected;
  const outputs = Object.entries(outputsOf(selected));
  const targets = ordered.filter((node) => node.id !== selected.id && node.data.kind !== 'trigger');
  const nextSelect = (output, label) => {
    const selectId = `${id}-next-${output || 'default'}`;
    return (
      <div className="bc-flow-field" key={output || 'default'}>
        <label className="label" htmlFor={selectId}>{label}</label>
        <select
          id={selectId}
          className="select"
          value={targetOf(edges, selected.id, output) || ''}
          disabled={readonly}
          onChange={(event) => onConnectOutput(selected.id, output, event.target.value || null)}
        >
          <option value="">{t('panel.none')}</option>
          {targets.map((node) => <option key={node.id} value={node.id}>{node.data.label}</option>)}
        </select>
      </div>
    );
  };

  return (
    <section className="bc-flow-panel" aria-labelledby={`${id}-title`}>
      <div>
        <p className="bc-flow-kicker">{t(`kind.${data.kind}`)}</p>
        <h3 id={`${id}-title`} ref={headingRef} tabIndex={-1} className="bc-flow-title">{data.label}</h3>
      </div>
      <div className="bc-flow-field">
        <label className="label" htmlFor={`${id}-name`}>{t('panel.name')}</label>
        <input
          id={`${id}-name`}
          className="input"
          value={data.label}
          readOnly={readonly}
          onChange={(event) => onEdit(selected.id, { label: event.target.value })}
        />
      </div>
      {Object.entries(data.properties).map(([key, value]) => (
        <div className="bc-flow-field" key={key}>
          <label className="label" htmlFor={`${id}-p-${key}`}>{typeof fields[key] === 'string' ? fields[key] : key}</label>
          <input
            id={`${id}-p-${key}`}
            className="input"
            value={value === null || value === undefined ? '' : String(value)}
            inputMode={typeof value === 'number' ? 'numeric' : undefined}
            readOnly={readonly}
            onChange={(event) => {
              const text = event.target.value;
              const next = typeof value === 'number' && /^-?\d+(\.\d+)?$/.test(text) ? Number(text) : text;
              onEdit(selected.id, { properties: { ...data.properties, [key]: next } });
            }}
          />
        </div>
      ))}
      {outputs.length
        ? outputs.map(([key, label]) => nextSelect(key, t('panel.nextFor', { output: label })))
        : nextSelect(null, t('panel.next'))}
      <div className="bc-flow-actions">
        {!readonly && (
          <button type="button" className="btn" data-variant="destructive" data-size="sm" onClick={() => onDelete(selected.id)}>
            {t('panel.delete')}
          </button>
        )}
        <button type="button" className="btn" data-variant="outline" data-size="sm" onClick={onDeselect}>{t('panel.back')}</button>
      </div>
    </section>
  );
}

export function Editor({ initial, config, icons, input, container }) {
  const t = useMemo(() => translator(config.i18n), [config.i18n]);
  const readonly = !!config.readonly;
  const fields = config.fields && typeof config.fields === 'object' ? config.fields : {};
  const ariaLabel = useCallback((node) => `${t(`kind.${node.data.kind}`)}: ${node.data.label}`, [t]);

  const [nodes, setNodes] = useState(() => initial.nodes.map((node) => ({ ...node, ariaLabel: ariaLabel(node) })));
  const [edges, setEdges] = useState(initial.edges);
  const [status, setStatus] = useState('');
  const colorMode = useColorMode();
  const { screenToFlowPosition, setCenter, getZoom } = useReactFlow();
  const headingRef = useRef(null);
  const last = useRef(serialize(initial.nodes, initial.edges));
  const action = useRef('change');

  const templates = useMemo(() => {
    const list = [];
    (Array.isArray(config.palette) ? config.palette : []).forEach((group) => {
      (Array.isArray(group.items) ? group.items : []).forEach((item) => list.push({ ...item, group: group.label || '', index: list.length }));
    });
    return list;
  }, [config.palette]);

  const labelOf = (id) => (nodes.find((node) => node.id === id) || { data: { label: id } }).data.label;
  const announce = (text) => setStatus(text);

  // Every real change (not selection, measuring or an unfinished drag): hidden input + event.
  useEffect(() => {
    if (nodes.some((node) => node.dragging)) return;
    const json = serialize(nodes, edges);
    if (json === last.current) return;
    last.current = json;
    if (input) input.value = json;
    container.dispatchEvent(new CustomEvent('basecoat:flow-change', {
      bubbles: true,
      detail: { flow: JSON.parse(json), action: action.current },
    }));
    action.current = 'change';
  }, [nodes, edges, input, container]);

  const onNodesChange = useCallback((changes) => {
    if (changes.some((change) => change.type === 'position')) action.current = 'move';
    setNodes((current) => applyNodeChanges(changes, current));
  }, []);
  const onEdgesChange = useCallback((changes) => setEdges((current) => applyEdgeChanges(changes, current)), []);
  const onNodesDelete = (deleted) => {
    action.current = 'delete';
    announce(deleted.map((node) => t('status.deleted', { label: node.data.label })).join(' '));
  };
  const onEdgesDelete = (deleted) => {
    if (action.current !== 'delete') action.current = 'disconnect';
    if (deleted.length && !nodes.some((node) => node.selected)) {
      announce(t('status.disconnected', { source: labelOf(deleted[0].source) }));
    }
  };
  const connectOutput = (source, output, target) => {
    action.current = target ? 'connect' : 'disconnect';
    setEdges((current) => connect(current, source, output, target));
    announce(target
      ? t('status.connected', { source: labelOf(source), target: labelOf(target) })
      : t('status.disconnected', { source: labelOf(source) }));
  };
  const onConnect = (connection) => connectOutput(connection.source, connection.sourceHandle || null, connection.target);

  const addStep = (template, position = null) => {
    if (readonly) return;
    const after = position ? null : nodes.find((node) => node.selected) || null;
    const id = uniqueId(template.label, nodes.map((node) => node.id));
    const [created] = toReactFlow({
      nodes: [{ ...template, id, position: position || nextPosition(nodes, after) }],
    }).nodes;
    const node = { ...created, selected: true, ariaLabel: ariaLabel(created) };
    action.current = 'add';
    setNodes((current) => [...current.map((n) => (n.selected ? { ...n, selected: false } : n)), node]);
    if (after && node.data.kind !== 'trigger') {
      const keys = Object.keys(outputsOf(after));
      const output = keys.length ? keys.find((key) => !targetOf(edges, after.id, key)) : null;
      if (output !== undefined) {
        const previous = targetOf(edges, after.id, output);
        setEdges((current) => {
          let next = connect(current, after.id, output, id);
          // Inserted between two steps: the new step leads to the old next one.
          if (previous) next = connect(next, id, Object.keys(outputsOf(node))[0] || null, previous);
          return next;
        });
      }
    }
    announce(t('status.added', { label: node.data.label }));
  };

  const focusNode = (id) => window.requestAnimationFrame(() => {
    const el = Array.from(container.querySelectorAll('.react-flow__node')).find((n) => n.getAttribute('data-id') === id);
    if (el) el.focus();
  });
  const select = (id) => {
    const node = nodes.find((n) => n.id === id);
    if (!node) return;
    setNodes((current) => current.map((n) => (n.selected === (n.id === id) ? n : { ...n, selected: n.id === id })));
    const width = (node.measured && node.measured.width) || 0;
    const height = (node.measured && node.measured.height) || 0;
    setCenter(node.position.x + width / 2, node.position.y + height / 2, { zoom: getZoom(), duration: 200 });
    focusNode(id);
  };
  const deselect = () => {
    setNodes((current) => current.map((n) => (n.selected ? { ...n, selected: false } : n)));
    window.requestAnimationFrame(() => headingRef.current && headingRef.current.focus());
  };
  const edit = (id, patch) => {
    action.current = 'edit';
    setNodes((current) => current.map((node) => {
      if (node.id !== id) return node;
      const next = { ...node, data: { ...node.data, ...patch } };
      return { ...next, ariaLabel: ariaLabel(next) };
    }));
  };
  const remove = (id) => {
    const label = labelOf(id);
    action.current = 'delete';
    setNodes((current) => current.filter((node) => node.id !== id));
    setEdges((current) => current.filter((edge) => edge.source !== id && edge.target !== id));
    announce(t('status.deleted', { label }));
    window.requestAnimationFrame(() => headingRef.current && headingRef.current.focus());
  };

  const onDrop = (event) => {
    const index = event.dataTransfer.getData(DRAG_TYPE);
    if (index === '' || !templates[Number(index)]) return;
    event.preventDefault();
    addStep(templates[Number(index)], screenToFlowPosition({ x: event.clientX, y: event.clientY }));
  };
  const onDragOver = (event) => {
    if (!Array.from(event.dataTransfer.types).includes(DRAG_TYPE)) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'copy';
  };

  // Connections are announced with the step labels (React Flow would read the ids).
  const labelledEdges = useMemo(() => {
    const byId = new Map(nodes.map((node) => [node.id, node]));
    return edges.map((edge) => {
      const source = byId.get(edge.source);
      const target = byId.get(edge.target);
      if (!source || !target) return edge;
      const output = edge.sourceHandle ? outputsOf(source)[edge.sourceHandle] : null;
      const vars = { source: source.data.label, target: target.data.label, output };
      return { ...edge, ariaLabel: t(output ? 'canvas.edgeOutput' : 'canvas.edge', vars) };
    });
  }, [edges, nodes, t]);

  const selected = nodes.find((node) => node.selected) || null;
  const context = useMemo(() => ({ t, readonly, fields, icons }), [t, readonly, fields, icons]);
  // React Flow's texts; the live message is a template (`{direction}`, `{x}`, `{y}`) turned into its callback.
  const ariaLabelConfig = useMemo(() => {
    if (!config.ariaLabels || typeof config.ariaLabels !== 'object') return undefined;
    const labels = { ...config.ariaLabels };
    const live = labels['node.a11yDescription.ariaLiveMessage'];
    if (typeof live === 'string') {
      labels['node.a11yDescription.ariaLiveMessage'] = (vars) => live.replace(/\{(\w+)\}/g, (m, k) => (k in vars ? String(vars[k]) : m));
    }
    return labels;
  }, [config.ariaLabels]);

  return (
    <Ctx.Provider value={context}>
      <div className="bc-flow" data-readonly={readonly ? 'true' : undefined}>
        {!readonly && templates.length > 0 && <Palette templates={templates} onAdd={(item) => addStep(item)} />}
        <div className="bc-flow-canvas" role="region" aria-label={t('canvas.label')}>
          <ReactFlow
            nodes={nodes}
            edges={labelledEdges}
            nodeTypes={NODE_TYPES}
            onNodesChange={onNodesChange}
            onEdgesChange={onEdgesChange}
            onNodesDelete={onNodesDelete}
            onEdgesDelete={onEdgesDelete}
            onConnect={onConnect}
            isValidConnection={(connection) => isValidConnection(connection, nodes)}
            onDrop={onDrop}
            onDragOver={onDragOver}
            nodesDraggable={!readonly}
            nodesConnectable={!readonly}
            edgesReconnectable={false}
            deleteKeyCode={readonly ? null : ['Backspace', 'Delete']}
            defaultEdgeOptions={{ type: 'smoothstep' }}
            fitView
            fitViewOptions={{ padding: 0.2, maxZoom: 1 }}
            minZoom={0.25}
            maxZoom={2}
            colorMode={colorMode}
            ariaLabelConfig={ariaLabelConfig}
          >
            <Background gap={20} />
            <Controls showInteractive={false} />
            <MiniMap pannable zoomable />
          </ReactFlow>
        </div>
        <Panel
          nodes={nodes}
          edges={edges}
          selected={selected}
          headingRef={headingRef}
          onSelect={select}
          onDeselect={deselect}
          onEdit={edit}
          onConnectOutput={connectOutput}
          onDelete={remove}
        />
        <p className="bc-flow-sr" aria-live="polite">{status}</p>
      </div>
    </Ctx.Provider>
  );
}
