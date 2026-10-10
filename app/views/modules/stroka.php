<?php

declare(strict_types=1);

/** @var array $data */
/** @var string $search */
/** @var string $strokaStatus */
/** @var string $strokaDate */

$rows = $data['rows'] ?? [];

$now = time();

$statusItems = [
    'all' => 'Все',
    'active' => 'Активные',
    'scheduled' => 'Запланированные',
    'expired' => 'Завершённые',
    'deleted' => 'Выключенные',
];

$defaultStart = date(
    'Y-m-d',
    strtotime('+1 day')
);

$defaultEnd = date(
    'Y-m-d',
    strtotime('+4 days')
);

?>

<section class="stroka-page">

    <div class="page-heading">
        <div>
            <h1>Объявления</h1>

            <div class="page-heading__counter">
                <?= e(
                    number_format(
                        (int) ($data['total'] ?? 0),
                        0,
                        ',',
                        ' '
                    )
                ) ?>
                записей
            </div>
        </div>


        <form
            method="get"
            class="stroka-heading-date"
            id="stroka-heading-date-form"
        >
            <input
                type="hidden"
                name="module"
                value="stroka"
            >

            <?php if ($search !== ''): ?>
                <input
                    type="hidden"
                    name="search"
                    value="<?= e($search) ?>"
                >
            <?php endif; ?>

            <input
                type="hidden"
                name="stroka_status"
                id="stroka-heading-status"
                value="<?= e($strokaStatus) ?>"
            >

            <input
                type="date"
                class="input stroka-heading-date__input<?php
                    if (
                        isset($_GET['stroka_date'])
                        && $strokaDate !== date('Y-m-d')
                    ) {
                        echo ' stroka-heading-date__input--history';
                    }
                ?>"
                id="stroka-heading-date"
                name="stroka_date"
                value="<?= e($strokaDate) ?>"
                aria-label="Дата показа объявлений"
            >
        </form>


        <div class="page-heading__actions">

            <form
                method="post"
                onsubmit="return confirm(
                    'Перезапустить инфоканал?'
                )"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <button
                    type="submit"
                    class="button warning"
                    name="action"
                    value="stroka_reboot"
                >
                    Reboot
                </button>
            </form>

            <button
                type="button"
                class="button"
                id="stroka-stat-button"
                style="margin-right:20px;"
            >
                Статистика
            </button>

            <button
                type="button"
                class="button primary"
                id="stroka-create-button"
            >
                ＋ Добавить
            </button>

        </div>
    </div>

    <form
        method="get"
        class="stroka-filter-bar"
    >
        <input
            type="hidden"
            name="module"
            value="stroka"
        >

        <input
            type="hidden"
            id="stroka-filter-date"
            name="stroka_date"
            value="<?= e($strokaDate) ?>"
        >


        <input
            class="input"
            type="search"
            name="search"
            value="<?= e($search) ?>"
            placeholder="Поиск..."
            autocomplete="off"
        >

        <select
            class="input"
            id="stroka-status"
            name="stroka_status"
        >
            <?php foreach (
                $statusItems
                as $statusKey => $statusLabel
            ): ?>
                <option
                    value="<?= e($statusKey) ?>"
                    <?= $strokaStatus === $statusKey
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= e($statusLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button
            type="submit"
            class="button"
        >
            Найти
        </button>
    </form>

    <?php if (!$rows): ?>

        <div class="empty-state">
            <strong>
                Строки не найдены
            </strong>

            <span>
                Для выбранного фильтра записей нет.
            </span>
        </div>

    <?php else: ?>

        <div class="subscriber-list">

            <?php foreach ($rows as $row): ?>

                <?php

                $id = (int) ($row['id'] ?? 0);

                $dateStart = (int) (
                    $row['datestart']
                    ?? 0
                );

                $dateEnd = (int) (
                    $row['dateend']
                    ?? 0
                );

                $deleted = (int) (
                    $row['delete']
                    ?? 0
                ) === 1;

                $deleted = (int) (
                    $row['delete']
                    ?? 0
                ) === 1;

                if ($dateStart > $now) {
                    $rowStatus = 'scheduled';
                    $rowStatusLabel = 'Запланирована';
                } elseif ($dateEnd < $now) {
                    $rowStatus = 'expired';
                    $rowStatusLabel = 'Завершена';
                } else {
                    $rowStatus = 'active';
                    $rowStatusLabel = 'Активна';
                }

                $editData = [
                    'id' => $id,
                    'name' => (string) (
                        $row['name']
                        ?? ''
                    ),
                    'address' => (string) (
                        $row['address']
                        ?? ''
                    ),
                    'phone' => (string) (
                        $row['phone']
                        ?? ''
                    ),
                    'text' => (string) (
                        $row['text']
                        ?? ''
                    ),
                    'amount' => (string) (
                        $row['amount']
                        ?? ''
                    ),
                    'date' => (string) (
                        $row['date']
                        ?? ''
                    ),

                    'datestart' =>
                        $dateStart > 0
                            ? date(
                                'Y-m-d',
                                $dateStart
                            )
                            : '',

                    'dateend' =>
                        $dateEnd > 0
                            ? date(
                                'Y-m-d',
                                $dateEnd
                            )
                            : '',

                    'beznal' => (int) (
                        $row['beznal']
                        ?? 0
                    ),

                    'mystr' => (int) (
                        $row['mystr']
                        ?? 0
                    ),

                    'sh_tv' => (int) (
                        $row['sh_tv']
                        ?? 0
                    ),

                    'sh_int' => (int) (
                        $row['sh_int']
                        ?? 0
                    ),

                    'sh_pan' => (int) (
                        $row['sh_pan']
                        ?? 0
                    ),

                    'deleted' => $deleted,
                ];

                ?>

                <article
                    class="subscriber-card stroka-card<?= $rowStatusLabel !== 'Активна' ? ' stroka-card--inactive' : ' stroka-card--active' ?>
                    <?= !empty($row['beznal']) ? ' stroka-card--beznal' : '' ?>
                    <?= !empty($row['mystr']) ? ' stroka-card--mystr' : '' ?>"
                >
                    <div class="stroka-card__text-block">

                        <?php
                            $text = (string) ($row['text'] ?? '');

                            if (mb_strlen($text) > 60) {
                                $text = mb_substr($text, 0, 60) . '...';
                            }
                        ?>

                        <div
                            class="stroka-text"
                            role="button"
                            tabindex="0"
                            data-stroka-edit='<?= e(
                                json_encode(
                                    $editData,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                )
                            ) ?>'
                        >
                            <?= nl2br(e($text)) ?>
                        </div>

                        
                        <div class="muted">
                            <?= e(
                                (string) (
                                    $row['name']
                                    ?? ''
                                )
                            ) ?>

                            <?php if (
                                trim(
                                    (string) (
                                        $row['address']
                                        ?? ''
                                    )
                                ) !== ''
                            ): ?>
                                ·
                                <?= e(
                                    (string) $row['address']
                                ) ?>
                            <?php endif; ?>

                            <?php if (!empty($row['date'])): ?>
                                <strong>
                                    от <?= e((string) $row['date']) ?>
                                </strong>
                            <?php endif; ?>
                        </div>

                    </div>


                    <div class="stroka-card__sum">
                        <span class="label">
                            Сумма
                        </span>

                        <?= e(
                            (string) (
                                $row['amount']
                                ?: '—'
                            )
                        ) ?>
                    </div>


                    <div>
                        <span class="label">
                            Период
                        </span>

                        <?= $dateStart > 0
                            ? e(
                                date(
                                    'd.m.Y',
                                    $dateStart
                                )
                            )
                            : '—'
                        ?>

                        —

                        <?= $dateEnd > 0
                            ? e(
                                date(
                                    'd.m.Y',
                                    $dateEnd
                                )
                            )
                            : '—'
                        ?>
                    </div>                    

                    <div class="stroka-destinations">

                        <span
                            title="Инфоканал"
                            class="<?= !empty($row['sh_tv'])
                                ? 'active'
                                : ''
                            ?>"
                        >
                            TV
                        </span>

                        <span
                            title="Интернет"
                            class="<?= !empty($row['sh_int'])
                                ? 'active'
                                : ''
                            ?>"
                        >
                            WEB
                        </span>

                        <span
                            title="Панель"
                            class="<?= !empty($row['sh_pan'])
                                ? 'active'
                                : ''
                            ?>"
                        >
                            LED
                        </span>

                        <span
                            title="Telegram"
                            class="<?= !empty($row['telegram'])
                                ? 'active'
                                : ''
                            ?>"
                        >
                            TG
                        </span>

                    </div>


                    <?php if (
                        $rowStatus === 'active'
                        || $rowStatus === 'scheduled'
                    ): ?>

                        <form method="post" class="stroka-card__toggle">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $id ?>"
                            >

                            <button
                                type="submit"
                                class="button <?= $deleted ? 'primary' : 'danger' ?>"
                                name="action"
                                value="<?= $deleted
                                    ? 'stroka_restore'
                                    : 'stroka_delete'
                                ?>"
                            >
                                <?= $deleted ? 'ВКЛ' : 'ОТКЛ' ?>
                            </button>
                        </form>

                    <?php endif; ?>



                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<dialog
    class="modal"
    id="stroka-edit-modal"
>
    <form method="post">

        <div class="modal-head">
            <div>
                <h2 id="stroka-modal-title">
                    Новое объявление
                </h2>
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
            id="stroka-id"
            value=""
        >

        <div class="stroka-checkboxes">
            <label>
                <input
                    type="checkbox"
                    name="mystr"
                    id="stroka-mystr"
                    value="1"
                >
                Наше объявление
            </label>

            <label>
                <input
                    type="checkbox"
                    name="beznal"
                    id="stroka-beznal"
                    value="1"
                >
                Безнал
            </label>
        </div>

        <div class="stroka-form-grid">

            <label>
                ФИО

                <input
                    class="input"
                    type="text"
                    name="name"
                    id="stroka-name"
                    maxlength="250"
                >
            </label>

            <label>
                Телефон

                <input
                    class="input"
                    type="text"
                    name="phone"
                    id="stroka-phone"
                    maxlength="50"
                >
            </label>

        </div>

        <label>
            Адрес

            <input
                class="input"
                type="text"
                name="address"
                id="stroka-address"
                maxlength="250"
            >
        </label>

        <div class="stroka-form-grid">

            <label>
                Дата заявления

                <input
                    class="input"
                    type="date"
                    name="date"
                    id="stroka-date"
                    value="<?= e(date('Y-m-d')) ?>"
                >
            </label>

            <label>
                Сумма

                <input
                    class="input"
                    type="text"
                    name="amount"
                    id="stroka-amount"
                    inputmode="decimal"
                    pattern="^\d+(\.\d{1,2})?$"
                    maxlength="7"
                >
            </label>

        </div>

        <div class="stroka-form-grid">

            <label>
                Показывать с 00:00

                <input
                    class="input"
                    type="date"
                    name="datestart"
                    id="stroka-datestart"
                    value="<?= e($defaultStart) ?>"
                    required
                >
            </label>

            <label>
                Показывать до 23:59

                <input
                    class="input"
                    type="date"
                    name="dateend"
                    id="stroka-dateend"
                    value="<?= e($defaultEnd) ?>"
                    required
                >
            </label>

        </div>

        <label>
            Текст

            <textarea
                class="input"
                name="text"
                id="stroka-text"
                rows="7"
                required
            ></textarea>
        </label>

        <div
            class="muted"
            id="stroka-text-counter"
        >
            0 символов
        </div>

        <div class="stroka-checkboxes">

            <label>
                <input
                    type="checkbox"
                    name="sh_tv"
                    id="stroka-sh-tv"
                    value="1"
                    checked
                >
                Инфоканал
            </label>

            <label>
                <input
                    type="checkbox"
                    name="sh_int"
                    id="stroka-sh-int"
                    value="1"
                    checked
                >
                Интернет
            </label>

            <label>
                <input
                    type="checkbox"
                    name="sh_pan"
                    id="stroka-sh-pan"
                    value="1"
                >
                LED-панель
            </label>

        </div>

        <div class="modal-actions">

            <div class="stroka-modal-right">

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
                    id="stroka-save-button"
                    value="stroka_create"
                >
                    Сохранить
                </button>

            </div>

        </div>

    </form>
</dialog>




<dialog
    class="modal stroka-stat-modal"
    id="stroka-stat-modal"
>
    <div class="modal-head">
        <div>
            <h2>
                Статистика объявлений
            </h2>
        </div>

        <button
            type="button"
            class="icon-button"
            data-stat-close
            aria-label="Закрыть"
        >
            ×
        </button>
    </div>

    <div class="stroka-stat">

        <div class="stroka-stat__summary">

            <div class="stroka-stat-card">
                <span>
                    Активных / Запланированных
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $strokaStatistics['active']
                            ?? 0
                        ),
                        0,
                        ',',
                        ' '
                    ) ?> /
                    <?= number_format(
                        (int) (
                            $strokaStatistics['scheduled']
                            ?? 0
                        ),
                        0,
                        ',',
                        ' '
                    ) ?>
                </strong>
            </div>

            <div class="stroka-stat-card">
                <span>
                    За
                    <?= (int) (
                        $strokaStatistics[
                            'previous_year'
                        ]['year']
                        ?? date('Y') - 1
                    ) ?>
                    год
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $strokaStatistics[
                                'previous_year'
                            ]['amount']
                            ?? 0
                        ),
                        0,
                        ',',
                        ' '
                    ) ?>
                    руб.
                </strong>
            </div>

            <div class="stroka-stat-card">
                <span>
                    За
                    <?= (int) (
                        $strokaStatistics[
                            'current_year'
                        ]['year']
                        ?? date('Y')
                    ) ?>
                    год
                </span>

                <strong>
                    <?= number_format(
                        (int) (
                            $strokaStatistics[
                                'current_year'
                            ]['amount']
                            ?? 0
                        ),
                        0,
                        ',',
                        ' '
                    ) ?>
                    руб.
                </strong>
            </div>


        </div>



        <div class="stroka-stat-chart">
            <div class="stroka-stat-chart__head">
                <h3>
                    Суммы за последние 1.5 года
                </h3>

                <div class="stroka-stat-chart__legend">
                    <span>
                        <i class="total"></i>
                        Всего
                    </span>

                    <span>
                        <i class="individual"></i>
                        Физлица
                    </span>

                    <span>
                        <i class="legal"></i>
                        Юрлица
                    </span>
                </div>
            </div>

            <div class="stroka-stat-chart__canvas">
                <canvas
                    id="stroka-stat-chart"
                    height="240"
                ></canvas>
            </div>
        </div>



        <div class="stroka-stat__periods">

            <section class="stroka-stat-period">

                <h3>
                    Прошлый месяц
                </h3>

                <dl>
                    <div>
                        <dt>
                            Объявлений
                        </dt>

                        <dd>
                            <?= (int) (
                                $strokaStatistics[
                                    'last_month'
                                ]['count']
                                ?? 0
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Физлица
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'last_month'
                                    ]['individual']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Юрлица
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'last_month'
                                    ]['legal']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>

                    <div class="total">
                        <dt>
                            Всего
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'last_month'
                                    ]['total']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>
                </dl>

            </section>

            <section class="stroka-stat-period">

                <h3>
                    Текущий месяц
                </h3>

                <dl>
                    <div>
                        <dt>
                            Объявлений
                        </dt>

                        <dd>
                            <?= (int) (
                                $strokaStatistics[
                                    'current_month'
                                ]['count']
                                ?? 0
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Физлица
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'current_month'
                                    ]['individual']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Юрлица
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'current_month'
                                    ]['legal']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>

                    <div class="total">
                        <dt>
                            Всего
                        </dt>

                        <dd>
                            <?= number_format(
                                (int) (
                                    $strokaStatistics[
                                        'current_month'
                                    ]['total']
                                    ?? 0
                                ),
                                0,
                                ',',
                                ' '
                            ) ?>
                            руб.
                        </dd>
                    </div>
                </dl>

            </section>

        </div>

    </div>

    <div class="modal-actions">
        <button
            type="button"
            class="button"
            data-stat-close
        >
            Закрыть
        </button>
    </div>
</dialog>




<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {








        const headingDateForm =
            document.getElementById(
                'stroka-heading-date-form'
            );

        const headingDate =
            document.getElementById(
                'stroka-heading-date'
            );

        const headingStatus =
            document.getElementById(
                'stroka-heading-status'
            );

        if (
            headingDateForm
            && headingDate
            && headingStatus
        ) {
            headingDate.addEventListener(
                'change',
                function () {
                    headingStatus.value = 'all';
                    headingDateForm.submit();
                }
            );
        }






        const statusSelect =
            document.getElementById(
                'stroka-status'
            );

        const filterDate =
            document.getElementById(
                'stroka-filter-date'
            );

        if (
            statusSelect
            && filterDate
        ) {
            statusSelect.addEventListener(
                'change',
                function () {
                    if (
                        statusSelect.value !== 'all'
                    ) {
                        filterDate.value =
                            '<?= e(date('Y-m-d')) ?>';
                    }

                    statusSelect.form.submit();
                }
            );
        }





        const statModal =
            document.getElementById(
                'stroka-stat-modal'
            );

        const statButton =
            document.getElementById(
                'stroka-stat-button'
            );



        const statChartData = <?= json_encode(
            $strokaStatistics['year_chart'] ?? [],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        ) ?>;

        let statChartDrawn = false;

        function drawStatChart() {
            const canvas =
                document.getElementById(
                    'stroka-stat-chart'
                );

            if (
                !canvas
                || !statChartData.length
            ) {
                return;
            }

            const container =
                canvas.parentElement;

            const dpr =
                window.devicePixelRatio || 1;

            const width =
                container.clientWidth;

            const height = 240;

            canvas.width =
                Math.round(width * dpr);

            canvas.height =
                Math.round(height * dpr);

            canvas.style.width =
                width + 'px';

            canvas.style.height =
                height + 'px';

            const ctx =
                canvas.getContext('2d');

            ctx.setTransform(
                dpr,
                0,
                0,
                dpr,
                0,
                0
            );

            ctx.clearRect(
                0,
                0,
                width,
                height
            );

            const padding = {
                top: 16,
                right: 18,
                bottom: 38,
                left: 38
            };

            const chartWidth =
                width
                - padding.left
                - padding.right;

            const chartHeight =
                height
                - padding.top
                - padding.bottom;

            const maxValue = Math.max(
                1,
                ...statChartData.map(
                    item => Number(item.total) || 0
                )
            );

            const stepCount = 4;

            ctx.font =
                '12px Inter, system-ui, sans-serif';

            ctx.textBaseline =
                'middle';

            ctx.strokeStyle =
                '#e2e8f0';

            ctx.fillStyle =
                '#64748b';

            ctx.lineWidth = 1;

            for (
                let i = 0;
                i <= stepCount;
                i++
            ) {
                const y =
                    padding.top
                    + chartHeight
                    * i
                    / stepCount;

                const value =
                    Math.round(
                        maxValue
                        * (
                            1
                            - i / stepCount
                        )
                    );

                ctx.beginPath();

                ctx.moveTo(
                    padding.left,
                    y
                );

                ctx.lineTo(
                    width - padding.right,
                    y
                );

                ctx.stroke();

                ctx.textAlign = 'right';

                ctx.fillText(
                    new Intl.NumberFormat(
                        'ru-RU',
                        {
                            maximumFractionDigits: 0
                        }
                    ).format(value),
                    padding.left - 8,
                    y
                );
            }

            const getX = index => {
                if (statChartData.length === 1) {
                    return (
                        padding.left
                        + chartWidth / 2
                    );
                }

                return (
                    padding.left
                    + chartWidth
                    * index
                    / (
                        statChartData.length
                        - 1
                    )
                );
            };

            const getY = value => (
                padding.top
                + chartHeight
                - (
                    Number(value)
                    / maxValue
                    * chartHeight
                )
            );

            statChartData.forEach(
                function (item, index) {
                    const x = getX(index);

                    ctx.fillStyle =
                        '#64748b';

                    ctx.textAlign =
                        'center';

                    ctx.textBaseline =
                        'top';

                    let label =
                        item.label;

                    if (
                        Number(item.month) === 1
                    ) {
                        label +=
                            ' '
                            + String(item.year)
                                .slice(-2);
                    }

                    ctx.fillText(
                        label,
                        x,
                        height
                        - padding.bottom
                        + 12
                    );
                }
            );

            const series = [
                {
                    key: 'total',
                    color: '#2563eb',
                    width: 3
                },
                {
                    key: 'individual',
                    color: '#16a34a',
                    width: 2
                },
                {
                    key: 'legal',
                    color: '#dc2626',
                    width: 2
                }
            ];

            series.forEach(
                function (line) {
                    ctx.beginPath();

                    ctx.strokeStyle =
                        line.color;

                    ctx.lineWidth =
                        line.width;

                    ctx.lineJoin =
                        'round';

                    ctx.lineCap =
                        'round';

                    statChartData.forEach(
                        function (
                            item,
                            index
                        ) {
                            const x =
                                getX(index);

                            const y =
                                getY(
                                    item[line.key]
                                );

                            if (index === 0) {
                                ctx.moveTo(
                                    x,
                                    y
                                );
                            } else {
                                ctx.lineTo(
                                    x,
                                    y
                                );
                            }
                        }
                    );

                    ctx.stroke();

                    statChartData.forEach(
                        function (
                            item,
                            index
                        ) {
                            const x =
                                getX(index);

                            const y =
                                getY(
                                    item[line.key]
                                );

                            ctx.beginPath();

                            ctx.fillStyle =
                                line.color;

                            ctx.arc(
                                x,
                                y,
                                3,
                                0,
                                Math.PI * 2
                            );

                            ctx.fill();
                        }
                    );
                }
            );
        }








        if (
            statModal
            && statButton
        ) {
            statButton.addEventListener(
                'click',
                function () {
                    statModal.showModal();

                    requestAnimationFrame(
                        function () {
                            drawStatChart();
                            statChartDrawn = true;
                        }
                    );
                }
            );

            statModal
                .querySelectorAll(
                    '[data-stat-close]'
                )
                .forEach(function (button) {
                    button.addEventListener(
                        'click',
                        function () {
                            statModal.close();
                        }
                    );
                });
        }



        window.addEventListener(
            'resize',
            function () {
                if (
                    statChartDrawn
                    && statModal.open
                ) {
                    drawStatChart();
                }
            }
        );



        const modal =
            document.getElementById(
                'stroka-edit-modal'
            );

        if (!modal) {
            return;
        }

        const createButton =
            document.getElementById(
                'stroka-create-button'
            );

        const fields = {
            id: document.getElementById('stroka-id'),
            name: document.getElementById('stroka-name'),
            address: document.getElementById('stroka-address'),
            phone: document.getElementById('stroka-phone'),
            text: document.getElementById('stroka-text'),
            amount: document.getElementById('stroka-amount'),
            date: document.getElementById('stroka-date'),
            datestart: document.getElementById('stroka-datestart'),
            dateend: document.getElementById('stroka-dateend'),
            beznal: document.getElementById('stroka-beznal'),
            mystr: document.getElementById('stroka-mystr'),
            sh_tv: document.getElementById('stroka-sh-tv'),
            sh_int: document.getElementById('stroka-sh-int'),
            sh_pan: document.getElementById('stroka-sh-pan')
        };

        function updateDateLimits() {
            if (
                fields.datestart.value
                && fields.dateend.value
            ) {
                fields.dateend.min =
                    fields.datestart.value;

                fields.datestart.max =
                    fields.dateend.value;
            }
        }



        fields.datestart.addEventListener(
            'change',
            function () {
                if (
                    fields.dateend.value
                    && fields.datestart.value > fields.dateend.value
                ) {
                    fields.dateend.value =
                        fields.datestart.value;
                }

                updateDateLimits();
            }
        );

        fields.dateend.addEventListener(
            'change',
            function () {
                if (
                    fields.datestart.value
                    && fields.dateend.value < fields.datestart.value
                ) {
                    fields.datestart.value =
                        fields.dateend.value;
                }

                updateDateLimits();
            }
        );



        const title =
            document.getElementById(
                'stroka-modal-title'
            );

        const saveButton =
            document.getElementById(
                'stroka-save-button'
            );


        const counter =
            document.getElementById(
                'stroka-text-counter'
            );

        function updateCounter() {
            const length =
                fields.text.value.length;

            const amountValue =
                fields.amount.value.trim();

            const amount =
                parseFloat(
                    amountValue.replace(',', '.')
                );

            const noAmount =
                amountValue === ''
                || Number.isNaN(amount)
                || amount === 0;

            const freeLongText =
                length >= 250
                && noAmount;

            const tooLongForLed =
                length > 500;

            counter.textContent =
                length + ' символов';

            fields.sh_pan.disabled =
                tooLongForLed
                || freeLongText;

            if (
                tooLongForLed
                || freeLongText
            ) {
                fields.sh_pan.checked = false;
            }

            if (tooLongForLed) {
                counter.textContent +=
                    ' — для LED максимум 500';
            } else if (freeLongText) {
                counter.textContent +=
                    ' — бесплатная поэма недоступна для LED';
            }
        }

        function resetForm() {
            fields.id.value = '';
            fields.name.value = '';
            fields.address.value = '';
            fields.phone.value = '';
            fields.text.value = '';
            fields.amount.value = '';

            fields.date.value =
                '<?= e(date('Y-m-d')) ?>';

            fields.datestart.value =
                '<?= e($defaultStart) ?>';

            fields.dateend.value =
                '<?= e($defaultEnd) ?>';

            fields.beznal.checked = false;
            fields.mystr.checked = false;
            fields.sh_tv.checked = true;
            fields.sh_int.checked = true;
            fields.sh_pan.checked = false;
            fields.sh_pan.disabled = false;

            title.textContent =
                'Новое объявление';

            saveButton.value =
                'stroka_create';

            saveButton.hidden = false;

            updateCounter();
            updateDateLimits();
        }

        function openEdit(card) {
            let data;

            try {
                data = JSON.parse(
                    card.dataset.strokaEdit
                    || '{}'
                );
            } catch (error) {
                return;
            }

            fields.id.value =
                data.id || '';

            fields.name.value =
                data.name || '';

            fields.address.value =
                data.address || '';

            fields.phone.value =
                data.phone || '';

            fields.text.value =
                data.text || '';

            fields.amount.value =
                data.amount || '';

            fields.datestart.value =
                data.datestart || '';

            fields.dateend.value =
                data.dateend || '';

            updateDateLimits();

            /*
             * Старая дата хранится как dd.mm.YYYY.
             */
            if (
                data.date
                && /^\d{2}\.\d{2}\.\d{4}$/.test(data.date)
            ) {
                const parts =
                    data.date.split('.');

                fields.date.value =
                    parts[2]
                    + '-'
                    + parts[1]
                    + '-'
                    + parts[0];
            } else {
                fields.date.value =
                    '<?= e(date('Y-m-d')) ?>';
            }

            fields.beznal.checked =
                Number(data.beznal) === 1;

            fields.mystr.checked =
                Number(data.mystr) === 1;

            fields.sh_tv.checked =
                Number(data.sh_tv) === 1;

            fields.sh_int.checked =
                Number(data.sh_int) === 1;

            fields.sh_pan.checked =
                Number(data.sh_pan) === 1;

            title.textContent =
                'Объявление #' + data.id;

            saveButton.value =
                'stroka_update';


            updateCounter();

            modal.showModal();
        }

        createButton.addEventListener(
            'click',
            function () {
                resetForm();
                modal.showModal();
            }
        );

        document
            .querySelectorAll(
                '[data-stroka-edit]'
            )
            .forEach(function (card) {
                card.addEventListener(
                    'click',
                    function () {
                        openEdit(card);
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
                        openEdit(card);
                    }
                );
            });


        fields.amount.addEventListener(
            'input',
            function () {
                let value = fields.amount.value;

                value = value.replace(',', '.');
                value = value.replace(/[^0-9.]/g, '');

                const firstDot = value.indexOf('.');

                if (firstDot !== -1) {
                    value =
                        value.slice(0, firstDot + 1)
                        + value
                            .slice(firstDot + 1)
                            .replace(/\./g, '');
                }

                const parts = value.split('.');

                if (parts.length === 2) {
                    value =
                        parts[0]
                        + '.'
                        + parts[1].slice(0, 2);
                }

                fields.amount.value = value;
                updateCounter();
            }
        );



        fields.mystr.addEventListener(
            'change',
            function () {
                if (!fields.mystr.checked) {
                    return;
                }

                fields.name.value =
                    'ооо трианда';

                fields.phone.value =
                    '6-83-88';

                fields.address.value =
                    'энергетиков 15';

                fields.amount.value =
                    '1.00';      
                    
                updateCounter();
            }
        );



        fields.text.addEventListener(
            'beforeinput',
            function (event) {
                const start =
                    fields.text.selectionStart ?? 0;

                const end =
                    fields.text.selectionEnd ?? start;

                if (
                    event.inputType === 'insertLineBreak'
                    || event.inputType === 'insertParagraph'
                ) {
                    event.preventDefault();

                    fields.text.setRangeText(
                        ' ',
                        start,
                        end,
                        'end'
                    );

                    updateCounter();

                    return;
                }

                if (
                    event.inputType !== 'insertText'
                    || event.data !== '"'
                ) {
                    return;
                }

                event.preventDefault();

                const before =
                    fields.text.value.slice(0, start);

                const openCount =
                    (before.match(/«/g) || []).length;

                const closeCount =
                    (before.match(/»/g) || []).length;

                const quote =
                    openCount > closeCount
                        ? '»'
                        : '«';

                fields.text.setRangeText(
                    quote,
                    start,
                    end,
                    'end'
                );

                updateCounter();
            }
        );

        fields.text.addEventListener(
            'input',
            updateCounter
        );



        resetForm();
    }
);



const strokaStatusSelect = document.getElementById('stroka-status');

if (strokaStatusSelect) {
    const storageKey = 'stroka_status';

    const params = new URLSearchParams(window.location.search);
    const statusFromUrl = params.get('stroka_status');

    if (statusFromUrl) {
        // Если статус уже пришёл через URL — запоминаем его.
        localStorage.setItem(storageKey, statusFromUrl);
    } else {
        // Если страница открыта без stroka_status —
        // восстанавливаем последний выбранный статус.
        const savedStatus = localStorage.getItem(storageKey);

        if (
            savedStatus &&
            strokaStatusSelect.querySelector(
                `option[value="${CSS.escape(savedStatus)}"]`
            )
        ) {
            strokaStatusSelect.value = savedStatus;
            strokaStatusSelect.form.submit();
        }
    }

    strokaStatusSelect.addEventListener('change', () => {
        localStorage.setItem(
            storageKey,
            strokaStatusSelect.value
        );
    });
}

</script>