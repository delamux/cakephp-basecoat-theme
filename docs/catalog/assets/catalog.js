/*
 * Component catalog runtime:
 * - syntax highlighting (highlight.js) of code blocks;
 * - highlight.js light/dark stylesheet following the theme (`.dark` on <html>);
 * - copy buttons ([data-copy="#code-id"]); the result is also announced to screen readers
 *   through a polite live region (the visual "Copied" hint is CSS generated content).
 */
(() => {
  const syncCodeTheme = () => {
    const dark = document.documentElement.classList.contains('dark');
    document.querySelectorAll('link[data-hljs-theme]').forEach((link) => {
      link.disabled = (link.dataset.hljsTheme === 'dark') !== dark;
    });
  };

  const highlight = () => {
    if (!window.hljs) return;
    document.querySelectorAll('pre code[class*="language-"]').forEach((code) => window.hljs.highlightElement(code));
  };

  // The live region exists before the first message, so screen readers pick it up.
  let live;
  const createLiveRegion = () => {
    live = document.createElement('div');
    live.className = 'sr-only';
    live.setAttribute('role', 'status');
    document.body.appendChild(live);
  };
  const announce = (message) => {
    if (!live) return;
    live.textContent = message;
    setTimeout(() => { live.textContent = ''; }, 1500);
  };
  const init = () => {
    createLiveRegion();
    highlight();
  };

  const copy = async (button) => {
    const target = document.querySelector(button.dataset.copy);
    if (!target || !navigator.clipboard) return;
    try {
      await navigator.clipboard.writeText(target.textContent);
      button.dataset.copied = 'true';
      announce('Copied');
      setTimeout(() => { delete button.dataset.copied; }, 1500);
    } catch (e) {
      console.error('[catalog] Copy failed', e);
    }
  };

  syncCodeTheme();
  new MutationObserver(syncCodeTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');
    if (button) copy(button);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
