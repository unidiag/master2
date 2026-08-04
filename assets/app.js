let pageLoading = false;

function showPageLoader() {
  if (pageLoading) {
    return;
  }

  pageLoading = true;
  document.body.classList.add('is-page-loading');
}

function hidePageLoader() {
  pageLoading = false;
  document.body.classList.remove('is-page-loading');
}

function isInternalNavigationLink(link) {
  if (!(link instanceof HTMLAnchorElement)) {
    return false;
  }

  const href = link.getAttribute('href');

  if (
    !href
    || href.startsWith('#')
    || href.startsWith('javascript:')
    || href.startsWith('mailto:')
    || href.startsWith('tel:')
  ) {
    return false;
  }

  if (
    link.target === '_blank'
    || link.hasAttribute('download')
  ) {
    return false;
  }

  let url;

  try {
    url = new URL(link.href, window.location.href);
  } catch {
    return false;
  }

  return url.origin === window.location.origin;
}

document.addEventListener('click', (event) => {
  if (
    event.defaultPrevented
    || event.button !== 0
    || event.ctrlKey
    || event.metaKey
    || event.shiftKey
    || event.altKey
  ) {
    return;
  }

  const link = event.target.closest('a');

  if (!isInternalNavigationLink(link)) {
    return;
  }

  const currentUrl = new URL(window.location.href);
  const targetUrl = new URL(link.href, window.location.href);

  if (
    currentUrl.pathname === targetUrl.pathname
    && currentUrl.search === targetUrl.search
    && targetUrl.hash !== ''
  ) {
    return;
  }

  showPageLoader();
});

document.addEventListener('submit', (event) => {
  if (event.defaultPrevented) {
    return;
  }

  const form = event.target;

  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  showPageLoader();
});

window.addEventListener('pageshow', () => {
  hidePageLoader();
});

window.addEventListener('pagehide', () => {
  showPageLoader();
});








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