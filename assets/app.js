const sidebar = document.querySelector('[data-sidebar]');

document
  .querySelector('[data-menu-toggle]')
  ?.addEventListener('click', () => {
    document.body.classList.add('menu-open');
  });

document
  .querySelector('[data-menu-close]')
  ?.addEventListener('click', () => {
    document.body.classList.remove('menu-open');
  });

document.querySelectorAll('[data-modal-open]').forEach((button) => {
  button.addEventListener('click', () => {
    const modalId = button.dataset.modalOpen;
    const dialog = document.getElementById(modalId);

    if (!(dialog instanceof HTMLDialogElement)) {
      console.error(`Диалог #${modalId} не найден`);
      return;
    }

    if (typeof dialog.showModal !== 'function') {
      console.error('Браузер не поддерживает dialog.showModal()');
      return;
    }

    dialog.showModal();
  });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
  button.addEventListener('click', () => {
    button.closest('dialog')?.close();
  });
});

document.querySelectorAll('dialog').forEach((dialog) => {
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });
});

document.querySelectorAll('[data-complete]').forEach((button) => {
  button.addEventListener('click', () => {
    const data = JSON.parse(button.dataset.complete);

    const completeId = document.getElementById('complete-id');

    if (completeId) {
      completeId.value = data.id;
    }

    const cost = document.getElementById('cost-field');

    if (cost) {
      cost.hidden = data.type !== 'ticket';
    }

    document.getElementById('complete-modal')?.showModal();
  });
});




function openKarandashEditModal(card) {
  const rawData = card.dataset.karandashEdit;

  if (!rawData) {
    return;
  }

  let data;

  try {
    data = JSON.parse(rawData);
  } catch (error) {
    console.error('Некорректные данные карточки карандаша', error);
    return;
  }

  const dialog = document.getElementById(
    'karandash-edit-modal'
  );

  const addressInput = document.getElementById(
    'karandash-edit-address'
  );

  const addressLabel = document.getElementById(
    'karandash-edit-address-label'
  );

  const descrInput = document.getElementById(
    'karandash-edit-descr'
  );

  if (!(dialog instanceof HTMLDialogElement)) {
    console.error('Диалог #karandash-edit-modal не найден');
    return;
  }

  if (!addressInput || !addressLabel || !descrInput) {
    console.error('Поля редактирования карандаша не найдены');
    return;
  }

  addressInput.value = data.address || '';
  addressLabel.textContent = data.address || 'Адрес не указан';
  descrInput.value = data.descr || '';

  dialog.showModal();

  requestAnimationFrame(() => {
    descrInput.focus();
    descrInput.setSelectionRange(
      descrInput.value.length,
      descrInput.value.length
    );
  });
}

document
  .querySelectorAll('[data-karandash-edit]')
  .forEach((card) => {
    card.addEventListener('click', () => {
      openKarandashEditModal(card);
    });

    card.addEventListener('keydown', (event) => {
      if (
        event.key !== 'Enter'
        && event.key !== ' '
      ) {
        return;
      }

      event.preventDefault();
      openKarandashEditModal(card);
    });
  });