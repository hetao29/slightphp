(() => {
  'use strict';
  document.documentElement.classList.add('js');
  const themeButtons = [...document.querySelectorAll('.theme-toggle')];
  function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = theme === 'light' ? '#ffffff' : '#101114';
    themeButtons.forEach(button => {
      button.setAttribute('aria-pressed', String(theme === 'light'));
      button.setAttribute('aria-label', theme === 'light' ? '切换深色主题' : '切换浅色主题');
      const icon = button.querySelector('[data-theme-icon]');
      const label = button.querySelector('[data-theme-label]');
      if (icon) icon.textContent = theme === 'light' ? '☾' : '☀';
      if (label) label.textContent = theme === 'light' ? '深色' : '浅色';
    });
  }
  let savedTheme;
  try { savedTheme = localStorage.getItem('slightphp-theme'); } catch {}
  applyTheme(savedTheme === 'light' ? 'light' : 'dark');
  themeButtons.forEach(button => button.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
    applyTheme(theme);
    try { localStorage.setItem('slightphp-theme', theme); } catch {}
  }));
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#site-nav');
  function closeMenu() {
    toggle?.setAttribute('aria-expanded', 'false');
    nav?.classList.remove('is-open');
  }
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
      closeMenu();
      toggle.focus();
    }
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.site-header')) closeMenu();
  });
  nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  const filters = [...document.querySelectorAll('[data-plugin-filter]')];
  const search = document.querySelector('#plugin-search');
  let category = 'all';
  function filterPlugins() {
    const query = (search?.value || '').trim().toLocaleLowerCase();
    let visible = 0;
    document.querySelectorAll('[data-plugin-type]').forEach(card => {
      card.hidden = !(category === 'all' || category === card.dataset.pluginType) || !card.textContent.toLocaleLowerCase().includes(query);
      if (!card.hidden) visible++;
    });
    const status = document.querySelector('#plugin-status');
    if (status) status.textContent = `显示 ${visible} 组插件`;
    const empty = document.querySelector('#plugin-empty');
    if (empty) empty.hidden = visible > 0;
    filters.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pluginFilter === category)));
  }
  filters.forEach(button => button.addEventListener('click', () => {
    category = button.dataset.pluginFilter;
    filterPlugins();
  }));
  search?.addEventListener('input', filterPlugins);
  if (filters.length) filterPlugins();
  const modes = [...document.querySelectorAll('[data-mode]')];
  function selectMode(mode) {
    modes.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.mode === mode)));
    document.querySelectorAll('[data-mode-panel]').forEach(panel => { panel.hidden = panel.dataset.modePanel !== mode; });
  }
  modes.forEach(button => button.addEventListener('click', () => selectMode(button.dataset.mode)));
  if (modes.length) selectMode('namespace');
  document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
    const code = document.getElementById(button.dataset.copy);
    const original = button.textContent;
    try {
      await navigator.clipboard.writeText(code.textContent);
      button.textContent = '已复制';
    } catch {
      const selection = window.getSelection();
      const range = document.createRange();
      range.selectNodeContents(code);
      selection.removeAllRanges();
      selection.addRange(range);
      button.textContent = '已选中，请手动复制';
    }
    let status = document.querySelector('#copy-status');
    if (!status) {
      status = document.createElement('p');
      status.id = 'copy-status'; status.className = 'sr-only'; status.setAttribute('role', 'status');
      document.body.appendChild(status);
    }
    status.textContent = button.textContent;
    setTimeout(() => { button.textContent = original; }, 2400);
  }));
})();
