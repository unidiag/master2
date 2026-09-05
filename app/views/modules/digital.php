
    <?php
    $digitalRows = $data['channels'] ?? [];
    $digitalServers = $data['servers'] ?? [];
    $digitalDistributors =
        $data['distributors']
        ?? [];

    if (!is_array($digitalDistributors)) {
        $digitalDistributors = [];
    }

    $onlineServers = 0;

    foreach ($digitalServers as $server) {
        if (!empty($server['online'])) {
            $onlineServers++;
        }
    }
    ?>

    <section class="channels-page digital-channels-page">

        <div class="page-heading">
            <div>
                <h1>Цифровые каналы</h1>

                <div
                    class="page-heading__counter"
                    id="digital-channel-counter"
                    data-total="<?= count($digitalRows) ?>"
                >
                    <?= e(number_format(
                        count($digitalRows),
                        0,
                        ',',
                        ' '
                    )) ?>
                    каналов
                </div>
            </div>

            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="digital_refresh"
                >

                <button
                    type="submit"
                    class="button primary"
                    onclick="return confirm(
                        'Получить актуальный список каналов с серверов Astra?'
                    )"
                >
                    ↻ Обновить
                </button>
            </form>
        </div>

        <?php if (
            $digitalServers
            || $digitalDistributors
        ): ?>

            <div class="digital-filter-bar">

                <select
                    id="digital-server-select"
                    class="digital-server-filter__select"
                >
                    <option value="">
                        Все сервера
                    </option>

                    <?php foreach (
                        $digitalServers
                        as $server
                    ): ?>

                        <?php
                        $serverAddress = trim(
                            (string) (
                                $server['address']
                                ?? ''
                            )
                        );

                        if ($serverAddress === '') {
                            continue;
                        }

                        $serverOnline =
                            !empty($server['online']);

                        $serverChannels = (int) (
                            $server['channels']
                            ?? 0
                        );
                        ?>

                        <option
                            value="<?= e($serverAddress) ?>"
                        >
                            <?= e($serverAddress) ?>

                            <?php if ($serverOnline): ?>
                                — <?= $serverChannels ?> каналов
                            <?php else: ?>
                                — недоступен
                            <?php endif; ?>
                        </option>

                    <?php endforeach; ?>
                </select>


                <select
                    id="digital-distrib-select"
                    class="digital-server-filter__select"
                >
                    <option value="">
                        Все дистрибьюторы
                    </option>

                    <?php foreach (
                        $digitalDistributors
                        as $distributor
                    ): ?>

                        <?php
                        $distributor = trim(
                            (string) $distributor
                        );

                        if ($distributor === '') {
                            continue;
                        }
                        ?>

                        <option
                            value="<?= e($distributor) ?>"
                        >
                            <?= e($distributor) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label class="digital-expensive-filter">
                    <input
                        type="checkbox"
                        id="digital-expensive-checkbox"
                    >

                    <span>Дорогие</span>
                </label>


                <div class="digital-filter-total">
                    <span>
                        Сумма
                    </span>

                    <strong id="digital-visible-summ">
                        0 $
                    </strong>
                </div>

            </div>

        <?php endif; ?>

        <?php if (!$digitalRows): ?>

            <div class="empty-state">

                <strong>
                    Каналы не найдены
                </strong>

                <span>
                    Проверьте доступность Cesbo Astra
                    и настройки digital.astras.
                </span>

            </div>

        <?php else: ?>

            <div class="digital-channel-grid">

                <?php
                $digitalNumber = 0;

                foreach ($digitalRows as $channel):

                    $digitalNumber++;

                    $name = trim(
                        (string) (
                            $channel['name']
                            ?? ''
                        )
                    );

                    $serviceName = trim(
                        (string) (
                            $channel['service_name']
                            ?? ''
                        )
                    );

                    $provider = trim(
                        (string) (
                            $channel['service_provider']
                            ?? ''
                        )
                    );

                    $astra = trim(
                        (string) (
                            $channel['astra']
                            ?? ''
                        )
                    );

                    $inputs = $channel['input'] ?? [];
                    $outputs = $channel['output'] ?? [];

                    if (!is_array($inputs)) {
                        $inputs = [];
                    }

                    if (!is_array($outputs)) {
                        $outputs = [];
                    }

                    $displayName =
                        $name !== ''
                            ? $name
                            : (
                                $serviceName !== ''
                                    ? $serviceName
                                    : 'Без названия'
                            );


                    $lcn = (int) (
                        $channel['lcn']
                        ?? 0
                    );

                    $distrib = trim(
                        (string) (
                            $channel['distrib']
                            ?? ''
                        )
                    );

                    $summ = (int) round(
                        (float) (
                            $channel['summ']
                            ?? 0
                        )
                    );

                    $info = trim(
                        (string) (
                            $channel['info']
                            ?? ''
                        )
                    );



                ?>

                    <article
                        class="digital-channel-card digital-channel-card--editable
                            <?= $lcn === 0
                                ? 'digital-channel-card--no-lcn'
                                : ''
                            ?>
                            <?= $distrib !== ''
                                ? 'digital-channel-card--has-distrib'
                                : ''
                            ?>
                            <?= $summ > 0
                                ? 'digital-channel-card--has-summ'
                                : ''
                            ?>                            
                        "
                        role="button"
                        tabindex="0"

                        data-digital-server="<?= e($astra) ?>"
                        data-digital-distrib="<?= e($distrib) ?>"
                        data-digital-summ="<?= e((string) $summ) ?>"
                        data-digital-order="<?= e((string) $digitalNumber) ?>"

                        data-digital-edit='<?= e(json_encode([
                            'id' => (int) ($channel['id'] ?? 0),
                            'name' => $displayName,
                            'server' => $astra,
                            'lcn' => $lcn,
                            'distrib' => $distrib,
                            'summ' => $summ,
                            'info' => $info,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                    >

                        <div class="digital-channel-card__head">

                            <div class="digital-channel-card__number">
                                <?= $lcn > 0
                                    ? e((string) $lcn)
                                    : '—'
                                ?>
                            </div>

                            <div
                                class="digital-channel-card__name"
                                title="<?= e($displayName) ?>"
                            >
                                <?= e($displayName) ?>
                            </div>

                        </div>

                        <?php if (
                            $distrib !== ''
                            || $summ > 0
                        ): ?>

                            <div class="digital-channel-card__commercial">

                                <?php if ($distrib !== ''): ?>
                                    <span>
                                        <?= e($distrib) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($summ > 0): ?>
                                    <strong>
                                        <?= e(number_format(
                                            $summ,
                                            0,
                                            ',',
                                            ' '
                                        )) ?> $
                                    </strong>
                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <?php if ($info !== ''): ?>

                            <div
                                class="digital-channel-card__info"
                                title="<?= e($info) ?>"
                            >
                                <?= nl2br(e($info)) ?>
                            </div>

                        <?php endif; ?>



                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>







<dialog
    class="modal"
    id="digital-edit-modal"
>
    <form method="post">

        <div class="modal-head">
            <div>
                <h2 id="digital-edit-title">
                    Телеканал
                </h2>

                <div
                    class="muted"
                    id="digital-edit-server"
                ></div>
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

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="id"
            id="digital-edit-id"
            value=""
        >

        <label>
            LCN

            <input
                class="input"
                type="number"
                name="lcn"
                id="digital-edit-lcn"
                min="0"
                max="999"
                step="1"
                value="0"
            >
        </label>

        <label>
            Дистрибьютор

            <select
                class="input select"
                name="distrib"
                id="digital-edit-distrib"
            >
                <option value="">
                    Не указан
                </option>

                <?php foreach (
                    $digitalDistributors
                    as $distributor
                ): ?>

                    <?php
                    $distributor = trim(
                        (string) $distributor
                    );

                    if ($distributor === '') {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= e($distributor) ?>"
                    >
                        <?= e($distributor) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Оплата в месяц, $
            <input
                class="input"
                type="number"
                name="summ"
                id="digital-edit-summ"
                min="0"
                step="1"
                value="0"
            >
        </label>

        <label>
            Дополнительная информация

            <textarea
                class="input"
                name="info"
                id="digital-edit-info"
                rows="4"
                maxlength="100"
                placeholder="До 100 символов"
            ></textarea>
        </label>

        <div
            class="modal-actions digital-modal-actions"
        >
            <button
                type="submit"
                class="button danger"
                name="action"
                value="digital_delete"
                formnovalidate
                onclick="return confirm(
                    'Удалить этот телеканал из базы данных?'
                )"
            >
                Удалить
            </button>

            <div class="digital-modal-actions__right">

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
                    name="action"
                    value="digital_save"
                >
                    Сохранить
                </button>

            </div>
        </div>

    </form>
</dialog>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const modal =
            document.getElementById(
                'digital-edit-modal'
            );

        if (!modal) {
            return;
        }

        const cards = document.querySelectorAll(
            '[data-digital-edit]'
        );

        const idInput =
            document.getElementById(
                'digital-edit-id'
            );

        const lcnInput =
            document.getElementById(
                'digital-edit-lcn'
            );

        const distribInput =
            document.getElementById(
                'digital-edit-distrib'
            );

        const summInput =
            document.getElementById(
                'digital-edit-summ'
            );

        const infoInput =
            document.getElementById(
                'digital-edit-info'
            );

        const title =
            document.getElementById(
                'digital-edit-title'
            );

        const server =
            document.getElementById(
                'digital-edit-server'
            );

        function openDigitalEdit(card) {
            let data;

            try {
                data = JSON.parse(
                    card.dataset.digitalEdit
                    || '{}'
                );
            } catch (error) {
                return;
            }

            idInput.value =
                data.id || '';

            lcnInput.value =
                data.lcn || 0;

            distribInput.value =
                data.distrib || '';

            summInput.value =
                data.summ || '0';

            infoInput.value =
                data.info || '';

            title.textContent =
                data.name || 'Телеканал';

            server.textContent =
                data.server || '';

            modal.showModal();
        }

        cards.forEach(function (card) {
            card.addEventListener(
                'click',
                function () {
                    openDigitalEdit(card);
                }
            );

            card.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key !== 'Enter'
                        && event.key !== ' '
                    ) {
                        return;
                    }

                    event.preventDefault();

                    openDigitalEdit(card);
                }
            );
        });
    }
);
</script>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const serverSelect =
            document.getElementById(
                'digital-server-select'
            );

        const distribSelect =
            document.getElementById(
                'digital-distrib-select'
            );

        const expensiveCheckbox =
            document.getElementById(
                'digital-expensive-checkbox'
            );

        const counter =
            document.getElementById(
                'digital-channel-counter'
            );

        const summElement =
            document.getElementById(
                'digital-visible-summ'
            );

        const cards = Array.from(
            document.querySelectorAll(
                '.digital-channel-card'
            )
        );

        const grid =
            document.querySelector(
                '.digital-channel-grid'
            );

        function updateDigitalChannels() {
            const selectedServer =
                serverSelect
                    ? serverSelect.value.trim()
                    : '';

            const selectedDistrib =
                distribSelect
                    ? distribSelect.value.trim()
                    : '';

            let visibleCount = 0;
            let visibleSumm = 0;

            cards.forEach(function (card) {
                const server = (
                    card.dataset.digitalServer
                    || ''
                ).trim();

                const distrib = (
                    card.dataset.digitalDistrib
                    || ''
                ).trim();

                const summ = parseFloat(
                    card.dataset.digitalSumm
                    || '0'
                ) || 0;

                const serverVisible =
                    selectedServer === ''
                    || server === selectedServer;

                const distribVisible =
                    selectedDistrib === ''
                    || distrib === selectedDistrib;

                const visible =
                    serverVisible
                    && distribVisible;

                card.style.display =
                    visible
                        ? ''
                        : 'none';

                if (!visible) {
                    return;
                }

                visibleCount++;
                visibleSumm += summ;
            });

            if (grid) {
                const sortedCards = [...cards];

                if (
                    expensiveCheckbox
                    && expensiveCheckbox.checked
                ) {
                    sortedCards.sort(function (a, b) {
                        const summA = parseFloat(
                            a.dataset.digitalSumm || '0'
                        ) || 0;

                        const summB = parseFloat(
                            b.dataset.digitalSumm || '0'
                        ) || 0;

                        return summB - summA;
                    });
                } else {
                    sortedCards.sort(function (a, b) {
                        return (
                            parseInt(
                                a.dataset.digitalOrder || '0',
                                10
                            )
                            -
                            parseInt(
                                b.dataset.digitalOrder || '0',
                                10
                            )
                        );
                    });
                }

                sortedCards.forEach(function (card) {
                    grid.appendChild(card);
                });
            }            

            if (counter) {
                counter.textContent =
                    new Intl.NumberFormat(
                        'ru-RU'
                    ).format(visibleCount)
                    + ' каналов';
            }

            if (summElement) {
                summElement.textContent =
                    new Intl.NumberFormat(
                        'ru-RU',
                        {
                            maximumFractionDigits: 0
                        }
                    ).format(visibleSumm)
                    + ' $';
            }
        }

        if (serverSelect) {
            serverSelect.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        if (distribSelect) {
            distribSelect.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        if (expensiveCheckbox) {
            expensiveCheckbox.addEventListener(
                'change',
                updateDigitalChannels
            );
        }

        updateDigitalChannels();
    }
);
</script>


            

