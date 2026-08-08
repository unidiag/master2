let pageLoading = false;
let previousAddressValue = '';

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

  const subscriberInfo = ticketCreateForm.querySelector(
    '[data-subscriber-info]'
  );

  const tariffElement = ticketCreateForm.querySelector(
    '[data-subscriber-tariff]'
  );

  const debtElement = ticketCreateForm.querySelector(
    '[data-subscriber-debt]'
  );

  const noteInput = ticketCreateForm.querySelector(
    '[name="other"]'
  );

  const streets = [
    'Заводская-',
    'Молодёжный-',
    'Набережная-',
    'Озмителя-',
    'Панчука-',
    'Энергетиков-',
  ];

  let lookupTimer = 0;
  let requestController = null;
  let lastLookupAddress = '';


  const ticketCreateDialog = ticketCreateForm.closest('dialog');

  if (ticketCreateDialog instanceof HTMLDialogElement) {
    ticketCreateDialog.addEventListener('toggle', () => {
      if (!ticketCreateDialog.open) {
        return;
      }

      requestAnimationFrame(() => {
        if (addressInput instanceof HTMLInputElement) {
          addressInput.focus();
        }
      });
    });
  }



  const clearSubscriber = () => {
    if (nameInput instanceof HTMLInputElement) {
      nameInput.value = '';
    }

    if (tariffElement instanceof HTMLElement) {
      tariffElement.textContent = '—';
    }

    if (debtElement instanceof HTMLElement) {
      debtElement.textContent = '—';
    }

    if (subscriberInfo instanceof HTMLElement) {
      subscriberInfo.hidden = true;
    }

    lastLookupAddress = '';
  };

  const normalizeStreet = () => {
    if (!(addressInput instanceof HTMLInputElement)) {
      return;
    }

    const value = addressInput.value.trim();

    if (value.length !== 1) {
      return;
    }

    const firstLetter = value.toLocaleLowerCase('ru-RU');

    const street = streets.find((item) =>
      item
        .charAt(0)
        .toLocaleLowerCase('ru-RU') === firstLetter
    );

    if (!street) {
      return;
    }

    addressInput.value = street;

    addressInput.setSelectionRange(
      street.length,
      street.length
    );
  };

  const isCompleteAddress = (address) => {
    return /^(Заводская|Молодёжный|Набережная|Озмителя|Панчука|Энергетиков)-[^-]+-[^-]+$/u
      .test(address);
  };

  const renderSubscriber = (subscriber) => {
    if (
      !(nameInput instanceof HTMLInputElement)
      || !subscriber
    ) {
      return;
    }

    nameInput.value = subscriber.name || '';

    if (tariffElement instanceof HTMLElement) {
      tariffElement.textContent =
        subscriber.tariff || '—';
    }

    if (debtElement instanceof HTMLElement) {
      const debt = Number(subscriber.debt || 0);

      debtElement.textContent =
        debt.toLocaleString(
          'ru-RU',
          {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }
        ) + ' руб.';
    }

    if (subscriberInfo instanceof HTMLElement) {
      subscriberInfo.hidden = false;
    }
  };


  tariffElement?.addEventListener('click', () => {
    if (
      !(tariffElement instanceof HTMLElement)
      || !(noteInput instanceof HTMLInputElement)
    ) {
      return;
    }

    const tariff = tariffElement.textContent?.trim();

    if (!tariff || tariff === '—') {
      return;
    }

    const current = noteInput.value.trim();

    noteInput.value = current
      ? `${current}; ${tariff}`
      : tariff;

    noteInput.focus();

    noteInput.setSelectionRange(
      noteInput.value.length,
      noteInput.value.length
    );
  });


  const lookupSubscriber = async (address) => {
    if (address === lastLookupAddress) {
      return;
    }

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
      'subscriber_lookup'
    );

    url.searchParams.set(
      'address',
      address
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

      lastLookupAddress = address;

      if (
        data.found
        && data.subscriber
      ) {
        renderSubscriber(
          data.subscriber
        );

        return;
      }

      clearSubscriber();
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      console.error(
        'Не удалось получить данные абонента',
        error
      );

      clearSubscriber();
    }
  };

  addressInput?.addEventListener('input', () => {
    window.clearTimeout(lookupTimer);

    requestController?.abort();

    const currentValue = addressInput.value;
    const isTypingFirstCharacter =
      previousAddressValue.length === 0 &&
      currentValue.length === 1;

    if (isTypingFirstCharacter) {
      normalizeStreet();
    }

    const address = addressInput.value.trim();

    previousAddressValue = addressInput.value;

    clearSubscriber();

    if (!isCompleteAddress(address)) {
      return;
    }

    lookupTimer = window.setTimeout(() => {
      lookupSubscriber(address);
    }, 150);
  });

  ticketCreateForm
    .closest('dialog')
    ?.addEventListener('close', () => {
      window.clearTimeout(lookupTimer);
      requestController?.abort();

      ticketCreateForm.reset();

      previousAddressValue = '';

      clearSubscriber();
    });
}






















document.addEventListener('DOMContentLoaded', function () {
    const statusFilter = document.getElementById(
        'status-filter'
    );

    if (!statusFilter) {
        return;
    }

    const cards = Array.from(
        document.querySelectorAll(
            '.cards > .card[data-status]'
        )
    );

    function applyStatusFilter() {
        const status = statusFilter.value;

        cards.forEach(function (card) {
            const cardStatus =
                card.dataset.status || '';

            card.hidden =
                status !== 'all'
                && cardStatus !== status;
        });
    }

    statusFilter.addEventListener(
        'change',
        applyStatusFilter
    );

    /*
     * По умолчанию показываем
     * только открытые записи.
     */
    statusFilter.value = 'open';

    applyStatusFilter();
});











document.addEventListener(
    'DOMContentLoaded',
    function () {
        const form = document.getElementById(
            'database-import-form'
        );

        const fileInput =
            document.getElementById(
                'database-import-file'
            );

        if (!form || !fileInput) {
            return;
        }

        const progress =
            document.getElementById(
                'database-import-progress'
            );

        const progressBar =
            document.getElementById(
                'database-import-progress-bar'
            );

        const progressText =
            document.getElementById(
                'database-import-progress-text'
            );

        const progressPercent =
            document.getElementById(
                'database-import-progress-percent'
            );

        const result =
            document.getElementById(
                'database-import-result'
            );

        fileInput.addEventListener(
            'change',
            function () {
                if (
                    !fileInput.files
                    || fileInput.files.length === 0
                ) {
                    return;
                }

                const body =
                    new FormData(form);

                const xhr =
                    new XMLHttpRequest();

                xhr.open(
                    'POST',
                    'index.php',
                    true
                );

                /*
                 * Во время загрузки запрещаем
                 * повторный выбор файла.
                 */
                fileInput.disabled = true;

                progress.hidden = false;
                result.hidden = true;

                progressBar.style.width = '0%';

                progressPercent.textContent = '0%';

                progressText.textContent =
                    'Загрузка файла…';

                xhr.upload.addEventListener(
                    'progress',
                    function (event) {
                        if (!event.lengthComputable) {
                            return;
                        }

                        const percent = Math.round(
                            (
                                event.loaded
                                / event.total
                            )
                            * 100
                        );

                        progressBar.style.width =
                            percent + '%';

                        progressPercent.textContent =
                            percent + '%';
                    }
                );

                xhr.upload.addEventListener(
                    'load',
                    function () {
                        progressBar.style.width =
                            '100%';

                        progressPercent.textContent =
                            '100%';

                        progressText.textContent =
                            'Обработка файла…';
                    }
                );

                xhr.addEventListener(
                    'load',
                    function () {
                        let response;

                        try {
                            response =
                                JSON.parse(
                                    xhr.responseText
                                );
                        } catch (error) {
                            showError(
                                'Сервер вернул некорректный ответ.'
                            );

                            return;
                        }

                        if (
                            xhr.status < 200
                            || xhr.status >= 300
                            || !response.success
                        ) {
                            showError(
                                response.error
                                || 'Ошибка импорта.'
                            );

                            return;
                        }

                        progressText.textContent =
                            'Импорт завершён';

                        result.hidden = false;

                        result.classList.remove(
                            'database-import-result--error'
                        );

                        result.classList.add(
                            'database-import-result--success'
                        );

                        result.textContent =
                            'Добавлено: '
                            + response.inserted
                            + ', пропущено: '
                            + response.skipped;

                        window.setTimeout(
                            function () {
                                window.location.href =
                                    'index.php?module=database';
                            },
                            1000
                        );
                    }
                );

                xhr.addEventListener(
                    'error',
                    function () {
                        showError(
                            'Ошибка соединения при загрузке файла.'
                        );
                    }
                );

                xhr.send(body);

                function showError(message) {
                    fileInput.disabled = false;

                    /*
                     * Разрешаем выбрать тот же
                     * самый файл ещё раз.
                     */
                    fileInput.value = '';

                    progressText.textContent =
                        'Ошибка';

                    result.hidden = false;

                    result.classList.remove(
                        'database-import-result--success'
                    );

                    result.classList.add(
                        'database-import-result--error'
                    );

                    result.textContent = message;
                }
            }
        );
    }
);