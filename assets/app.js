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

  const phoneElement = ticketCreateForm.querySelector(
    '[data-subscriber-phone]'
  );

  const debtElement = ticketCreateForm.querySelector(
    '[data-subscriber-debt]'
  );

  const ticketsElement = ticketCreateForm.querySelector(
    '[data-subscriber-tickets]'
  );

  const ticketsZeroElement = ticketCreateForm.querySelector(
    '[data-subscriber-tickets-zero]'
  );


  const noteInput = ticketCreateForm.querySelector(
    '[name="other"]'
  );

  const streets = [
    'Заводская-6-',
    'Молодёжный-',
    'Набережная-',
    'Озмителя-',
    'Панчука-',
    'Энергетиков-',
    'Садовая-1-'
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

    if (phoneElement instanceof HTMLElement) {
      phoneElement.textContent = '—';
    }

    if (subscriberInfo instanceof HTMLElement) {
      subscriberInfo.hidden = true;
    }

    if (ticketsElement instanceof HTMLAnchorElement) {
      ticketsElement.textContent = '0';
      ticketsElement.hidden = true;
      ticketsElement.removeAttribute('href');
    }

    if (ticketsZeroElement instanceof HTMLElement) {
      ticketsZeroElement.textContent = '0';
      ticketsZeroElement.hidden = false;
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
    return /^(Заводская|Молодёжный|Набережная|Озмителя|Панчука|Энергетиков|Садовая)-[^-]+-[^-]+$/u
      .test(address);
  };




  const renderSubscriber = (
    subscriber,
    ticketsCount,
    address
  ) => {
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

    if (phoneElement instanceof HTMLElement) {
      phoneElement.textContent =
        subscriber.phone || '—';
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

    const count = Number(ticketsCount || 0);

    if (
      ticketsElement instanceof HTMLAnchorElement
      && ticketsZeroElement instanceof HTMLElement
    ) {
      if (count > 0) {
        const ticketsUrl = new URL(
          window.location.href
        );

        ticketsUrl.search = '';
        ticketsUrl.hash = '';

        ticketsUrl.searchParams.set(
          'module',
          'zayavki'
        );

        ticketsUrl.searchParams.set(
          'search',
          address
        );

        ticketsUrl.searchParams.set(
          'status',
          'all'
        );

        ticketsElement.textContent =
          String(count);

        ticketsElement.href =
          ticketsUrl.toString();

        ticketsElement.hidden = false;
        ticketsZeroElement.hidden = true;
      } else {
        ticketsElement.textContent = '0';
        ticketsElement.hidden = true;

        ticketsElement.removeAttribute(
          'href'
        );

        ticketsZeroElement.textContent = '0';
        ticketsZeroElement.hidden = false;
      }
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
          data.subscriber,
          data.tickets_count,
          address
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

    const searchForm = statusFilter.closest(
        '.search-form'
    );

    searchForm?.addEventListener(
        'submit',
        function () {
            const searchInput =
                searchForm.querySelector(
                    'input[name="search"]'
                );

            if (
                searchInput
                && searchInput.value.trim() !== ''
            ) {
                statusFilter.value = 'all';
            }
        }
    );    

    const searchParams = new URLSearchParams(
        window.location.search
    );

    const searchValue = (
        searchParams.get('search') || ''
    ).trim();

    /*
    * При поиске показываем все найденные
    * записи, включая выполненные.
    * Без поиска по умолчанию показываем
    * только открытые.
    */
    statusFilter.value = searchValue !== ''
        ? 'all'
        : 'open';

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











const terminalElement = document.querySelector(
    '[data-terminal]'
);

if (
    terminalElement
    && window.Terminal
) {
    const statusElement = document.querySelector(
        '[data-terminal-status]'
    );

    const terminal = new Terminal({
        cursorBlink: true,
        convertEol: true,
        scrollback: 5000,
        fontSize: 14,
        fontFamily:
            '"JetBrains Mono", "Fira Code", monospace',
    });

    const fitAddon = new FitAddon.FitAddon();

    terminal.loadAddon(fitAddon);

    terminal.open(terminalElement);
    fitAddon.fit();

    const protocol =
        location.protocol === 'https:'
            ? 'wss:'
            : 'ws:';

    const socket = new WebSocket(
        `${protocol}//${location.host}/terminal-ws`
    );

    socket.binaryType = 'arraybuffer';

    socket.addEventListener(
        'open',
        () => {
            if (statusElement) {
                statusElement.textContent =
                    'Подключено';

                statusElement.classList.add(
                    'connected'
                );
            }

            sendResize();
            terminal.focus();
        }
    );

    socket.addEventListener(
        'message',
        (event) => {
            if (event.data instanceof ArrayBuffer) {
                terminal.write(
                    new Uint8Array(event.data)
                );

                return;
            }

            terminal.write(event.data);
        }
    );

    socket.addEventListener(
        'close',
        () => {
            if (statusElement) {
                statusElement.textContent =
                    'Отключено';

                statusElement.classList.remove(
                    'connected'
                );

                statusElement.classList.add(
                    'disconnected'
                );
            }

            terminal.write(
                '\r\n\x1b[31mСоединение закрыто\x1b[0m\r\n'
            );
        }
    );

    terminal.onData((data) => {
        if (
            socket.readyState
            !== WebSocket.OPEN
        ) {
            return;
        }

        socket.send(JSON.stringify({
            type: 'input',
            data,
        }));
    });

    function sendResize() {
        if (
            socket.readyState
            !== WebSocket.OPEN
        ) {
            return;
        }

        socket.send(JSON.stringify({
            type: 'resize',
            cols: terminal.cols,
            rows: terminal.rows,
        }));
    }

    window.addEventListener(
        'resize',
        () => {
            if (fitAddon) {
                fitAddon.fit();
            }

            sendResize();
        }
    );
}





/*
 * SMS: нормализация и проверка номера телефона.
 *
 * Допустимый итоговый формат:
 * +375XXYYYYYYY
 *
 * XX:
 * 29
 * 33
 * 44
 */
function normalizeSmsPhone(value) {
  const raw = String(value).trim();

  /*
   * В базе может быть несколько номеров:
   *
   * 12345, 2100375
   * 12345, +375291112233
   *
   * Стационарный пятизначный номер
   * для SMS не подходит.
   *
   * Берём первый номер, содержащий
   * не менее 7 цифр.
   */
  const parts = raw.split(/[,;]/);

  let digits = '';

  for (const part of parts) {
    const candidate = part.replace(/\D/g, '');

    if (candidate.length >= 7) {
      digits = candidate;
      break;
    }
  }

  /*
   * Если разделителей не было,
   * обрабатываем всё значение.
   */
  if (digits === '') {
    digits = raw.replace(/\D/g, '');
  }

  /*
   * Местный семизначный мобильный номер:
   *
   * 2100375
   *
   * ->
   *
   * +375292100375
   */
  if (digits.length === 7) {
    return `+37529${digits}`;
  }

  /*
   * 291112233
   *
   * ->
   *
   * +375291112233
   */
  if (digits.length === 9) {
    return `+375${digits}`;
  }

  /*
   * 0291112233
   *
   * ->
   *
   * +375291112233
   */
  if (
    digits.length === 10
    && digits.startsWith('0')
  ) {
    return `+375${digits.slice(1)}`;
  }

  /*
   * 80291112233
   *
   * ->
   *
   * +375291112233
   */
  if (
    digits.length === 11
    && digits.startsWith('80')
  ) {
    return `+375${digits.slice(2)}`;
  }

  /*
   * 375291112233
   *
   * ->
   *
   * +375291112233
   */
  if (
    digits.length === 12
    && digits.startsWith('375')
  ) {
    return `+${digits}`;
  }

  return raw;
}

function isValidSmsPhone(value) {
  return /^\+375(?:29|33|44)\d{7}$/
    .test(value);
}

document
  .querySelectorAll('[data-sms-form]')
  .forEach((form) => {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const phoneInput = form.querySelector(
      '[data-sms-phone]'
    );

    const submitButton = form.querySelector(
      '[data-sms-submit]'
    );

    if (
      !(phoneInput instanceof HTMLInputElement)
      || !(submitButton instanceof HTMLButtonElement)
    ) {
      return;
    }

    const validatePhone = () => {
      const valid = isValidSmsPhone(
        phoneInput.value
      );

      phoneInput.classList.toggle(
        'is-invalid',
        !valid
      );

      submitButton.disabled = !valid;

      phoneInput.setCustomValidity(
        valid
          ? ''
          : 'Номер должен иметь формат +375XX1112233, где XX — 29, 33 или 44.'
      );

      return valid;
    };

    const normalizePhone = () => {
      phoneInput.value = normalizeSmsPhone(
        phoneInput.value
      );

      validatePhone();
    };

    /*
     * Сразу нормализуем номер,
     * полученный из базы.
     */
    normalizePhone();

    /*
     * Во время ввода только проверяем.
     */
    phoneInput.addEventListener(
      'input',
      validatePhone
    );

    /*
     * После ухода из поля приводим
     * номер к каноническому виду.
     */
    phoneInput.addEventListener(
      'blur',
      normalizePhone
    );

    form.addEventListener(
      'submit',
      (event) => {
        normalizePhone();

        if (!validatePhone()) {
          event.preventDefault();
          phoneInput.focus();
        }
      }
    );
  });