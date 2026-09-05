 <?php
 


/** @var string $module */



?><dialog
    class="modal create-modal"
    id="create-modal"
>
    <?php if ($module === 'zayavki'): ?>
        <form
            method="post"
            class="ticket-create-form"
            data-ticket-create-form
        >
            <div class="modal-head">
                <h2>Добавить новую заявку от абонента</h2>

                <button
                    type="button"
                    class="icon-button"
                    data-modal-close
                    aria-label="Закрыть"
                >
                    ×
                </button>
            </div>

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="ticket-create-form__person">
                <label>
                    <span>Адрес абонента</span>
                    <input
                        class="input"
                        type="text"
                        name="address"
                        maxlength="50"
                        required
                        autocomplete="off"
                        data-subscriber-address
                        placeholder="Начните вводить улицу"
                    >
                </label>

                <label>
                    <span>ФИО</span>
                    <input
                        class="input"
                        type="text"
                        name="abonent"
                        maxlength="50"
                        required
                        autocomplete="name"
                        data-subscriber-name
                    >
                </label>
            </div>

            <div
                class="ticket-subscriber-info"
                data-subscriber-info
                hidden
            >
                <div class="ticket-subscriber-info__item">
                    <span>Тариф</span>
                    <button
                        type="button"
                        class="ticket-subscriber-tariff"
                        data-subscriber-tariff
                        title="Добавить тариф в примечание"
                    >
                        —
                    </button>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Телефон</span>
                    <strong data-subscriber-phone>—</strong>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Долг</span>
                    <strong data-subscriber-debt>—</strong>
                </div>

                <div class="ticket-subscriber-info__item">
                    <span>Всего заявок</span>
                    <a
                        data-subscriber-tickets
                        href="#"
                        target="_blank"
                        rel="noopener"
                        hidden
                        class="data-subscriber-tickets-count"
                    >
                        0
                    </a>
                    <strong data-subscriber-tickets-zero>0</strong>
                </div>

            </div>


            <label>
                <span>Описание заявки</span>

                <select
                    class="input select"
                    name="description"
                    required
                >
                    <option value="" selected disabled>
                        Выберите описание
                    </option>

                    <option value="нет трансляции">
                        нет трансляции
                    </option>

                    <option value="плохая трансляция">
                        плохая трансляция
                    </option>

                    <option value="настройка каналов">
                        настройка каналов
                    </option>

                    <option value="ремонт квартирной сети">
                        ремонт квартирной сети
                    </option>

                    <option value="авария на линии">
                        авария на линии
                    </option>

                    <option value="подключить на площадке">
                        подключить на площадке
                    </option>

                    <option value="другие услуги">
                        другие услуги
                    </option>
                </select>
            </label>

            <label>
                <span>Примечание</span>

                <input
                    class="input"
                    type="text"
                    name="other"
                    maxlength="50"
                    autocomplete="off"
                >
            </label>

            <div class="ticket-create-form__actions">
                <button
                    class="button primary"
                    type="submit"
                >
                    Добавить
                </button>

                <button
                    class="button button-link"
                    type="button"
                    data-modal-close
                >
                    Отмена
                </button>
            </div>
        </form>
    <?php else: ?>
        <form
            method="post"
            class="ticket-create-form"
            data-ticket-create-form
        >
            <div class="modal-head">
                <h2>Новое подключение</h2>
                <button
                    type="button"
                    class="icon-button"
                    data-modal-close
                    aria-label="Закрыть"
                >
                    ×
                </button>
            </div>

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="ticket-create-form__person">
                <label>
                    <span>Адрес абонента</span>
                    <input
                        class="input"
                        type="text"
                        name="address"
                        maxlength="50"
                        required
                        autocomplete="off"
                        data-subscriber-address
                        placeholder="Начните вводить улицу"
                    >
                </label>

                <label>
                    <span>ФИО</span>
                    <input
                        class="input"
                        type="text"
                        name="abonent"
                        maxlength="50"
                        required
                        autocomplete="name"
                        data-subscriber-name
                    >
                </label>
            </div>

            <label>
                <span>Род коммутации</span>
                <select
                    class="input select"
                    name="description"
                    required
                >
                    <option value="" selected disabled>
                        Выберите действие
                    </option>

                    <option value="отключить всё">
                        отключить всё
                    </option>

                    <option value="отключить временно">
                        отключить временно
                    </option>

                    <option value="подключить госканалы">
                        подключить госканалы
                    </option>

                    <option value="подключить аналоговый пакет">
                        подключить аналоговый пакет
                    </option>

                    <option value="подключить цифровой пакет">
                        подключить цифровой пакет
                    </option>

                    <option value="подключить IPTV пакет">
                        подключить IPTV
                    </option>

                    <option value="переезд на другой адрес">
                        переезд на другой адрес
                    </option>
                </select>
            </label>

            <label>
                <span>Дополнительно</span>
                <input
                    class="input"
                    type="text"
                    name="other"
                    maxlength="50"
                    autocomplete="off"
                >
            </label>

            <div class="ticket-create-form__actions">
                <button
                    class="button primary"
                    type="submit"
                >
                    Сохранить
                </button>

                <button
                    class="button button-link"
                    type="button"
                    data-modal-close
                >
                    Отмена
                </button>
            </div>
        </form>
    <?php endif; ?>
</dialog>







<dialog class="modal" id="complete-modal">
    <form method="post">
        <div class="modal-head">
            <h2>Завершение</h2>

            <button
                type="button"
                class="icon-button"
                data-modal-close
            >
                ×
            </button>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="complete"
        >

        <input
            type="hidden"
            name="id"
            id="complete-id"
        >

        <label>
            Мастер

            <input
                class="input"
                name="master"
                maxlength="50"
                value="<?= e(current_user()) ?>"
                required
            >
        </label>

        <label>
            Результат

            <input
                class="input"
                name="result"
                maxlength="50"
                value="OK"
                required
            >
        </label>

        <label id="cost-field">
            Стоимость

            <input
                class="input"
                name="cost"
                maxlength="10"
                inputmode="decimal"
                value="-"
            >
        </label>

        <button
            class="button primary full"
            type="submit"
        >
            Сохранить
        </button>
    </form>
</dialog>

