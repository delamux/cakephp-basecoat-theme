/*
 * BasecoatTheme (CakePHP plugin) runtime glue.
 * - Initializes charts rendered by ChartHelper (canvas[data-basecoat-chart]).
 * - Wires theme toggles rendered by ThemeHelper::toggle() ([data-theme-toggle]).
 * - Sidebar toggle ([data-sidebar-toggle]), dialogs ([data-dialog-open], [data-dialog-close],
 *   [data-dialog-backdrop-close]), dismissible alerts ([data-alert-dismiss]) and flash toasts
 *   (template[data-basecoat-toast]): event delegation, no inline JS (CSP without 'unsafe-inline').
 * - Progress bars ([data-progress-value]: width via CSSOM) and "Back" links ([data-history-back]).
 * - Re-initializes charts, toasts and progress bars in content swapped by htmx (htmx:load).
 * Requires Basecoat JS (and Chart.js + Basecoat chart.js for charts) to be loaded first.
 */
(() => {
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
    // Backdrop click (press and release on the dialog element itself, not its content)
    if (target instanceof HTMLDialogElement && target.matches('[data-dialog-backdrop-close]') && backdropPress === target) {
      target.close();
    }
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

  window.BasecoatTheme = { initCharts, initToasts, initProgress, toggleTheme };
})();
