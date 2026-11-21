// Entry of webroot/js/basecoat-flow.js: mounts the flow editor on every [data-flow-editor] rendered
// by FlowHelper (on load, on htmx:load and through window.BasecoatFlow.init(root)) and unmounts
// editors whose container leaves the page. If anything fails, the server-rendered list stays.
import { createRoot } from 'react-dom/client';
import { ReactFlowProvider } from '@xyflow/react';
import { Editor } from './editor.jsx';
import { toReactFlow } from './model.js';

(() => {
  if (window.BasecoatFlow) return;

  const roots = new Map();

  const parse = (el) => {
    if (!el) return null;
    try {
      return JSON.parse(el.textContent);
    } catch (e) {
      return null;
    }
  };

  // Unmounts an editor and shows the server-rendered list again.
  const unmount = (container) => {
    const root = roots.get(container);
    if (!root) return;
    roots.delete(container);
    root.unmount();
    delete container.dataset.flowMounted;
    const canvas = container.querySelector('[data-flow-canvas]');
    const fallback = container.querySelector('[data-flow-fallback]');
    if (canvas) canvas.hidden = true;
    if (fallback) fallback.hidden = false;
  };

  const mount = (container) => {
    // A failed editor is not retried (it would fail and flicker on every htmx:load).
    if (roots.has(container) || container.dataset.flowFailed === 'true') return;
    const data = parse(container.querySelector('script[data-flow-data]'));
    const config = parse(container.querySelector('script[data-flow-config]')) || {};
    const canvas = container.querySelector('[data-flow-canvas]');
    if (!data || !canvas) return;
    const fallback = container.querySelector('[data-flow-fallback]');
    const icons = new Map();
    const template = container.querySelector('template[data-flow-icons]');
    if (template) template.content.querySelectorAll('svg[data-icon]').forEach((svg) => icons.set(svg.getAttribute('data-icon'), svg));

    const fail = (error) => {
      console.error('[BasecoatTheme] Flow editor error', error);
      container.dataset.flowFailed = 'true';
      window.setTimeout(() => {
        unmount(container);
        canvas.hidden = true;
        if (fallback) fallback.hidden = false;
      });
    };

    try {
      const root = createRoot(canvas, { onUncaughtError: fail });
      roots.set(container, root);
      container.dataset.flowMounted = 'true';
      canvas.hidden = false;
      if (fallback) fallback.hidden = true;
      root.render(
        <ReactFlowProvider>
          <Editor
            initial={toReactFlow(data)}
            config={config}
            icons={icons}
            input={container.querySelector('input[data-flow-input]')}
            container={container}
          />
        </ReactFlowProvider>,
      );
    } catch (error) {
      fail(error);
    }
  };

  // Mounts the editors inside `root` (and `root` itself); unmounts the ones no longer in the page.
  const init = (root = document) => {
    roots.forEach((r, container) => { if (!container.isConnected) unmount(container); });
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const found = Array.from(scope.querySelectorAll('[data-flow-editor]'));
    if (scope instanceof Element && scope.matches('[data-flow-editor]')) found.unshift(scope);
    found.forEach(mount);
  };

  window.BasecoatFlow = { init, mount, unmount };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
  } else {
    init();
  }
  document.addEventListener('htmx:load', (event) => init(event.target));
  document.addEventListener('htmx:beforeCleanupElement', (event) => {
    if (!(event.target instanceof Element)) return;
    roots.forEach((r, container) => {
      if (event.target === container || event.target.contains(container)) unmount(container);
    });
  });
})();
