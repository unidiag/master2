<?php


/** @var int $house */
/** @var string $subscriberAddress */
/** @var string $subscriberTariff */
/** @var string $subscriberKarandashDescr */
/** @var string $subscriberPhone */
/** @var string $houseDescr */
/** @var string $personal */
/** @var string $subscriber */
/** @var int $subscriberDebt */
/** @var bool $subscriberOnKarandash */
/** @var array $apartments */
/** @var array $payments */
/** @var array $qrRows */
/** @var bool $subscriberDisconnected */


if ($personal !== ''): ?>




<section class="payments-page">
    <div class="page-heading payments-heading">
        <div>
            <a
                class="apartments-heading__back"
                href="<?= e(url([
                    'module' => 'stat',
                    'house' => $house,
                ])) ?>"
            >
                ← Квартиры дома
            </a>

            <h1>
                <?= e(
                    $subscriber !== ''
                        ? $subscriber
                        : 'Абонент'
                ) ?>
            </h1>

            <?php if ($subscriberAddress !== ''): ?>
                <div class="page-heading__counter">
                    <?= e($subscriberAddress) ?>
                </div>
            <?php endif; ?>

            <div class="payments-heading__meta">
                Лицевой счёт:
                <strong><?= e($personal) ?></strong>

                <?php if ($subscriberTariff !== ''): ?>
                    · <?= e($subscriberTariff) ?>
                <?php endif; ?>
            </div>

            <?php
            $subscriberBalance = (float) ($data['balance'] ?? 0);

            if ($subscriberBalance < 0): ?>
                <div class="payments-heading__debt payments-heading__debt--clear">
                    <span>Аванс</span>
                    <strong>
                        <?= e(number_format(
                            abs($subscriberBalance),
                            2,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>
            <?php else: ?>
                <div class="<?= $subscriberDebt > 0
                    ? 'payments-heading__debt payments-heading__debt--positive'
                    : 'payments-heading__debt payments-heading__debt--clear'
                ?>">
                    <span>Задолженность</span>
                    <strong>
                        <?= e(number_format(
                            $subscriberDebt,
                            2,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>
            <?php endif; ?>

            <?php if ($subscriberKarandashDescr !== ''): ?>
                <button
                    class="payments-heading__karandash"
                    type="button"
                    data-modal-open="karandash-modal"
                    title="Изменить запись на карандаше"
                >
                    <?= nl2br(e($subscriberKarandashDescr)) ?>
                </button>
            <?php endif; ?>

        </div>

        <div class="payments-heading__actions">


            <?php
                render_sms_button(
                    $subscriberAddress,
                    $subscriberPhone,
                    $personal,
                    $subscriberDebt,
                    (string) $house,
                    $tickets->countByAddress($subscriberAddress),
                    $subscriber,
                    $recentSms ?? []
                );
            ?>

            <button
                class="button<?= $subscriberOnKarandash
                    ? ' karandash_edit'
                    : ''
                ?>"
                type="button"
                data-modal-open="karandash-modal"
            >
                Карандаш
            </button>

            
            <?php if (current_user() !== 'kassa'): ?>
                <form
                    method="post"
                    class="payments-heading__disconnect"
                    onsubmit="return confirm(
                        '<?= $subscriberDisconnected
                            ? 'Подтвердить подключение абонента?'
                            : 'Отправить сообщение об отключении абонента?'
                        ?>'
                    )"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="telegram_disconnect"
                    >

                    <input
                        type="hidden"
                        name="house"
                        value="<?= e($house) ?>"
                    >

                    <input
                        type="hidden"
                        name="personal"
                        value="<?= e($personal) ?>"
                    >

                    <button
                        class="button <?= $subscriberDisconnected
                            ? 'connect'
                            : 'danger'
                        ?>"
                        type="submit"
                    >
                        <?= $subscriberDisconnected
                            ? 'Подключить'
                            : 'Отключить'
                        ?>
                    </button>
                </form>
            <?php endif; ?>


        </div>




        <dialog class="modal" id="karandash-modal">
            <form method="post">
                <div class="modal-head">
                    <h2>
                        <?= $subscriberOnKarandash
                            ? 'Изменить запись'
                            : 'Взять на карандаш'
                        ?>
                    </h2>

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
                    name="return_module"
                    value="stat"
                >

                <input
                    type="hidden"
                    name="action"
                    value="karandash_add"
                >

                <input
                    type="hidden"
                    name="house"
                    value="<?= e($house) ?>"
                >

                <input
                    type="hidden"
                    name="personal"
                    value="<?= e($personal) ?>"
                >

                <input
                    type="hidden"
                    name="address"
                    value="<?= e($subscriberAddress) ?>"
                >

                <div class="karandash-address">
                    <span>Абонент</span>

                    <strong>
                        <?= e(
                            $subscriber !== ''
                                ? $subscriber
                                : 'Без имени'
                        ) ?>
                    </strong>
                </div>

                <div class="karandash-address">
                    <span>Адрес</span>

                    <strong>
                        <?= e(
                            $subscriberAddress !== ''
                                ? $subscriberAddress
                                : $house
                        ) ?>
                    </strong>
                </div>

                <label>
                    Причина

                    <textarea
                        class="input"
                        name="descr"
                        rows="6"
                        maxlength="2000"
                        <?= $subscriberOnKarandash ? '' : 'required' ?>
                        placeholder="Почему абонент взят на карандаш"
                    ><?= e($subscriberKarandashDescr) ?></textarea>
                </label>

                <div class="modal-actions">
                    <button
                        class="button"
                        type="button"
                        data-modal-close
                    >
                        Отмена
                    </button>

                    <button
                        class="button primary"
                        type="submit"
                    >
                        Сохранить
                    </button>
                </div>
            </form>
        </dialog>



    </div>




        

        <?php if (!$payments): ?>
            <div class="empty-state">
                <strong>Операции не обнаружены</strong>
                <span>
                    В истории нет изменений задолженности.
                </span>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <?php
                $paymentUpdate = (string) (
                    $payment['update']
                    ?? ''
                );

                $amount = (float) (
                    $payment['amount']
                    ?? 0
                );

                $currentDebt = (float) (
                    $payment['current_debt']
                    ?? 0
                );

                $type = (string) (
                    $payment['type']
                    ?? 'payment'
                );

                $isPayment =
                    $type === 'payment';

                $isDisconnectCreated =
                    $type === 'disconnect_created';

                $isDisconnectDeleted =
                    $type === 'disconnect_deleted';

                $isDisconnect =
                    $isDisconnectCreated
                    || $isDisconnectDeleted;
                ?>

                <article
                    class="payment-item payment-item--<?= $isDisconnect
                        ? 'disconnect'
                        : ($isPayment ? 'payment' : 'charge')
                    ?>"
                >
                    <div
                        class="payment-item__date<?= $isDisconnectCreated
                            ? ' payment-item__date--disconnect'
                            : ($isDisconnectDeleted
                                ? ' payment-item__date--reconnect'
                                : '')
                        ?>"
                    >
                        <?= e(format_unix_time(
                            $paymentUpdate,
                            $isDisconnect
                                ? 'd.m.Y H:i:s'
                                : 'd.m.Y'
                        )) ?>
                    </div>

                    <div class="payment-item__description">
                        <?php if ($isDisconnectCreated): ?>

                            Отключение

                            <?php if (
                                trim(
                                    (string) (
                                        $payment['tariff']
                                        ?? ''
                                    )
                                ) !== ''
                            ): ?>
                                <span class="muted">
                                    · <?= e(
                                        (string) $payment['tariff']
                                    ) ?>
                                </span>
                            <?php endif; ?>

                        <?php elseif ($isDisconnectDeleted): ?>

                            Снято отключение

                        <?php else: ?>

                            <?= $isPayment
                                ? 'Оплата'
                                : 'Начислено'
                            ?>

                        <?php endif; ?>
                    </div>

                    <div class="payment-item__amount">

                        <?php if ($isDisconnect): ?>

                            <span class="payment-item__balance">
                                <?= e(number_format(
                                    $currentDebt,
                                    2,
                                    ',',
                                    ' '
                                )) ?>
                            </span>

                        <?php else: ?>

                            <span class="payment-item__operation-sum">
                                <?= $isPayment
                                    ? '−'
                                    : '+'
                                ?><?= e(number_format(
                                    $amount,
                                    2,
                                    ',',
                                    ' '
                                )) ?>
                            </span>

                            <span class="payment-item__balance">
                                <?= e(number_format(
                                    $currentDebt,
                                    2,
                                    ',',
                                    ' '
                                )) ?>
                            </span>

                        <?php endif; ?>

                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>



<?php elseif ($house !== ''): ?>
    <section class="apartments-page">
        <div class="page-heading apartments-heading">

            <div class="apartments-heading__top">

                <div class="apartments-heading__title">
                    <a
                        class="apartments-heading__back"
                        href="?module=stat"
                    >
                        ← Все дома
                    </a>

                    <h1><?= e($house) ?></h1>

                    <div class="page-heading__counter">
                        <?= e(number_format(
                            count($apartments),
                            0,
                            ',',
                            ' '
                        )) ?>
                        квартир
                    </div>
                </div>

                <button
                    type="button"
                    class="house-note<?= $houseDescr === ''
                        ? ' house-note--empty'
                        : ''
                    ?>"
                    data-modal-open="house-descr-modal"
                    title="<?= e(
                        $houseDescr !== ''
                            ? $houseDescr
                            : 'Нет инфо'
                    ) ?>"
                >
                    <span class="house-note__icon">✎</span>

                    <span class="house-note__text">
                        <?= e(
                            $houseDescr !== ''
                                ? $houseDescr
                                : 'Нет инфо'
                        ) ?>
                    </span>
                </button>

            </div>

            <div class="apartments-heading__controls">


                <button
                    type="button"
                    class="button"
                    data-modal-open="house-qr-modal"
                >
                    QR ящиков
                    <?php if ($qrRows): ?>
                        (<?= count($qrRows) ?>)
                    <?php endif; ?>
                </button>            

                <label class="apartments-group-control">
                    <span>Группа</span>

                    <select
                        id="apartments-group-size"
                        class="apartments-group-control__select"
                        data-house="<?= e($house) ?>"
                        data-csrf-token="<?= e(csrf_token()) ?>"
                    >
                        <?php for (
                            $groupSize = 0;
                            $groupSize <= 6;
                            $groupSize++
                        ): 
                            if ($groupSize === 1) continue;
                        ?>
                            <option
                                value="<?= e((string) $groupSize) ?>"
                                <?= $groupSize === $apartmentGroupSize
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= $groupSize === 0
                                    ? 'Не группировать'
                                    : e((string) $groupSize)
                                ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>            



                <label class="apartments-sort">
                    <input
                        id="apartments-sort-debt"
                        type="checkbox"
                    >

                    <span>Задолженность</span>
                </label>
            </div>
        </div>








        <dialog
            class="modal house-qr-modal"
            id="house-qr-modal"
        >
            <div class="house-qr-modal__content">

                <div class="modal-head">
                    <div>
                        <h2>QR ящиков</h2>
                        <div class="muted">
                            <?= e($house) ?>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="icon-button"
                        data-modal-close
                        aria-label="Закрыть"
                    >
                        ×
                    </button>
                </div>

                <?php if (!$qrRows): ?>
                    <div class="house-qr-empty">
                        QR-коды для этого дома пока не добавлены.
                    </div>
                <?php else: ?>

                    <div class="house-qr-list">

                        <?php foreach ($qrRows as $qrRow): ?>
                            <?php
                            $qrEntrance = (int) (
                                $qrRow['entrance']
                                ?? 0
                            );
                            ?>

                            <div class="house-qr-row">

                                <div class="house-qr-row__code">
                                    <?= e(
                                        (string) $qrRow['qrcode']
                                    ) ?>
                                </div>

                                <div class="house-qr-row__entrance">
                                    <?= $qrEntrance > 0
                                        ? 'Подъезд ' . $qrEntrance
                                        : 'Подъезд не указан'
                                    ?>
                                </div>

                                <form
                                    method="post"
                                    class="house-qr-row__delete"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="qr_delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="house"
                                        value="<?= e($house) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="qr_id"
                                        value="<?= e(
                                            (string) $qrRow['id']
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="button danger house-qr-row__delete-button"
                                        title="Удалить"
                                    >
                                        ×
                                    </button>
                                </form>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <form
                    method="post"
                    class="house-qr-add"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="qr_add"
                    >

                    <input
                        type="hidden"
                        name="house"
                        value="<?= e($house) ?>"
                    >

                    <div class="house-qr-add__fields">

                        <label>
                            QR-код

                            <input
                                type="text"
                                class="input house-qr-add__code"
                                name="qrcode"
                                maxlength="4"
                                minlength="4"
                                pattern="[0-9]{4}"
                                inputmode="numeric"
                                placeholder="0000"
                                autocomplete="off"
                                required
                            >
                        </label>

                        <label>
                            Подъезд

                            <select
                                class="input"
                                name="entrance"
                            >
                                <option value="0">
                                    Не установлен
                                </option>

                                <?php for (
                                    $entrance = 1;
                                    $entrance <= 20;
                                    $entrance++
                                ): ?>
                                    <option
                                        value="<?= $entrance ?>"
                                    >
                                        <?= $entrance ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>

                    </div>

                    <div class="modal-actions">
                        <button
                            type="button"
                            class="button"
                            data-modal-close
                        >
                            Закрыть
                        </button>

                        <button
                            type="submit"
                            class="button primary"
                        >
                            ＋ Добавить
                        </button>
                    </div>

                </form>

            </div>
        </dialog>




        

        <dialog
            class="modal house-descr-modal"
            id="house-descr-modal"
        >
            <form method="post">
                <div class="modal-head">
                    <h2>Информация по дому</h2>

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
                    value="house_descr"
                >

                <input
                    type="hidden"
                    name="house"
                    value="<?= e($house) ?>"
                >

                <div class="house-descr-modal__house">
                    <span>Дом</span>
                    <strong><?= e($house) ?></strong>
                </div>

                <label>
                    Заметка

                    <textarea
                        class="input house-descr-modal__textarea"
                        name="descr"
                        rows="7"
                        maxlength="2000"
                        placeholder="Коды домофонов, доступ, оборудование и другая информация"
                    ><?= e($houseDescr) ?></textarea>
                </label>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="button"
                        data-modal-close
                    >
                        Отмена
                    </button>

                    <button
                        type="submit"
                        class="button primary"
                    >
                        Сохранить
                    </button>
                </div>
            </form>
        </dialog>

    <?php if (!$apartments): ?>
        <div class="empty-state">
            В этом доме квартиры не найдены.
        </div>
    <?php else: ?>
        <div class="apartment-grid" id="apartment-grid">
            <?php foreach ($apartments as $apartment): ?>
                <?php
                $tariff = trim((string) ($apartment['tariff'] ?? ''));
                $debt = (float) ($apartment['debt'] ?? 0);





                $apartmentNumber = trim(
                    (string) (
                        $apartment['number']
                        ?? ''
                    )
                );

                $apartmentAddress =
                    (string) $house
                    . '-'
                    . $apartmentNumber;

                $isDisconnected = isset(
                    $disconnectedAddresses[
                        $apartmentAddress
                    ]
                );




                $apartmentClass = '';

                if ($tariff === 'Нет договора') {
                    $apartmentClass = ' apartment-card--inactive';
                } elseif ($tariff === 'Аналоговый пакет') {
                    $apartmentClass = ' apartment-card--analog';
                } elseif ($tariff === 'Цифровой пакет') {
                    $apartmentClass = ' apartment-card--digital';
                } elseif ($tariff === 'IPTV') {
                    $apartmentClass = ' apartment-card--iptv';
                } elseif (
                    str_contains(
                        mb_strtolower($tariff, 'UTF-8'),
                        'государствен'
                    )
                ) {
                    $apartmentClass = ' apartment-card--state-package';
                }

                if (
                    current_user() !== 'kassa'
                    && (bool) ($apartment['debt_always_zero'] ?? false)
                ) {
                    $apartmentClass .= ' apartment-card--always-zero';
                }
                ?>

                <a
                    class="apartment-card<?= $apartmentClass ?>"
                    href="<?= e(url([
                        'module' => 'stat',
                        'house' => $house,
                        'personal' => (string) ($apartment['personal'] ?? ''),
                    ])) ?>"
                    data-debt="<?= e(number_format($debt, 2, '.', '')) ?>"
                    data-apartment="<?= e((string) ($apartment['number'] ?? '')) ?>"
                    data-personal="<?= e(
                        (string) ($apartment['personal'] ?? '')
                    ) ?>"
                    data-apartment="<?= e(
                        (string) ($apartment['number'] ?? '')
                    ) ?>"                    
                >
                    <div
                        class="apartment-card__number"
                        data-disconnected="<?= $isDisconnected ? '1' : '0' ?>"
                        <?= $isDisconnected
                            ? 'style="color: red;"'
                            : ''
                        ?>
                    >
                        <?= e($apartmentNumber) ?>
                    </div>

                    <div
                        class="apartment-card__subscriber"
                        title="<?= e((string) ($apartment['subscriber'] ?? '')) ?>"
                    >
                        <?= e((string) ($apartment['subscriber'] ?? '')) ?>
                    </div>

                    <?php
                    $karandashDescr = trim(
                        (string) ($apartment['karandash_descr'] ?? '')
                    );
                    ?>

                    <?php if ($karandashDescr !== ''): ?>
                        <div
                            class="apartment-card__karandash"
                            title="<?= e($karandashDescr) ?>"
                        >
                            <?= nl2br(e($karandashDescr)) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($debt != 0): ?>
                        <div class="<?= $debt > 0
                            ? 'apartment-card__debt'
                            : 'apartment-card__balance'
                        ?>">
                            <?= e(number_format($debt, 2, ',', ' ')) ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>


        </div>

        <form
            id="apartment-disconnect-form"
            method="post"
            hidden
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="telegram_disconnect"
            >

            <input
                type="hidden"
                name="house"
                value="<?= e($house) ?>"
            >

            <input
                id="apartment-disconnect-personal"
                type="hidden"
                name="personal"
                value=""
            >
        </form>

        <?php if (
            $house !== ''
            && $personal === ''
        ): ?>

            <div class="house-control">

                <form
                    method="post"
                    class="house-control__form"
                    onsubmit="return confirm('Подтвердить выполнение контроля дома?')"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="house_control"
                    >

                    <input
                        type="hidden"
                        name="house"
                        value="<?= e($house) ?>"
                    >

                    <button
                        type="submit"
                        class="button house-control__button"
                    >
                        Контроль:

                        <?php if ($houseControl !== ''): ?>

                            <?= e(
                                (new DateTimeImmutable(
                                    $houseControl
                                ))->format('d.m.Y H:i:s')
                            ) ?>

                        <?php else: ?>

                            не выполнялся

                        <?php endif; ?>
                    </button>

                </form>

            </div>

        <?php endif; ?>



        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById(
                'apartments-sort-debt'
            );

            const groupSelect = document.getElementById(
                'apartments-group-size'
            );

            const grid = document.getElementById(
                'apartment-grid'
            );



            const disconnectForm = document.getElementById(
                'apartment-disconnect-form'
            );

            const disconnectPersonal = document.getElementById(
                'apartment-disconnect-personal'
            );

            let savedGroupSize = groupSelect.value;

            async function saveApartmentGroup(groupSize) {
                const house = groupSelect.dataset.house || '';
                const csrfToken =
                    groupSelect.dataset.csrfToken || '';

                const body = new FormData();

                body.set('ajax', 'save_apartment_group');
                body.set('csrf_token', csrfToken);
                body.set('house', house);
                body.set('group_size', String(groupSize));

                const response = await fetch('index.php', {
                    method: 'POST',
                    body,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.error ||
                        'Не удалось сохранить размер группы.'
                    );
                }

                savedGroupSize = String(result.group_size);
            }

            if (!checkbox || !groupSelect || !grid) {
                return;
            }


            const LONG_PRESS_DELAY = 600;
            const LONG_PRESS_MOVE = 12;

            let longPressTimer = null;
            let longPressCard = null;
            let longPressX = 0;
            let longPressY = 0;

            let suppressNextClick = false;
            let ignoreContextMenuUntil = 0;
            let disconnectInProgress = false;


            const disconnectAllowed =
                <?= current_user() !== 'kassa'
                    ? 'true'
                    : 'false'
                ?>;


            async function disconnectApartment(card) {
                if (
                    !disconnectAllowed
                    || disconnectInProgress
                ) {
                    return;
                }

                disconnectInProgress = true;

                const personal =
                    card.dataset.personal || '';

                if (personal === '') {
                    window.alert(
                        'У квартиры отсутствует лицевой счёт.'
                    );

                    return;
                }

                const apartmentNumber =
                    card.querySelector(
                        '.apartment-card__number'
                    );

                const isDisconnected =
                    apartmentNumber
                    && apartmentNumber.dataset.disconnected === '1';

                const house =
                    groupSelect.dataset.house || '';

                const csrfToken =
                    groupSelect.dataset.csrfToken || '';

                const body = new FormData();

                body.set(
                    'action',
                    'telegram_disconnect'
                );

                body.set(
                    'csrf_token',
                    csrfToken
                );

                body.set(
                    'house',
                    house
                );

                body.set(
                    'personal',
                    personal
                );

                body.set(
                    'ajax',
                    '1'
                );

                /*
                * Показываем общий loader
                * на время ожидания ответа сервера.
                */
                showPageLoader();

                try {
                    const response = await fetch(
                        'index.php',
                        {
                            method: 'POST',
                            body,
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                            },
                        }
                    );

                    const result =
                        await response.json();

                    if (
                        !response.ok
                        || !result.success
                    ) {
                        throw new Error(
                            result.error
                            || 'Не удалось изменить состояние отключения.'
                        );
                    }

                    /*
                    * Сервер сам сообщает текущее состояние:
                    *
                    * true  — отключён
                    * false — отметка снята
                    */
                    if (apartmentNumber) {
                        apartmentNumber.dataset.disconnected =
                            result.disconnected
                                ? '1'
                                : '0';

                        apartmentNumber.style.color =
                            result.disconnected
                                ? 'red'
                                : '';
                    }
                } catch (error) {
                    window.alert(
                        error instanceof Error
                            ? error.message
                            : 'Не удалось изменить состояние отключения.'
                    );
                } finally {
                    disconnectInProgress = false;
                    hidePageLoader();
                }
            }





            function cancelLongPress() {
                if (longPressTimer !== null) {
                    clearTimeout(longPressTimer);
                }

                longPressTimer = null;
                longPressCard = null;
            }


            /*
            * ПК:
            * правая кнопка мыши сразу выполняет
            * отключение выбранной квартиры.
            */
            grid.addEventListener(
                'contextmenu',
                function (event) {
                    const card = event.target.closest(
                        '.apartment-card'
                    );

                    if (!card) {
                        return;
                    }

                    event.preventDefault();

                    /*
                    * После long press мобильный браузер
                    * иногда дополнительно создаёт
                    * событие contextmenu.
                    */
                    if (
                        Date.now()
                        < ignoreContextMenuUntil
                    ) {
                        return;
                    }

                    disconnectApartment(card);
                }
            );


            /*
            * Телефон / планшет:
            * длинное нажатие выполняет отключение.
            */
            grid.addEventListener(
                'pointerdown',
                function (event) {
                    if (event.pointerType === 'mouse') {
                        return;
                    }

                    const card = event.target.closest(
                        '.apartment-card'
                    );

                    if (!card) {
                        return;
                    }

                    cancelLongPress();

                    longPressCard = card;
                    longPressX = event.clientX;
                    longPressY = event.clientY;

                    longPressTimer = window.setTimeout(
                        function () {
                            if (!longPressCard) {
                                return;
                            }

                            /*
                            * После long press не разрешаем
                            * обычному click открыть карточку.
                            */
                            suppressNextClick = true;

                            /*
                            * Подавляем возможный contextmenu,
                            * который браузер создаст следом.
                            */
                            ignoreContextMenuUntil =
                                Date.now() + 1000;

                            const card = longPressCard;

                            longPressTimer = null;
                            longPressCard = null;

                            disconnectApartment(card);
                        },
                        LONG_PRESS_DELAY
                    );
                }
            );


            /*
            * При движении пальца считаем это скроллом,
            * а не длинным нажатием.
            */
            grid.addEventListener(
                'pointermove',
                function (event) {
                    if (longPressTimer === null) {
                        return;
                    }

                    const deltaX = Math.abs(
                        event.clientX - longPressX
                    );

                    const deltaY = Math.abs(
                        event.clientY - longPressY
                    );

                    if (
                        deltaX > LONG_PRESS_MOVE
                        || deltaY > LONG_PRESS_MOVE
                    ) {
                        cancelLongPress();
                    }
                }
            );


            grid.addEventListener(
                'pointerup',
                function () {
                    cancelLongPress();
                }
            );


            grid.addEventListener(
                'pointercancel',
                function () {
                    cancelLongPress();
                }
            );


            /*
            * После long press браузер может
            * сгенерировать обычный click по <a>.
            * Не разрешаем открыть страницу абонента.
            */
            grid.addEventListener(
                'click',
                function (event) {
                    if (!suppressNextClick) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    suppressNextClick = false;
                },
                true
            );




            /*
            * Сохраняем исходные карточки отдельно.
            * После группировки непосредственными дочерними
            * элементами grid станут уже блоки.
            */
            const cards = Array.from(
                grid.querySelectorAll('.apartment-card')
            );

            function compareApartments(a, b) {
                const apartmentA =
                    a.dataset.apartment || '';

                const apartmentB =
                    b.dataset.apartment || '';

                return apartmentA.localeCompare(
                    apartmentB,
                    'ru',
                    {
                        numeric: true,
                        sensitivity: 'base'
                    }
                );
            }

            function getSortedCards() {
                const sortedCards = cards.slice();

                if (checkbox.checked) {
                    sortedCards.sort(function (a, b) {
                        const debtA =
                            parseFloat(a.dataset.debt) || 0;

                        const debtB =
                            parseFloat(b.dataset.debt) || 0;

                        if (debtB !== debtA) {
                            return debtB - debtA;
                        }

                        return compareApartments(a, b);
                    });
                } else {
                    sortedCards.sort(compareApartments);
                }

                return sortedCards;
            }

            function renderApartmentGroups() {
                const groupSize = Math.max(
                0,
                Math.min(
                    6,
                    Number.parseInt(groupSelect.value, 10)
                )
                );

                const sortedCards = getSortedCards();

                grid.replaceChildren();

                if (groupSize === 0) {
                    sortedCards.forEach(function (card) {
                        grid.appendChild(card);
                });

                grid.classList.add('apartment-grid--ungrouped');
                    return;
                }

                grid.classList.remove('apartment-grid--ungrouped');

                for (
                    let index = 0;
                    index < sortedCards.length;
                    index += groupSize
                ) {
                    const group = document.createElement('div');

                    group.className = 'apartment-group';

                    group.style.setProperty(
                        '--apartment-group-size',
                        String(groupSize)
                    );

                    const groupCards = sortedCards.slice(
                        index,
                        index + groupSize
                    );

                    groupCards.forEach(function (card) {
                        group.appendChild(card);
                    });

                    grid.appendChild(group);
                }
            }

            checkbox.addEventListener(
                'change',
                renderApartmentGroups
            );

            groupSelect.addEventListener(
            'change',
            async function () {
                const previousValue = savedGroupSize;

                renderApartmentGroups();

                groupSelect.disabled = true;

                try {
                await saveApartmentGroup(
                    Number.parseInt(
                    groupSelect.value,
                    10
                    )
                );
                } catch (error) {
                console.error(
                    'Ошибка сохранения группировки:',
                    error
                );

                groupSelect.value = previousValue;
                renderApartmentGroups();

                window.alert(
                    error instanceof Error
                    ? error.message
                    : 'Не удалось сохранить группировку.'
                );
                } finally {
                groupSelect.disabled = false;
                }
            }
            );

            /*
            * При первом открытии сразу формируем блоки
            * по четыре квартиры.
            */
            renderApartmentGroups();
        });
        </script>




    <?php endif; ?>
</section>
<?php else: ?>




<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('house-sort');
    const grid = document.getElementById('house-grid');

    if (!select || !grid) {
        return;
    }

    const cookieName = 'master_house_sort';

    const allowedSorts = [
        'default',
        'debt',
        'penetration',
        'control'
    ];

    const cards = Array.from(
        grid.querySelectorAll('.house-card-link')
    );

    const savedSort = getCookie(cookieName);

    if (
        savedSort
        && allowedSorts.includes(savedSort)
    ) {
        select.value = savedSort;
    }

    select.addEventListener('change', function () {
        setCookie(
            cookieName,
            select.value,
            365
        );

        sortCards();
    });



function sortCards() {
    const sortedCards = cards.slice();

    /*
     * По умолчанию показываем все дома.
     * При сортировке "По контролю"
     * неактивные будут скрыты ниже.
     */
    cards.forEach(function (card) {
        card.hidden = false;
    });

    if (select.value === 'debt') {
        sortedCards.sort(function (a, b) {
            const debtA =
                parseFloat(a.dataset.debt) || 0;

            const debtB =
                parseFloat(b.dataset.debt) || 0;

            if (debtB !== debtA) {
                return debtB - debtA;
            }

            return compareHouses(a, b);
        });
    } else if (select.value === 'penetration') {
        sortedCards.sort(function (a, b) {
            const penetrationA =
                parseFloat(
                    a.dataset.penetration
                ) || 0;

            const penetrationB =
                parseFloat(
                    b.dataset.penetration
                ) || 0;

            if (
                penetrationB !== penetrationA
            ) {
                return (
                    penetrationB
                    - penetrationA
                );
            }

            return compareHouses(a, b);
        });
    } else if (select.value === 'control') {
        sortedCards.sort(function (a, b) {
            const controlA =
                parseInt(
                    a.dataset.control || '0',
                    10
                );

            const controlB =
                parseInt(
                    b.dataset.control || '0',
                    10
                );

            /*
             * Дома без контроля —
             * в самом начале.
             */
            if (
                controlA === 0
                && controlB !== 0
            ) {
                return -1;
            }

            if (
                controlB === 0
                && controlA !== 0
            ) {
                return 1;
            }

            /*
             * Затем от самого старого
             * контроля к самому свежему.
             */
            if (controlA !== controlB) {
                return controlA - controlB;
            }

            return compareHouses(a, b);
        });
    } else {
        sortedCards.sort(compareHouses);
    }

    sortedCards.forEach(function (card) {
        /*
         * При сортировке по контролю
         * не показываем неактивные дома.
         */
        if (
            select.value === 'control'
            && card.querySelector(
                '.house-card--inactive'
            )
        ) {
            card.hidden = true;
        }

        grid.appendChild(card);
    });
}



    function compareHouses(a, b) {
        return (
            a.dataset.house || ''
        ).localeCompare(
            b.dataset.house || '',
            'ru',
            {
                numeric: true,
                sensitivity: 'base'
            }
        );
    }

    function getCookie(name) {
        const prefix =
            encodeURIComponent(name) + '=';

        const cookies =
            document.cookie.split(';');

        for (
            let index = 0;
            index < cookies.length;
            index++
        ) {
            const cookie =
                cookies[index].trim();

            if (
                cookie.indexOf(prefix) === 0
            ) {
                return decodeURIComponent(
                    cookie.substring(
                        prefix.length
                    )
                );
            }
        }

        return '';
    }

    function setCookie(
        name,
        value,
        days
    ) {
        const expires = new Date();

        expires.setTime(
            expires.getTime()
            + days * 24 * 60 * 60 * 1000
        );

        document.cookie =
            encodeURIComponent(name)
            + '='
            + encodeURIComponent(value)
            + '; expires='
            + expires.toUTCString()
            + '; path=/'
            + '; SameSite=Lax';
    }

    /*
     * Сразу применяем сортировку,
     * восстановленную из cookie.
     */
    sortCards();
});
</script>




    <?php
    /** @var array<string, mixed> $data */

    $houses = $data['houses'] ?? [];
    $databaseUpdate = (string) ($data['update'] ?? '');
    $subscribersTotal = (int) ($data['total'] ?? 0);

    $connectedTotal = 0;

    foreach ($houses as $house) {
        $connectedTotal +=
            (int) ($house['state_channels'] ?? 0)
            + (int) ($house['analog_package'] ?? 0)
            + (int) ($house['digital_package'] ?? 0);
    }


    $stateChannelsTotal = 0;
    $analogPackageTotal = 0;
    $digitalPackageTotal = 0;
    $iptvPackageTotal = 0;

    foreach ($houses as $house) {
        $stateChannelsTotal += (int) ($house['state_channels'] ?? 0);
        $analogPackageTotal += (int) ($house['analog_package'] ?? 0);
        $digitalPackageTotal += (int) ($house['digital_package'] ?? 0);
        $iptvPackageTotal += (int) ($house['iptv_package'] ?? 0);
    }

    $connectedTotal =
        $stateChannelsTotal
        + $analogPackageTotal
        + $digitalPackageTotal
        + $iptvPackageTotal;

    $stateChannelsPercent = $connectedTotal > 0
        ? ($stateChannelsTotal / $connectedTotal) * 100
        : 0;

    $analogPackagePercent = $connectedTotal > 0
        ? ($analogPackageTotal / $connectedTotal) * 100
        : 0;

    $digitalPackagePercent = $connectedTotal > 0
        ? ($digitalPackageTotal / $connectedTotal) * 100
        : 0;

    $iptvPackagePercent = $connectedTotal > 0
        ? ($iptvPackageTotal / $connectedTotal) * 100
        : 0;



    ?>

    <section class="page-heading">
        <div>
            <h1>Статистика по домам</h1>

            <p class="page-heading__description">
                Последнее обновление:
                <strong>
                    <?= e(format_unix_time($databaseUpdate, 'd.m.Y H:i')) ?>
                </strong>
            </p>
        </div>

        <div class="house-toolbar">
            <label for="house-sort">Сортировка</label>

            <select id="house-sort" class="house-toolbar__select">
                <option value="default">По умолчанию</option>
                <option value="debt">По общей задолженности</option>
                <option value="penetration">По проникновению</option>
                <option value="control">По контролю</option>
            </select>
        </div>

        <div class="page-heading__counter">
            <div>
                <?= e(number_format(count($houses), 0, ',', ' ')) ?>
                домов ·
                <?= e(number_format($connectedTotal, 0, ',', ' ')) ?>
                из
                <?= e(number_format($subscribersTotal, 0, ',', ' ')) ?>
                абонентов
            </div>

            <div
                class="page-heading__packages"
                title="Государственные каналы / Аналоговый пакет / Цифровой пакет / IPTV"
            >
                <span class="page-heading__packages-count">
                    <?= e(number_format($stateChannelsTotal, 0, ',', ' ')) ?>/<?= e(number_format($analogPackageTotal, 0, ',', ' ')) ?>/<?= e(number_format($digitalPackageTotal, 0, ',', ' ')) ?>/<?= e(number_format($iptvPackageTotal, 0, ',', ' ')) ?>
                </span>

                <span class="page-heading__packages-percent">
                    <?= e(number_format($stateChannelsPercent, 0, ',', ' ')) ?>%/<?= e(number_format($analogPackagePercent, 0, ',', ' ')) ?>%/<?= e(number_format($digitalPackagePercent, 0, ',', ' ')) ?>%/<?= e(number_format($iptvPackagePercent, 0, ',', ' ')) ?>%
                </span>
            </div>
        </div>
    </section>

    <?php if (!$houses): ?>

        <div class="empty-state">
            <strong>Статистика отсутствует</strong>
            <span>В последнем обновлении абонентской базы нет записей.</span>
        </div>

    <?php else: ?>

        <div class="house-grid" id="house-grid">
            <?php foreach ($houses as $house): ?>
                <?php
                $debt = (float) ($house['debt'] ?? 0);
                $debtors = (int) ($house['debtors'] ?? 0);

                $control = trim(
                    (string) ($house['control'] ?? '')
                );

                $controlTimestamp = $control !== ''
                    ? strtotime($control)
                    : 0;

                if ($controlTimestamp === false) {
                    $controlTimestamp = 0;
                }

                $controlLimitTimestamp = strtotime('-3 months');

                $houseControlClass = '';

                if ($controlTimestamp > 0) {
                    $houseControlClass =
                        $controlTimestamp >= $controlLimitTimestamp
                            ? ' house-card--control-fresh'
                            : ' house-card--control-old';
                }              

                $stateChannels = (int) ($house['state_channels'] ?? 0);
                $analogPackage = (int) ($house['analog_package'] ?? 0);
                $digitalPackage = (int) ($house['digital_package'] ?? 0);
                $iptvPackage = (int) ($house['iptv_package'] ?? 0);
                $subscribers = (int) ($house['subscribers'] ?? 0);

                $connected = $stateChannels + $analogPackage + $digitalPackage + $iptvPackage;

                $penetration = $subscribers > 0
                    ? round(($connected / $subscribers) * 100, 1)
                    : 0;

                $penetration = max(0, min(100, $penetration));

                $penetrationWidth = number_format(
                    (float) $penetration,
                    1,
                    '.',
                    ''
                );

                $isEmptyHouse = $connected === 0;
                ?>
                <a
                    class="house-card-link"
                    href="?module=stat&amp;house=<?= rawurlencode((string) ($house['house'] ?? '')) ?>"
                    data-house="<?= e(mb_strtolower((string) ($house['house'] ?? ''), 'UTF-8')) ?>"
                    data-debt="<?= e(number_format($debt, 2, '.', '')) ?>"
                    data-penetration="<?= e($penetrationWidth) ?>"
                    data-control="<?= e((string) $controlTimestamp) ?>"
                >
                    <article
                        class="house-card<?= $isEmptyHouse
                            ? ' house-card--inactive'
                            : ''
                        ?><?= $houseControlClass ?>"
                    >
                    <?php
                    $debt = (float) ($house['debt'] ?? 0);
                    $debtors = (int) ($house['debtors'] ?? 0);

                    $stateChannels = (int) ($house['state_channels'] ?? 0);
                    $analogPackage = (int) ($house['analog_package'] ?? 0);
                    $digitalPackage = (int) ($house['digital_package'] ?? 0);
                    $iptvPackage = (int) ($house['iptv_package'] ?? 0);
                    $subscribers = (int) ($house['subscribers'] ?? 0);

                    $connected = $stateChannels + $analogPackage + $digitalPackage + $iptvPackage;

                    $penetration = $subscribers > 0
                        ? round(($connected / $subscribers) * 100, 1)
                        : 0;

                    $penetration = max(0, min(100, $penetration));

                    $penetrationWidth = number_format(
                        $penetration,
                        1,
                        '.',
                        ''
                    );

                    ?>

                    <header class="house-card__header">
                        <div>
                            <h2 class="house-card__title">
                                <?= e($house['house'] ?? '') ?>
                            </h2>

                            <span
                                class="house-card__subtitle"
                                title="Государственные каналы / Аналоговый пакет / Цифровой пакет / IPTV"
                            >
                                <?= e((int) ($house['state_channels'] ?? 0)) ?>/<?= e((int) ($house['analog_package'] ?? 0)) ?>/<?= e((int) ($house['digital_package'] ?? 0)) ?>/<?= e((int) ($house['iptv_package'] ?? 0)) ?>
                                из
                                <?= e((int) ($house['subscribers'] ?? 0)) ?>
                                квартир
                            </span>

                        </div>

                        <?php
                        $karandashCount = (int) ($house['karandash'] ?? 0);
                        
                        if (
                            $debt > 0
                            || $controlTimestamp > 0
                        ): ?>
                            <div class="house-card__debt">
                                <?php if ($debt != 0): ?>
                                    <strong>
                                        <?= e(number_format($debt, 2, ',', ' ')) ?>
                                        <small>(<?= e($debtors) ?> аб.)</small>
                                    </strong>
                                <?php endif; ?>

                                <?php if ($controlTimestamp > 0): ?>
                                    <div class="house-card__control">
                                        <?= e(date(
                                            'd.m.Y',
                                            $controlTimestamp
                                        )) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($karandashCount > 0): ?>
                            <div class="house-card__karandash">
                                <strong><?= e($karandashCount) ?></strong>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div
                        class="house-card__penetration"
                        title="Проникновение услуг: <?= e(number_format($penetration, 1, ',', ' ')) ?>%"
                    >
                        <div
                            class="house-card__penetration-fill"
                            style="width: <?= e($penetrationWidth) ?>%;"
                        ></div>

                        <span class="house-card__penetration-value">
                            <?= e(number_format($penetration, 0, ',', ' ')) ?>%
                        </span>
                    </div>

                </article>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
<?php endif; ?>
