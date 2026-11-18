/*
 * BasecoatTheme (CakePHP plugin) runtime glue.
 * - Initializes charts rendered by ChartHelper (canvas[data-basecoat-chart]).
 * - Wires theme toggles rendered by ThemeHelper::toggle() ([data-theme-toggle]).
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

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');
    if (toggle) {
      event.preventDefault();
      toggleTheme();
    }
  });

  const boot = () => initCharts();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
  window.addEventListener('load', boot);

  window.BasecoatTheme = { initCharts, toggleTheme };
})();
