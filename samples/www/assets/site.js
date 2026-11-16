(() => {
  'use strict';
  document.documentElement.classList.add('js');
  const t = window.SampleUI.t;
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
    if (status) status.textContent = t('plugin_count', { count: visible });
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
  document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
    const code = document.getElementById(button.dataset.copy);
    const original = button.textContent;
    try {
      await navigator.clipboard.writeText(code.textContent);
      button.textContent = t('copied');
    } catch {
      const selection = window.getSelection();
      const range = document.createRange();
      range.selectNodeContents(code);
      selection.removeAllRanges();
      selection.addRange(range);
      button.textContent = t('copy_manually');
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
