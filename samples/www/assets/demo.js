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
})();
