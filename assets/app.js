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








  const ticketCreateForm = document.querySelector(
  '[data-ticket-create-form]'
);

if (ticketCreateForm instanceof HTMLFormElement) {
  const addressInput = ticketCreateForm.querySelector(
    '[data-subscriber-address]'
  );

  const nameInput = ticketCreateForm.querySelector(
    '[data-subscriber-name]'
  );

  const suggestions = ticketCreateForm.querySelector(
    '[data-subscriber-suggestions]'
  );

  let searchTimer = 0;
  let requestController = null;
  let selectedIndex = -1;
  let currentItems = [];

  const closeSuggestions = () => {
    if (!(suggestions instanceof HTMLElement)) {
      return;
    }

    suggestions.hidden = true;
    suggestions.replaceChildren();
    currentItems = [];
    selectedIndex = -1;

    addressInput?.setAttribute(
      'aria-expanded',
      'false'
    );
  };

  const selectSubscriber = (item) => {
    if (
      !(addressInput instanceof HTMLInputElement)
      || !(nameInput instanceof HTMLInputElement)
    ) {
      return;
    }

    addressInput.value = item.address || '';
    nameInput.value = item.name || '';

    closeSuggestions();

    if (nameInput.value === '') {
      nameInput.focus();
    }
  };

  const setActiveSuggestion = (index) => {
    if (!(suggestions instanceof HTMLElement)) {
      return;
    }

    const buttons = suggestions.querySelectorAll(
      '[data-subscriber-suggestion]'
    );

    if (buttons.length === 0) {
      selectedIndex = -1;
      return;
    }

    selectedIndex = Math.max(
      0,
      Math.min(index, buttons.length - 1)
    );

    buttons.forEach((button, buttonIndex) => {
      const active = buttonIndex === selectedIndex;

      button.classList.toggle(
        'is-active',
        active
      );

      button.setAttribute(
        'aria-selected',
        active ? 'true' : 'false'
      );
    });

    buttons[selectedIndex]?.scrollIntoView({
      block: 'nearest',
    });
  };

  const renderSuggestions = (items) => {
    if (!(suggestions instanceof HTMLElement)) {
      return;
    }

    suggestions.replaceChildren();
    currentItems = items;
    selectedIndex = -1;

    if (items.length === 0) {
      closeSuggestions();
      return;
    }

    items.forEach((item, index) => {
      const button = document.createElement('button');

      button.type = 'button';
      button.className = 'subscriber-suggestion';
      button.dataset.subscriberSuggestion = String(index);
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', 'false');

      const address = document.createElement('strong');
      address.textContent = item.address || '';

      const details = document.createElement('span');

      const detailParts = [];

      if (item.name) {
        detailParts.push(item.name);
      }

      if (item.personal) {
        detailParts.push(`л/с ${item.personal}`);
      }

      details.textContent = detailParts.join(' · ');

      button.append(address);

      if (details.textContent !== '') {
        button.append(details);
      }

      button.addEventListener('mousedown', (event) => {
        /*
         * Не даём input потерять focus раньше выбора.
         */
        event.preventDefault();
      });

      button.addEventListener('click', () => {
        selectSubscriber(item);
      });

      suggestions.append(button);
    });

    suggestions.hidden = false;

    addressInput?.setAttribute(
      'aria-expanded',
      'true'
    );
  };

  const loadSuggestions = async (query) => {
    requestController?.abort();
    requestController = new AbortController();

    const url = new URL(
      window.location.href
    );

    url.search = '';
    url.hash = '';
    url.searchParams.set(
      'module',
      'zayavki'
    );
    url.searchParams.set(
      'ajax',
      'subscriber_search'
    );
    url.searchParams.set(
      'query',
      query
    );

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
        },
        signal: requestController.signal,
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(
          `Ошибка AJAX: ${response.status}`
        );
      }

      const data = await response.json();

      renderSuggestions(
        Array.isArray(data.items)
          ? data.items.slice(0, 10)
          : []
      );
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      console.error(
        'Не удалось загрузить адреса',
        error
      );

      closeSuggestions();
    }
  };

  addressInput?.addEventListener('input', () => {
    const query = addressInput.value.trim();

    window.clearTimeout(searchTimer);

    if (query.length < 2) {
      requestController?.abort();
      closeSuggestions();
      return;
    }

    searchTimer = window.setTimeout(() => {
      loadSuggestions(query);
    }, 250);
  });

  addressInput?.addEventListener('keydown', (event) => {
    if (
      !(suggestions instanceof HTMLElement)
      || suggestions.hidden
    ) {
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setActiveSuggestion(selectedIndex + 1);
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();

      setActiveSuggestion(
        selectedIndex <= 0
          ? currentItems.length - 1
          : selectedIndex - 1
      );

      return;
    }

    if (
      event.key === 'Enter'
      && selectedIndex >= 0
      && currentItems[selectedIndex]
    ) {
      event.preventDefault();

      selectSubscriber(
        currentItems[selectedIndex]
      );

      return;
    }

    if (event.key === 'Escape') {
      closeSuggestions();
    }
  });

  document.addEventListener('click', (event) => {
    if (!ticketCreateForm.contains(event.target)) {
      closeSuggestions();
    }
  });

  ticketCreateForm
    .closest('dialog')
    ?.addEventListener('close', () => {
      requestController?.abort();
      closeSuggestions();
      ticketCreateForm.reset();
    });
}