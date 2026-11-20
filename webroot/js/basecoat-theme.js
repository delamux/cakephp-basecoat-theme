/*
 * BasecoatTheme (CakePHP plugin) runtime glue.
 * - Initializes charts rendered by ChartHelper (canvas[data-basecoat-chart]).
 * - Wires theme toggles rendered by ThemeHelper::toggle() ([data-theme-toggle]).
 * - Sidebar toggle ([data-sidebar-toggle]), dialogs ([data-dialog-open], [data-dialog-close],
 *   [data-dialog-backdrop-close]), dismissible alerts ([data-alert-dismiss]) and flash toasts
 *   (template[data-basecoat-toast]): event delegation, no inline JS (CSP without 'unsafe-inline').
 * - Progress bars ([data-progress-value]: width via CSSOM) and "Back" links ([data-history-back]).
 * - Command menu: [data-command-open] trigger, Ctrl/Cmd + dialog[data-command-hotkey], backdrop close.
 * - Date range field: preset buttons ([data-range-from]/[data-range-to]) and trigger text ([data-range-label]).
 * - Workflow designer ([data-workflow-designer], `workflow` bake screen): mounts
 *   sequential-workflow-designer when the page loaded it, follows the light/dark theme.
 * - Re-initializes charts, toasts, progress bars and workflow designers in content swapped by htmx (htmx:load).
 * Requires Basecoat JS (and Chart.js + Basecoat chart.js for charts) to be loaded first.
 */
(() => {
  // Loaded twice (e.g. the script tag repeated in a page)? Keep the first instance only, so the
  // delegated listeners below are registered once and a toggle or hotkey fires once.
  if (window.BasecoatTheme) return;
  window.BasecoatTheme = {};

  const parse = (value) => {
    if (!value) return undefined;
    try { return JSON.parse(value); } catch (e) { console.error('[BasecoatTheme] Invalid JSON', e); return undefined; }
  };

  const initCharts = (root = document) => {
    if (!window.basecoat || typeof window.basecoat.chart !== 'function') return;
    root.querySelectorAll('canvas[data-basecoat-chart]:not([data-chart-initialized])').forEach((canvas) => {
      const config = {};
      const options = parse(canvas.dataset.chartOptions);
      if (options) config.options = options;
      try { window.basecoat.chart(canvas, config); } catch (e) { console.error('[BasecoatTheme] Chart error', e); }
    });
  };

  const toggleTheme = () => {
    if (window.basecoat && window.basecoat.theme && typeof window.basecoat.theme.toggle === 'function') {
      window.basecoat.theme.toggle();
      return;
    }
    const dark = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('themeMode', dark ? 'dark' : 'light'); } catch (e) { /* storage disabled */ }
    document.dispatchEvent(new CustomEvent('basecoat:themechange', { detail: { mode: dark ? 'dark' : 'light' } }));
  };

  // Flash toasts rendered as <template data-basecoat-toast data-toaster="id">: move them into the toaster
  const initToasts = (root = document) => {
    root.querySelectorAll('template[data-basecoat-toast]').forEach((tpl) => {
      const toaster = document.getElementById(tpl.dataset.toaster || 'toaster');
      if (!toaster) return;
      toaster.appendChild(tpl.content.cloneNode(true));
      tpl.remove();
    });
    if (window.basecoat && typeof window.basecoat.init === 'function') window.basecoat.init('toast');
  };

  const initProgress = (root = document) => {
    root.querySelectorAll('[data-progress-value]').forEach((bar) => {
      bar.style.width = `${parseFloat(bar.dataset.progressValue) || 0}%`;
    });
  };

  let backdropPress = null;
  document.addEventListener('pointerdown', (event) => {
    backdropPress = event.target instanceof HTMLDialogElement ? event.target : null;
  });

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;
    const toggle = target.closest('[data-theme-toggle]');
    if (toggle) {
      event.preventDefault();
      toggleTheme();
      return;
    }
    const sidebar = target.closest('[data-sidebar-toggle]');
    if (sidebar) {
      document.getElementById(sidebar.dataset.sidebarToggle)?.toggle?.();
      return;
    }
    const open = target.closest('[data-dialog-open]');
    if (open) {
      document.getElementById(open.dataset.dialogOpen)?.showModal?.();
      return;
    }
    if (target.closest('[data-dialog-close]')) {
      target.closest('dialog')?.close();
      return;
    }
    const back = target.closest('[data-history-back]');
    if (back && window.history.length > 1) {
      event.preventDefault();
      window.history.back();
      return;
    }
    if (target.closest('[data-alert-dismiss]')) {
      target.closest('.alert')?.remove();
      return;
    }
    const command = target.closest('[data-command-open]');
    if (command) {
      const dialog = document.getElementById(command.dataset.commandOpen);
      if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
      return;
    }
    // Date range preset: fill both inputs, update the trigger text and close the popover
    const preset = target.closest('[data-range-from]');
    if (preset) {
      const root = preset.closest('.popover');
      const inputs = root ? root.querySelectorAll('input[type="date"]') : [];
      if (inputs.length < 2) return;
      inputs[0].value = preset.dataset.rangeFrom || '';
      inputs[1].value = preset.dataset.rangeTo || '';
      inputs[0].dispatchEvent(new Event('change', { bubbles: true }));
      // Closing relies on Basecoat's popover markup and state: the panel is the direct child
      // `[data-popover]` (hidden with aria-hidden="true") and the direct child button carries
      // aria-expanded. Keep in sync with DateRangeFieldHelper and Basecoat's popover.js.
      const panel = root.querySelector(':scope > [data-popover]');
      const trigger = root.querySelector(':scope > button');
      if (panel) panel.setAttribute('aria-hidden', 'true');
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
      }
      return;
    }
    // Backdrop click (press and release on the dialog element itself, not its content)
    if (target instanceof HTMLDialogElement && target.matches('[data-dialog-backdrop-close], .command-dialog') && backdropPress === target) {
      target.close();
    }
  });

  // Command menu hotkey: Ctrl/Cmd + key toggles dialog[data-command-hotkey]
  document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.altKey || event.shiftKey) return;
    const key = event.key?.toLowerCase();
    if (!key) return;
    const dialog = Array.from(document.querySelectorAll('dialog[data-command-hotkey]'))
      .find((d) => (d.dataset.commandHotkey || '').toLowerCase() === key);
    if (!dialog) return;
    event.preventDefault();
    if (dialog.open) dialog.close();
    else if (typeof dialog.showModal === 'function') dialog.showModal();
  });

  // Date range field: keep the trigger text in sync with the inputs
  document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'date') return;
    const root = input.closest('.popover');
    const label = root && root.querySelector('[data-range-label]');
    if (!label) return;
    const [from, to] = root.querySelectorAll('input[type="date"]');
    label.textContent = (from.value || to.value)
      ? `${from.value || '…'} – ${to.value || '…'}`
      : (label.dataset.placeholder || '');
  });

  // Workflow designer: [data-workflow-designer] with a `script[data-workflow-definition]` and a
  // `script[data-workflow-config]` JSON data block, a `[data-workflow-canvas]` placeholder, an optional
  // `input[data-workflow-input]` (kept in sync with the definition) and a `[data-workflow-fallback]`
  // (hidden once mounted). Needs sequential-workflow-designer (global `sequentialWorkflowDesigner`);
  // without it nothing changes. Editors are DOM built with Basecoat classes (text via textContent).
  const workflows = [];
  const workflowTheme = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  let workflowFieldId = 0;

  const node = (tag, className, text) => {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  };

  // Plain objects for `properties` / `branches` (PHP encodes an empty array as `[]`)
  const asObject = (value) => (value && typeof value === 'object' && !Array.isArray(value) ? value : {});
  const normalizeSteps = (steps) => (Array.isArray(steps) ? steps : []).map((step) => {
    const copy = Object.assign({}, step, { properties: asObject(step.properties) });
    if (step.branches !== undefined) {
      const branches = {};
      Object.keys(asObject(step.branches)).forEach((name) => { branches[name] = normalizeSteps(step.branches[name]); });
      copy.branches = branches;
    }
    return copy;
  });

  const workflowField = (labelText, control) => {
    const field = node('div', 'field');
    field.setAttribute('role', 'group');
    const id = `workflow-field-${++workflowFieldId}`;
    control.id = id;
    const label = node('label', 'label', labelText);
    label.htmlFor = id;
    field.append(label, control);
    return field;
  };

  const workflowInput = (value, readonly, onInput) => {
    const input = node('input', 'input');
    input.type = typeof value === 'number' ? 'number' : 'text';
    input.value = value === undefined || value === null ? '' : String(value);
    input.disabled = readonly;
    // An emptied number field stays '' (Number('') would be 0)
    input.addEventListener('input', () => onInput(input.type === 'number' && input.value !== '' ? Number(input.value) : input.value));
    return input;
  };

  const mountWorkflow = (container) => {
    const lib = window.sequentialWorkflowDesigner;
    const canvas = container.querySelector('[data-workflow-canvas]');
    const definitionBlock = container.querySelector('script[data-workflow-definition]');
    if (!lib || !lib.Designer || !canvas || !definitionBlock) return;
    const raw = parse(definitionBlock.textContent);
    if (!raw) return;
    const config = parse(container.querySelector('script[data-workflow-config]')?.textContent) || {};
    const fallback = container.querySelector('[data-workflow-fallback]');
    const input = container.querySelector('input[data-workflow-input]');
    const i18n = (key, fallbackText) => (config.i18n && typeof config.i18n[key] === 'string' ? config.i18n[key] : fallbackText);
    const fields = asObject(config.fields);
    const state = {
      container,
      designer: null,
      theme: null,
      definition: { properties: asObject(raw.properties), sequence: normalizeSteps(raw.sequence) },
    };

    // Step list of the workflow editor: keyboard path to every step (the canvas needs a pointer)
    const stepButtons = (steps) => {
      const list = node('ul');
      steps.forEach((step) => {
        const item = node('li');
        const button = node('button', 'btn', step.name || step.type);
        button.type = 'button';
        button.dataset.variant = 'ghost';
        button.dataset.size = 'sm';
        button.addEventListener('click', () => {
          state.designer?.selectStepById(step.id);
          state.designer?.moveViewportToStep(step.id);
        });
        item.append(button);
        Object.keys(step.branches || {}).forEach((branch) => {
          item.append(node('div', 'workflow-editor-branch', branch));
          if (step.branches[branch].length) item.append(stepButtons(step.branches[branch]));
        });
        list.append(item);
      });
      return list;
    };

    const rootEditorProvider = (definition, context, readonly) => {
      const editor = node('div', 'workflow-editor');
      editor.append(node('h3', 'workflow-editor-title', i18n('editor.workflow', 'Workflow')));
      const props = definition.properties;
      editor.append(workflowField(i18n('editor.name', 'Name'), workflowInput(props.name, readonly, (value) => {
        props.name = value;
        context.notifyPropertiesChanged();
      })));
      const description = node('textarea', 'textarea');
      description.value = props.description || '';
      description.disabled = readonly;
      description.addEventListener('input', () => {
        props.description = description.value;
        context.notifyPropertiesChanged();
      });
      editor.append(workflowField(i18n('editor.description', 'Description'), description));
      const nav = node('nav');
      nav.setAttribute('aria-label', i18n('editor.steps', 'Steps'));
      nav.append(node('h4', 'workflow-editor-title', i18n('editor.steps', 'Steps')));
      if (definition.sequence.length) {
        nav.append(node('p', 'workflow-editor-hint', i18n('editor.stepsHint', 'Choose a step to edit it.')));
        const list = stepButtons(definition.sequence);
        list.className = 'workflow-editor-steps';
        nav.append(list);
      } else {
        nav.append(node('p', 'workflow-editor-hint', i18n('editor.noSteps', 'Drag a step from the toolbox onto the canvas.')));
      }
      editor.append(nav);
      return editor;
    };

    const stepEditorProvider = (step, context, definition, readonly) => {
      const editor = node('div', 'workflow-editor');
      const heading = node('div');
      heading.append(node('p', 'workflow-editor-kicker', i18n('editor.step', 'Step')), node('h3', 'workflow-editor-title', step.name));
      editor.append(heading);
      editor.append(workflowField(i18n('editor.stepName', 'Step name'), workflowInput(step.name, readonly, (value) => {
        step.name = value;
        heading.lastChild.textContent = value;
        context.notifyNameChanged();
      })));
      step.properties = asObject(step.properties);
      Object.keys(step.properties).forEach((key) => {
        const value = step.properties[key];
        if (value !== null && typeof value === 'object') return;
        editor.append(workflowField(typeof fields[key] === 'string' ? fields[key] : key, workflowInput(value, readonly, (next) => {
          step.properties[key] = next;
          context.notifyPropertiesChanged();
        })));
      });
      const back = node('button', 'btn', i18n('editor.back', 'Back to workflow'));
      back.type = 'button';
      back.dataset.variant = 'outline';
      back.dataset.size = 'sm';
      back.addEventListener('click', () => state.designer?.clearSelectedStep());
      editor.append(back);
      return editor;
    };

    // The library renders its controls as <div>s: name them and make them reachable by keyboard.
    const enhance = () => {
      const search = canvas.querySelector('.sqd-toolbox-filter-input');
      if (search) {
        search.setAttribute('aria-label', i18n('toolbox.search', 'Search steps'));
        search.name = 'workflow-step-search';
        search.type = 'search';
      }
      canvas.querySelectorAll('.sqd-control-bar-button, .sqd-smart-editor-toggle, .sqd-toolbox-header').forEach((control) => {
        control.setAttribute('role', 'button');
        control.tabIndex = 0;
        const name = control.getAttribute('title') || control.textContent.trim();
        if (name) control.setAttribute('aria-label', name);
      });
    };

    const build = (theme) => lib.Designer.create(canvas, state.definition, {
      theme,
      undoStackSize: 20,
      toolbox: { groups: (Array.isArray(config.toolbox) ? config.toolbox : []).map((group) => ({ name: group.name, steps: normalizeSteps(group.steps) })) },
      steps: { iconUrlProvider: () => null },
      controlBar: true,
      contextMenu: true,
      // Delete / Backspace remove the selected step only when the focus is not in another control
      keyboard: {
        canHandleKey: () => {
          const active = document.activeElement;
          return !active || active === document.body || !!active.closest('.sqd-workspace');
        },
      },
      editors: { rootEditorProvider, stepEditorProvider },
      i18n,
    });

    const attach = (designer, theme) => {
      state.designer = designer;
      state.theme = theme;
      designer.onDefinitionChanged.subscribe((event) => {
        state.definition = event.definition;
        if (input) input.value = JSON.stringify(event.definition);
      });
      enhance();
      designer.onIsToolboxCollapsedChanged.subscribe(enhance);
      designer.onIsEditorCollapsedChanged.subscribe(enhance);
    };

    // First mount: on failure the server-rendered list stays.
    const create = () => {
      const theme = workflowTheme();
      canvas.hidden = false;
      let designer;
      try {
        designer = build(theme);
      } catch (e) {
        console.error('[BasecoatTheme] Workflow designer error', e);
        canvas.querySelectorAll(':scope > .sqd-designer').forEach((el) => el.remove());
        canvas.hidden = true;
        return;
      }
      if (fallback) fallback.hidden = true;
      container.querySelectorAll('[data-workflow-hint]').forEach((hint) => { hint.hidden = false; });
      container.dataset.workflowMounted = 'true';
      attach(designer, theme);
    };

    // Enter / Space on the controls made focusable by enhance()
    canvas.addEventListener('keydown', (event) => {
      const control = event.target instanceof Element ? event.target.closest('.sqd-designer [role="button"]') : null;
      if (!control || (event.key !== 'Enter' && event.key !== ' ')) return;
      event.preventDefault();
      control.click();
    });

    // Theme switch: build the new designer next to the old one and swap; if it throws, keep the old one.
    state.recreate = () => {
      const theme = workflowTheme();
      if (!state.designer || state.theme === theme) return;
      const previous = state.designer;
      const previousRoot = canvas.querySelector(':scope > .sqd-designer');
      state.definition = previous.getDefinition();
      let designer;
      try {
        designer = build(theme);
      } catch (e) {
        console.error('[BasecoatTheme] Workflow designer error', e);
        canvas.querySelectorAll(':scope > .sqd-designer').forEach((el) => { if (el !== previousRoot) el.remove(); });
        state.theme = theme; // do not retry on every class change; the previous designer keeps working
        return;
      }
      previous.destroy();
      attach(designer, theme);
    };
    state.destroy = () => {
      state.designer?.destroy();
      state.designer = null;
    };
    create();
    if (state.designer) workflows.push(state);
  };

  // Designers whose container left the document (htmx swaps, removed nodes): destroy them.
  const pruneWorkflows = (removed) => {
    for (let i = workflows.length - 1; i >= 0; i--) {
      const { container } = workflows[i];
      if (!container.isConnected || (removed && (removed === container || removed.contains(container)))) {
        workflows[i].destroy();
        workflows.splice(i, 1);
      }
    }
  };

  const initWorkflows = (root = document) => {
    pruneWorkflows();
    if (!window.sequentialWorkflowDesigner) return;
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const found = Array.from(scope.querySelectorAll('[data-workflow-designer]:not([data-workflow-mounted])'));
    if (scope instanceof Element && scope.matches('[data-workflow-designer]:not([data-workflow-mounted])')) found.unshift(scope);
    found.forEach((container) => {
      if (workflows.some((s) => s.container === container)) return;
      try { mountWorkflow(container); } catch (e) { console.error('[BasecoatTheme] Workflow designer error', e); }
    });
  };

  // The designer has its own light / dark theme: re-create it when `.dark` changes on <html>.
  new MutationObserver(() => {
    pruneWorkflows();
    workflows.forEach((state) => state.recreate());
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  const boot = () => { initCharts(); initToasts(); initProgress(); initWorkflows(); };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
    // Deferred libraries placed after this script (e.g. the workflow designer) run before DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => initWorkflows());
  }
  window.addEventListener('load', boot);
  // htmx: content swapped in
  document.addEventListener('htmx:load', (event) => {
    initCharts(event.target);
    initToasts(event.target);
    initProgress(event.target);
    initWorkflows(event.target);
  });
  document.addEventListener('htmx:beforeCleanupElement', (event) => {
    if (workflows.length && event.target instanceof Element) pruneWorkflows(event.target);
  });

  Object.assign(window.BasecoatTheme, { initCharts, initToasts, initProgress, initWorkflows, toggleTheme });
})();
