/*
 * BasecoatTheme drop zone (webroot/js/basecoat-dropzone.js), used by the `file-manager` screen.
 *
 * Load it with the CSP nonce, e.g.:  $this->Html->script('BasecoatTheme.basecoat-dropzone', ['block' => 'script', 'defer' => true]);
 * Vanilla, no dependencies, no inline styles, no innerHTML (rows are cloned from a <template>).
 * Progressive enhancement: without JS the zone is a plain <input type="file" multiple>.
 *
 * Markup (hooks):
 *   [data-dropzone]                       the drop target; gets data-over while files are dragged over it
 *     input[type=file][data-dropzone-input]   the native picker (also used by keyboard users)
 *     [data-dropzone-list]                container (hidden until there are files)
 *       <template data-dropzone-item>       one row: [data-dropzone-name], [data-dropzone-size] and
 *                                           [data-dropzone-remove] (data-dropzone-remove-label="Remove {name}")
 *     [data-dropzone-actions]             hidden until there are files; holds [data-dropzone-upload] / [data-dropzone-clear]
 *   Messages: data-dropzone-i18n-added="{count} added." / -removed / -cleared / -ready on the zone ({count}, {name}).
 *
 * The files are only listed in the page. "Upload" fires the cancelable event `basecoat:upload`
 * on the zone with {files: File[]}; wire your own request there (FormData + CSRF token) or
 * set data-endpoint and handle it. Nothing is sent by this script.
 *
 * API: window.BasecoatDropzone.init(root), .formatSize(bytes). Auto init: DOMContentLoaded, htmx:load, htmx:afterSwap.
 */
(() => {
  const formatSize = (bytes) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = Math.max(0, Number(bytes) || 0);
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit += 1; }
    return `${unit === 0 || value >= 10 ? Math.round(value) : Math.round(value * 10) / 10} ${units[unit]}`;
  };

  if (typeof module !== 'undefined' && module.exports) module.exports = { formatSize };
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  if (window.BasecoatDropzone) return;

  const message = (zone, key, vars) => {
    const template = zone.getAttribute(`data-dropzone-i18n-${key}`) || '';
    return template.replace(/\{(\w+)\}/g, (m, name) => (vars && vars[name] !== undefined ? String(vars[name]) : m));
  };
  const announce = (zone, key, vars) => {
    const text = message(zone, key, vars);
    if (text && window.BasecoatScreens && typeof window.BasecoatScreens.announce === 'function') window.BasecoatScreens.announce(text);
  };
  const sameFile = (a, b) => a.name === b.name && a.size === b.size && a.lastModified === b.lastModified;

  const setup = (zone) => {
    if (zone.hasAttribute('data-dropzone-ready')) return;
    const input = zone.querySelector('[data-dropzone-input]');
    const list = zone.querySelector('[data-dropzone-list]');
    const template = zone.querySelector('template[data-dropzone-item]');
    const actions = zone.querySelector('[data-dropzone-actions]');
    if (!input || !list || !template) return;
    zone.setAttribute('data-dropzone-ready', '');
    let files = [];

    const render = () => {
      list.querySelectorAll('[data-dropzone-row]').forEach((row) => row.remove());
      files.forEach((file, index) => {
        const row = template.content.firstElementChild.cloneNode(true);
        row.setAttribute('data-dropzone-row', String(index));
        const name = row.querySelector('[data-dropzone-name]');
        const size = row.querySelector('[data-dropzone-size]');
        const remove = row.querySelector('[data-dropzone-remove]');
        if (name) name.textContent = file.name;
        if (size) size.textContent = formatSize(file.size);
        if (remove) {
          const label = remove.getAttribute('data-dropzone-remove-label');
          if (label) remove.setAttribute('aria-label', label.replace('{name}', file.name));
        }
        list.appendChild(row);
      });
      list.hidden = files.length === 0;
      if (actions) actions.hidden = files.length === 0;
    };

    const add = (incoming) => {
      const added = Array.from(incoming).filter((file) => !files.some((known) => sameFile(known, file)));
      if (added.length === 0) return;
      files = files.concat(added);
      render();
      announce(zone, 'added', { count: added.length, total: files.length });
    };

    input.addEventListener('change', () => {
      add(input.files || []);
      input.value = '';
    });

    let depth = 0;
    const hasFiles = (event) => !!event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files');
    zone.addEventListener('dragenter', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      depth += 1;
      zone.setAttribute('data-over', '');
    });
    zone.addEventListener('dragover', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      event.dataTransfer.dropEffect = 'copy';
    });
    zone.addEventListener('dragleave', () => {
      depth = Math.max(0, depth - 1);
      if (depth === 0) zone.removeAttribute('data-over');
    });
    zone.addEventListener('drop', (event) => {
      if (!hasFiles(event)) return;
      event.preventDefault();
      depth = 0;
      zone.removeAttribute('data-over');
      add(event.dataTransfer.files);
    });

    zone.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) return;
      const remove = target.closest('[data-dropzone-remove]');
      if (remove) {
        const row = remove.closest('[data-dropzone-row]');
        const index = row ? Number(row.getAttribute('data-dropzone-row')) : -1;
        if (index >= 0 && files[index]) {
          const [gone] = files.splice(index, 1);
          render();
          announce(zone, 'removed', { name: gone.name, total: files.length });
          input.focus();
        }
        return;
      }
      if (target.closest('[data-dropzone-clear]')) {
        files = [];
        render();
        announce(zone, 'cleared', {});
        input.focus();
        return;
      }
      if (target.closest('[data-dropzone-upload]') && files.length > 0) {
        const event2 = new CustomEvent('basecoat:upload', { bubbles: true, cancelable: true, detail: { files: files.slice() } });
        zone.dispatchEvent(event2);
      }
    });
  };

  const init = (root = document) => {
    const scope = root && root.querySelectorAll ? root : document;
    if (scope instanceof Element && scope.matches('[data-dropzone]')) setup(scope);
    scope.querySelectorAll('[data-dropzone]').forEach(setup);
  };

  window.BasecoatDropzone = { init, formatSize };
  const start = (event) => init(event && event.target instanceof Element ? event.target : document);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init(document));
  else init(document);
  document.addEventListener('htmx:load', start);
  document.addEventListener('htmx:afterSwap', start);
})();
