/*
 * BasecoatTheme kanban list sync (webroot/js/basecoat-kanban.js).
 * Load after basecoat-dnd.js: $this->Html->script('BasecoatTheme.basecoat-kanban', ['block' => 'script', 'defer' => true]);
 * When a card is moved (basecoat:move of basecoat-dnd.js) the text of [data-kanban-row="<card id>"] (the Column cell
 * of the List view) becomes the label of the target list (data-dnd-label). Vanilla, no innerHTML.
 * (A generic hook for this belongs in basecoat-screens.js; this file can go away then.)
 */
(() => {
  if (typeof document === 'undefined' || window.BasecoatKanban) return;
  window.BasecoatKanban = true;
  const sync = (id, label) => {
    document.querySelectorAll('[data-kanban-row]').forEach((el) => {
      if (el.getAttribute('data-kanban-row') === String(id)) el.textContent = label;
    });
  };
  document.addEventListener('basecoat:move', (event) => {
    const d = event.detail || {};
    if (d.mode && d.mode !== 'list') return;
    // Cancelled by another listener: the card goes back, nothing to sync
    if (event.defaultPrevented) return;
    const to = d.to;
    if (!to || !to.hasAttribute('data-kanban-list')) return;
    sync(d.id, to.getAttribute('data-dnd-label') || '');
  });
  // A failed save reverts the card: put the previous column back
  document.addEventListener('basecoat:move-error', (event) => {
    const d = event.detail || {};
    if (d.from && d.from.hasAttribute && d.from.hasAttribute('data-kanban-list')) sync(d.id, d.from.getAttribute('data-dnd-label') || '');
  });
})();
