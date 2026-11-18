(() => {
  const t = window.SampleUI.t;
  const search = document.querySelector('#search');
  let filter = 'all';
  function update() {
    let visible = 0;
    document.querySelectorAll('.task').forEach(task => {
      const matches = filter === 'all' || (filter === 'done') === (task.dataset.done === '1');
      task.hidden = !matches || !task.querySelector('.task-title').textContent.toLocaleLowerCase().includes(search.value.toLocaleLowerCase());
      if (!task.hidden) visible++;
    });
    document.querySelector('#empty').hidden = visible > 0;
    document.querySelector('#filter-status').textContent = t('task_count', { count: visible });
  }
  document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
    filter = button.dataset.filter;
    document.querySelectorAll('[data-filter]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    update();
  }));
  search?.addEventListener('input', update);
  document.querySelectorAll('.delete').forEach(button => button.addEventListener('click', event => {
    if (!confirm(t('confirm_delete'))) event.preventDefault();
  }));
  document.querySelector('#route-form')?.addEventListener('submit', event => {
    event.preventDefault();
    location.href = event.currentTarget.dataset.base + encodeURIComponent(document.querySelector('#segment').value);
  });
  document.querySelectorAll('[data-csrf-demo]').forEach(csrfDemo => {
    const buttons = [...csrfDemo.querySelectorAll('[data-csrf-case]')];
    const output = csrfDemo.querySelector('[data-csrf-result]');
    buttons.forEach(button => {
      button.disabled = false;
      button.addEventListener('click', async () => {
        buttons.forEach(item => { item.disabled = true; });
        output.setAttribute('aria-busy', 'true');
        const headers = { 'Content-Type': 'application/json' };
        if (button.dataset.csrfCase === 'valid') headers[csrfDemo.dataset.csrfHeader] = document.head.querySelector(`meta[name="${csrfDemo.dataset.csrfMeta}"]`).content;
        if (button.dataset.csrfCase === 'invalid') headers[csrfDemo.dataset.csrfHeader] = 'invalid';
        try {
          const response = await fetch(csrfDemo.dataset.csrfPath, {
            method: 'POST', credentials: 'same-origin', headers,
            body: JSON.stringify({ example: 'csrf' })
          });
          if (response.ok) {
            const result = await response.json();
            document.querySelector('[data-csrf-count]').textContent = result.accepted;
            output.textContent = `HTTP ${response.status}\n${t('csrf_demo_accepted')}`;
          } else {
            output.textContent = `HTTP ${response.status}\n${t(response.status === 403 ? 'csrf_demo_rejected' : 'sample_request_failed')}`;
          }
        } catch {
          output.textContent = t('sample_request_failed');
        } finally {
          output.removeAttribute('aria-busy');
          buttons.forEach(item => { item.disabled = false; });
        }
      });
    });
  });
})();
// Plugin experiments render response text with textContent, including HTML logs.
document.querySelectorAll('[data-lab-json]').forEach(panel => {
  panel.querySelectorAll('[data-json-case]').forEach(button => {
    button.disabled = false;
    button.addEventListener('click', async () => {
      button.disabled = true;
      const output = panel.querySelector('[data-lab-output]');
      try {
        const response = await fetch('/lab/plugin/decode', {
          method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': panel.dataset.token, 'X-Sample': 'SlightPHP'},
          body: button.dataset.jsonCase === 'valid' ? JSON.stringify({message: 'Hello SlightPHP'}) : '{invalid'
        });
        output.textContent = `HTTP ${response.status}\n${await response.text()}`;
      } catch (error) {
        output.textContent = window.SampleUI.t('sample_request_failed');
      } finally { button.disabled = false; }
    });
  });
});
document.querySelectorAll('[data-lab-response]').forEach(panel => {
  panel.querySelectorAll('[data-preview]').forEach(link => {
    link.addEventListener('click', async event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      const output = panel.querySelector('[data-lab-output]');
      try {
        const response = await fetch(link.href, {credentials: 'same-origin'});
        output.textContent = `HTTP ${response.status}\nContent-Type: ${response.headers.get('content-type')}\nX-Sample: ${response.headers.get('x-sample')}\n\n${await response.text()}`;
      } catch (error) { output.textContent = window.SampleUI.t('sample_request_failed'); }
    });
  });
});
