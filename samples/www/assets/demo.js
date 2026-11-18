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
        if (button.dataset.csrfCase === 'valid') headers[csrfDemo.dataset.csrfHeader] = csrfDemo.dataset.csrfToken;
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
