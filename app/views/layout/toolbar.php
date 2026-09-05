<?php
declare(strict_types=1);

/** @var string $module */
/** @var string $action */
/** @var string $search */
/** @var bool $withoutCharges */
/** @var bool $withoutPayments */


if (
            in_array(
                $module,
                [
                    'zayavki',
                    'podkluchki',
                    'otkluchki',
                    'database',
                    'sms',
                ],
                true
            )
            && !(
                $module === 'database'
                && $action === 'history'
            )
        ): ?>


            <section class="toolbar">
  <form class="search-form" method="get">
    <input
        type="hidden"
        name="module"
        value="<?= e($module) ?>"
    >

    <?php if (
        $module === 'database'
        && $withoutCharges
    ): ?>
        <input
            type="hidden"
            name="without_charges"
            value="1"
        >
    <?php endif; ?>

    <?php if (
        $module === 'database'
        && $withoutPayments
    ): ?>
        <input
            type="hidden"
            name="without_payments"
            value="1"
        >
    <?php endif; ?>

    <input
        class="input"
        type="search"
        name="search"
        value="<?= e($search) ?>"
        placeholder="Поиск…"
        autocomplete="off"
    >

    <?php if (
        !in_array(
            $module,
            ['database', 'sms', 'otkluchki'],
            true
        )
    ): ?>

        <select
            class="input select"
            id="status-filter"
            name="status"
        >
            <option value="all">
                Все
            </option>

            <option value="open" selected>
                Открытые
            </option>

            <option value="done">
                Выполненные
            </option>
        </select>

    <?php endif; ?>

    <button
        class="button"
        type="submit"
    >
        Найти
    </button>
</form>
<?php if ($module === 'sms'): ?>

    <button
        class="button primary"
        type="button"
        id="sms-verification-button"
    >
        Отправить код
    </button>

<?php elseif (
    in_array(
        $module,
        ['zayavki', 'podkluchki'],
        true
    )
): ?>

    <button
        class="button primary"
        type="button"
        data-modal-open="create-modal"
    >
        ＋ Добавить
    </button>

<?php elseif ($module === 'database'): ?>




<?php if (
    $module === 'database'
    && current_user() === 'admin'
): ?>

    <?php
    /*
     * Базовые параметры.
     */
    $databaseFilterParams = [
        'module' => 'database',
    ];

    if ($search !== '') {
        $databaseFilterParams['search'] = $search;
    }

    /*
     * Все.
     */
    $allUrl = url(
        $databaseFilterParams
    );

    /*
     * Без начислений.
     */
    $withoutChargesUrl = url(
        array_merge(
            $databaseFilterParams,
            [
                'without_charges' => '1',
            ]
        )
    );

    /*
     * Без оплаты.
     */
    $withoutPaymentsUrl = url(
        array_merge(
            $databaseFilterParams,
            [
                'without_payments' => '1',
            ]
        )
    );


    $exportParams = [
        'module' => 'database',
        'export' => '1',
    ];

    if ($search !== '') {
        $exportParams['search'] = $search;
    }

    if ($withoutCharges) {
        $exportParams[
            'without_charges'
        ] = '1';
    }

    if ($withoutPayments) {
        $exportParams[
            'without_payments'
        ] = '1';
    }

    $exportUrl = url(
        $exportParams
    );
    ?>


    <select
        class="input select"
        onchange="
            if (this.value) {
                showPageLoader();

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        window.location.href = this.value;
                    });
                });
            }
        "
    >
        <option
            value="<?= e($allUrl) ?>"
            <?= (
                !$withoutCharges
                && !$withoutPayments
            ) ? 'selected' : '' ?>
        >
            Все
        </option>

        <option
            value="<?= e($withoutChargesUrl) ?>"
            <?= $withoutCharges
                ? 'selected'
                : '' ?>
        >
            Без начислений
        </option>

        <option
            value="<?= e($withoutPaymentsUrl) ?>"
            <?= (
                !$withoutCharges
                && $withoutPayments
            ) ? 'selected' : '' ?>
        >
            Без оплаты
        </option>
    </select>


<a
    class="button"
    href="<?= e($exportUrl) ?>"
>
    ↓ Экспорт
</a>



<?php endif; ?>




                    <button
                        class="button primary"
                        type="button"
                        data-modal-open="database-import-modal"
                    >
                        ↑ Импорт
                    </button>

                <?php endif; ?>
            </section>
        <?php endif; ?>





