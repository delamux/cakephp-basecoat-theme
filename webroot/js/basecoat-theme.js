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
 * - Re-initializes charts, toasts and progress bars in content swapped by htmx (htmx:load).
 *   (The flow editor of FlowHelper is a separate bundle: webroot/js/basecoat-flow.js.)
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

  const boot = () => { initCharts(); initToasts(); initProgress(); };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
  window.addEventListener('load', boot);
  // htmx: content swapped in
  document.addEventListener('htmx:load', (event) => {
    initCharts(event.target);
    initToasts(event.target);
    initProgress(event.target);
  });

  Object.assign(window.BasecoatTheme, { initCharts, initToasts, initProgress, toggleTheme });
})();
