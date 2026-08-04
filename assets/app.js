const sidebar = document.querySelector('[data-sidebar]');
document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => document.body.classList.add('menu-open'));
document.querySelector('[data-menu-close]')?.addEventListener('click', () => document.body.classList.remove('menu-open'));

document.querySelectorAll('[data-modal-open]').forEach((button) => {
  button.addEventListener('click', () => document.getElementById(button.dataset.modalOpen)?.showModal());
});
document.querySelectorAll('[data-modal-close]').forEach((button) => {
  button.addEventListener('click', () => button.closest('dialog')?.close());
});
document.querySelectorAll('dialog').forEach((dialog) => {
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
});
document.querySelectorAll('[data-complete]').forEach((button) => {
  button.addEventListener('click', () => {
    const data = JSON.parse(button.dataset.complete);
    document.getElementById('complete-id').value = data.id;
    const cost = document.getElementById('cost-field');
    if (cost) cost.hidden = data.type !== 'ticket';
    document.getElementById('complete-modal')?.showModal();
  });
});
