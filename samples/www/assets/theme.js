(() => {
  'use strict';
  const root = document.documentElement;
  const messages = JSON.parse(document.getElementById('sample-messages')?.textContent || '{}');
  const t = (key, values = {}) => (messages[key] || key).replace(/\{(\w+)\}/g, (match, name) => values[name] ?? match);
  window.SampleUI = { t };
  const systemTheme = window.matchMedia('(prefers-color-scheme: light)');
  const modes = ['auto', 'dark', 'light'];
  let mode = 'auto';
  try {
    const saved = localStorage.getItem('slightphp-theme');
    if (modes.includes(saved)) mode = saved;
  } catch {}
  function applyTheme() {
    const theme = mode === 'auto' ? (systemTheme.matches ? 'light' : 'dark') : mode;
    root.dataset.theme = theme;
    root.dataset.themeMode = mode;
    document.querySelectorAll('[data-favicon-theme]').forEach(icon => {
      icon.media = icon.dataset.faviconTheme === theme ? 'all' : 'not all';
    });
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = theme === 'light' ? '#ffffff' : '#101114';
    document.querySelectorAll('.theme-toggle').forEach(button => {
      const next = modes[(modes.indexOf(mode) + 1) % modes.length];
      button.dataset.themeMode = mode;
      button.setAttribute('aria-label', t('theme_cycle', { theme: t(mode), next: t(next) }));
      const icon = button.querySelector('[data-theme-icon]');
      const label = button.querySelector('[data-theme-label]');
      if (icon) icon.textContent = { auto: '◐', dark: '☾', light: '☀' }[mode];
      if (label) label.textContent = t(mode);
    });
  }
  applyTheme();
  systemTheme.addEventListener('change', () => { if (mode === 'auto') applyTheme(); });
  document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    document.querySelectorAll('.theme-toggle').forEach(button => button.addEventListener('click', () => {
      mode = modes[(modes.indexOf(mode) + 1) % modes.length];
      try { localStorage.setItem('slightphp-theme', mode); } catch {}
      applyTheme();
    }));
    document.querySelectorAll('[data-language-link]').forEach(link => {
      link.hash = location.hash;
      link.addEventListener('click', () => { link.hash = location.hash; });
    });
  });
})();
