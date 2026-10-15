'use strict';
(() => {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const toggle = document.getElementById('menu-toggle');
  const close = () => { sidebar?.classList.remove('open'); backdrop?.classList.remove('open'); };
  toggle?.addEventListener('click', () => { sidebar?.classList.toggle('open'); backdrop?.classList.toggle('open'); });
  backdrop?.addEventListener('click', close);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  document.querySelectorAll('[data-filter-input]').forEach(input => {
    const target = document.querySelector('[data-filter-container="' + input.dataset.filterInput + '"]');
    if (!target) return;
    input.addEventListener('input', () => {
      const q = input.value.trim().toLocaleLowerCase('fr');
      target.querySelectorAll('[data-filter-item]').forEach(el => {
        el.hidden = !el.dataset.filterItem.includes(q);
      });
    });
  });
})();
