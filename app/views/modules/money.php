<?php

declare(strict_types=1);

/** @var array $data */
/** @var string $moneyMonth */
/** @var string $moneyYear */
/** @var array $moneyMonths */
/** @var array $moneyAdsChartLabels */
/** @var array $moneyAdsChartValues */
/** @var float $moneyAdsChartTotal */
/** @var array $moneySubscribersChart */




$moneyRows = $data['rows'] ?? [];


/*
 * Поля таблицы.
 */
$moneyFields = [
    'pole1' =>
        'Договора<br>Г / А / Ц',

    'pole3' =>
        'Объявления<br>ВСЕ / ЛЬГ',

    'pole4' =>
        'Сумма объявлений',

    'pole5' =>
        'Расторжение<br>Г / А / Ц',

    'pole6' =>
        'Должн. расторж.<br>Г / А / Ц',

    'pole7' =>
        'Примечание',
];


/*
 * Последние 5 лет.
 *
 * Например:
 *
 * 2026
 * 2025
 * 2024
 * 2023
 * 2022
 */
$currentYear = (int) date('y');

$moneyYears = [];

for ($i = 0; $i <= 6; $i++) { // в меню показывает последние 7 лет
    $moneyYears[] = sprintf(
        '%02d',
        $currentYear - $i
    );
}


/*
 * Форматирование значения.
 */
$moneyFormat = static function (
    string $value
): string {
    $value = trim($value);

    if (
        is_numeric($value)
        && (float) $value >= 1000
    ) {
        return number_format(
            (float) $value,
            0,
            '.',
            ' '
        );
    }

    return $value;
};


/*
 * Подсчёт итогов
 * за выбранный месяц.
 */
$totals = [];

foreach ($moneyRows as $row) {
    foreach (
        array_keys($moneyFields)
        as $field
    ) {
        $value = trim(
            (string) (
                $row[$field]
                ?? ''
            )
        );

        if ($value === '') {
            continue;
        }

        /*
         * Значения вида:
         *
         * 1/2/3
         */
        if (str_contains($value, '/')) {
            $parts = explode(
                '/',
                $value
            );

            $current = isset(
                $totals[$field]
            )
                ? explode(
                    '/',
                    (string) $totals[$field]
                )
                : [];

            foreach (
                $parts
                as $index => $part
            ) {
                $partValue = is_numeric(
                    $part
                )
                    ? (float) $part
                    : 0.0;

                $currentValue = isset(
                    $current[$index]
                )
                && is_numeric(
                    $current[$index]
                )
                    ? (float) $current[$index]
                    : 0.0;

                $current[$index] =
                    $currentValue
                    + round(
                        $partValue,
                        2
                    );
            }

            $totals[$field] = implode(
                '/',
                $current
            );

            continue;
        }

        if (is_numeric($value)) {
            $totals[$field] =
                (float) (
                    $totals[$field]
                    ?? 0
                )
                + (float) $value;
        }
    }
}


/*
 * Данные графика "Объявления".
 */
$moneyAdsChartLabelsJson = json_encode(
    $moneyAdsChartLabels ?? [],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

$moneyAdsChartValuesJson = json_encode(
    $moneyAdsChartValues ?? [],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_NUMERIC_CHECK
);

$moneyAdsChartTotalJson = json_encode(
    round(
        (float) (
            $moneyAdsChartTotal
            ?? 0
        ),
        2
    ),
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_NUMERIC_CHECK
);


$moneySubscribersChartJson =
    json_encode(
        $moneySubscribersChart
        ?? [],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_NUMERIC_CHECK
    );

?>

<section class="money-page">


    <!--
        Верхняя панель.
    -->
    <div class="money-toolbar">


        <!--
            Выбор месяца и года.
        -->
        <form
            method="get"
            class="money-filter"
        >

            <input
                type="hidden"
                name="module"
                value="money"
            >


            <label>

                <span>
                    Месяц
                </span>

                <select
                    name="month"
                    onchange="this.form.submit()"
                >

                    <?php foreach (
                        $moneyMonths
                        as $month => $label
                    ): ?>

                        <option
                            value="<?= e($month) ?>"
                            <?= $moneyMonth === $month
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>


            <label>

                <span>
                    Год
                </span>

                <select
                    name="year"
                    onchange="this.form.submit()"
                >

                    <?php foreach (
                        $moneyYears
                        as $year
                    ): ?>

                        <option
                            value="<?= e($year) ?>"
                            <?= $moneyYear === $year
                                ? 'selected'
                                : '' ?>
                        >
                            20<?= e($year) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

        </form>


        <!--
            Кнопки графиков.
        -->
        <div class="money-chart-buttons">

            <button
                type="button"
                class="money-chart-button"
                data-money-chart="subscribers"
            >
                📈 Абоненты
            </button>

            <button
                type="button"
                class="money-chart-button"
                data-money-chart="ads"
            >
                📈 Объявления
            </button>

        </div>

    </div>


    <!--
        Валидная отдельная форма
        добавления записи.

        Input'ы находятся в таблице ниже,
        но привязаны к этой форме через
        form="money-add-form".
    -->
    <form
        id="money-add-form"
        method="post"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="money_add"
        >

    </form>


    <!--
        Таблица.
    -->
    <div class="money-table-wrap">

        <table class="money-table">

            <thead>

                <tr>

                    <th>
                        Дата
                    </th>

                    <?php foreach (
                        $moneyFields
                        as $label
                    ): ?>

                        <th>
                            <?= $label ?>
                        </th>

                    <?php endforeach; ?>

                </tr>

            </thead>


            <tbody>


                <!--
                    Существующие записи.
                -->
                <?php foreach (
                    $moneyRows
                    as $row
                ): ?>

                    <?php

                    $rowId = (int) (
                        $row['id']
                        ?? 0
                    );

                    ?>

                    <tr>

                        <td
                            class="money-editable"
                            data-id="<?= $rowId ?>"
                            data-field="date"
                            data-value="<?= e(
                                (string) (
                                    $row['date']
                                    ?? ''
                                )
                            ) ?>"
                        >
                            <?= e(
                                (string) (
                                    $row['date']
                                    ?? ''
                                )
                            ) ?>
                        </td>


                        <?php foreach (
                            array_keys(
                                $moneyFields
                            )
                            as $field
                        ): ?>

                            <?php

                            $value = trim(
                                (string) (
                                    $row[$field]
                                    ?? ''
                                )
                            );

                            ?>

                            <td
                                class="money-editable"
                                data-id="<?= $rowId ?>"
                                data-field="<?= e($field) ?>"
                                data-value="<?= e($value) ?>"
                            >
                                <?= e(
                                    $moneyFormat(
                                        $value
                                    )
                                ) ?>
                            </td>

                        <?php endforeach; ?>

                    </tr>

                <?php endforeach; ?>


                <!--
                    Новая запись.
                -->
                <tr class="money-add-row">

                    <td>

                        <input
                            class="money-input"
                            type="text"
                            name="date"
                            value="<?= e(
                                date('d.m.y')
                            ) ?>"
                            autocomplete="off"
                            form="money-add-form"
                        >

                    </td>


                    <?php foreach (
                        array_keys(
                            $moneyFields
                        )
                        as $field
                    ): ?>

                        <td>

                            <input
                                class="money-input"
                                type="text"
                                name="<?= e($field) ?>"
                                value=""
                                autocomplete="off"
                                form="money-add-form"
                            >

                        </td>

                    <?php endforeach; ?>

                </tr>


                <!--
                    Итоги.
                -->
                <tr class="money-total-row">

                    <td>
                        ИТОГО
                    </td>


                    <?php foreach (
                        array_keys(
                            $moneyFields
                        )
                        as $field
                    ): ?>

                        <td>

                            <?php

                            $totalValue =
                                (string) (
                                    $totals[$field]
                                    ?? ''
                                );

                            ?>

                            <?= e(
                                $moneyFormat(
                                    $totalValue
                                )
                            ) ?>

                        </td>

                    <?php endforeach; ?>

                </tr>

            </tbody>

        </table>

    </div>


    <!--
        Enter в любом поле
        строки добавления
        отправляет money-add-form.
    -->
    <button
        type="submit"
        form="money-add-form"
        hidden
    ></button>


    <!--
        Форма inline-редактирования.
    -->
    <form
        id="money-edit-form"
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
            value="money_edit"
        >

        <input
            type="hidden"
            name="id"
            id="money-edit-id"
        >

        <input
            type="hidden"
            name="field"
            id="money-edit-field"
        >

        <input
            type="hidden"
            name="value"
            id="money-edit-value"
        >

        <input
            type="hidden"
            name="month"
            value="<?= e($moneyMonth) ?>"
        >

        <input
            type="hidden"
            name="year"
            value="<?= e($moneyYear) ?>"
        >

    </form>


    <!--
        Модальное окно графиков.
    -->
    <dialog
        id="money-chart-modal"
        class="money-chart-modal"
    >

        <div class="money-chart-modal__header">

            <strong
                id="money-chart-modal-title"
                class="money-chart-modal__title"
            >
            </strong>

            <div class="money-chart-modal__actions">

                <button
                    type="button"
                    id="money-chart-save"
                    class="money-chart-modal__save"
                    title="Сохранить график в PNG"
                >
                    💾 PNG
                </button>

                <button
                    type="button"
                    id="money-chart-modal-close"
                    class="money-chart-modal__close"
                    aria-label="Закрыть"
                >
                    ×
                </button>

            </div>

        </div>


        <div
            id="money-chart-modal-content"
            class="money-chart-modal__content"
        >
        </div>

    </dialog>

</section>


<!--
    Chart.js для графиков.
-->
<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"
></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    () => {

        let activeCell = null;

        let moneyChartInstance = null;


        /*
         * Inline edit.
         */
        const editForm =
            document.getElementById(
                'money-edit-form'
            );

        const idInput =
            document.getElementById(
                'money-edit-id'
            );

        const fieldInput =
            document.getElementById(
                'money-edit-field'
            );

        const valueInput =
            document.getElementById(
                'money-edit-value'
            );


        /*
         * Модальное окно.
         */
        const chartModal =
            document.getElementById(
                'money-chart-modal'
            );

        const chartModalTitle =
            document.getElementById(
                'money-chart-modal-title'
            );

        const chartModalContent =
            document.getElementById(
                'money-chart-modal-content'
            );

        const chartModalClose =
            document.getElementById(
                'money-chart-modal-close'
            );

        const chartSaveButton =
            document.getElementById(
                'money-chart-save'
            );


        /*
         * Данные графика объявлений.
         */
        const adsChartLabels =
            <?= $moneyAdsChartLabelsJson ?: '[]' ?>;

        const adsChartValues =
            <?= $moneyAdsChartValuesJson ?: '[]' ?>;

        const adsChartTotal =
            <?= $moneyAdsChartTotalJson ?: '0' ?>;

        /*
        * История количества абонентов.
        */
        const subscribersChartData =
            <?= $moneySubscribersChartJson ?: '[]' ?>;            


        /*
         * Форматирование суммы.
         */
        function formatMoney(
            value
        ) {
            return new Intl.NumberFormat(
                'ru-RU',
                {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }
            ).format(
                Number(value) || 0
            );
        }

        function formatInteger(
            value
        ) {
            return new Intl.NumberFormat(
                'ru-RU',
                {
                    maximumFractionDigits: 0,
                }
            ).format(
                Number(value) || 0
            );
        }


        function buildAdsYearSummary() {
            const totals = {};

            adsChartLabels.forEach(
                (label, index) => {
                    const parts =
                        String(label).split('.');

                    if (parts.length !== 2) {
                        return;
                    }

                    const year = parts[1];

                    if (!totals[year]) {
                        totals[year] = 0;
                    }

                    totals[year] +=
                        Number(
                            adsChartValues[index]
                            || 0
                        );
                }
            );

            return Object
                .keys(totals)
                .sort(
                    (a, b) =>
                        Number(a)
                        - Number(b)
                )
                .map(
                    (year) => {
                        return (
                            '<div class="money-chart-year-total">'
                            + '<span class="money-chart-year">'
                            + year
                            + ':</span>'
                            + '<strong>'
                            + formatMoney(
                                totals[year]
                            )
                            + '</strong>'
                            + '</div>'
                        );
                    }
                )
                .join('');
        }



        /*
         * Удаление текущего графика.
         */
        function destroyMoneyChart() {

            if (!moneyChartInstance) {
                return;
            }

            moneyChartInstance.destroy();

            moneyChartInstance = null;
        }


        /*
         * Абоненты.
         *
         * Пока график не строим.
         */
        function openSubscribersChart() {

            destroyMoneyChart();

            chartModalTitle.textContent =
                'Абоненты';


            if (
                !Array.isArray(
                    subscribersChartData
                )
                || subscribersChartData.length === 0
            ) {
                chartModalContent.innerHTML =
                    '<div class="money-chart-placeholder">'
                    + 'Нет данных для построения графика.'
                    + '</div>';

                chartModal.showModal();

                return;
            }


            /*
            * Последняя точка —
            * текущее расчётное количество.
            */
            const last =
                subscribersChartData[
                    subscribersChartData.length - 1
                ];


            chartModalContent.innerHTML =
                '<div class="money-chart-canvas-wrap">'
                + '<canvas id="money-subscribers-chart"></canvas>'
                + '</div>';


            chartModal.showModal();


            const canvas =
                document.getElementById(
                    'money-subscribers-chart'
                );


            if (
                !canvas
                || typeof Chart === 'undefined'
            ) {
                chartModalContent.innerHTML =
                    '<div class="money-chart-placeholder">'
                    + 'Не удалось загрузить библиотеку графиков.'
                    + '</div>';

                return;
            }


            const labels =
                subscribersChartData.map(
                    (row) => row.month
                );


            moneyChartInstance =
                new Chart(
                    canvas,
                    {
                        type: 'line',

                        data: {
                            labels,

                        datasets: [
                            {
                                label:
                                    'Госканалы ('
                                    + formatInteger(last.g)
                                    + ')',

                                data:
                                    subscribersChartData.map(
                                        (row) =>
                                            row.g
                                    ),

                                borderColor: '#000000',
                                backgroundColor: '#000000',

                                borderWidth: 2,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                tension: 0.2,
                                fill: false,
                            },
                            {
                                label:
                                    'Аналоговый пакет ('
                                    + formatInteger(last.a)
                                    + ')',

                                data:
                                    subscribersChartData.map(
                                        (row) =>
                                            row.a
                                    ),

                                borderColor: '#0066ff',
                                backgroundColor: '#0066ff',

                                borderWidth: 2,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                tension: 0.2,
                                fill: false,
                            },

                            {
                                label:
                                    'Цифровой пакет ('
                                    + formatInteger(last.c)
                                    + ')',

                                data:
                                    subscribersChartData.map(
                                        (row) =>
                                            row.c
                                    ),

                                borderColor: '#00a000',
                                backgroundColor: '#00a000',

                                borderWidth: 2,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                tension: 0.2,
                                fill: false,
                            },

                            {
                                label:
                                    'Общее количество абонентов ('
                                    + formatInteger(last.total)
                                    + ')',

                                data:
                                    subscribersChartData.map(
                                        (row) =>
                                            row.total
                                    ),

                                borderColor: '#ff0000',
                                backgroundColor: '#ff0000',

                                borderWidth: 3,
                                pointRadius: 1,
                                pointHoverRadius: 5,
                                tension: 0.2,
                                fill: false,
                            },
                        ],
                        },


                        options: {
                            responsive: true,

                            maintainAspectRatio:
                                false,

                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },

                            plugins: {
                                legend: {
                                    display: true,
                                },

                                tooltip: {
                                    callbacks: {
                                        label(
                                            context
                                        ) {
                                            return (
                                                context
                                                    .dataset
                                                    .label
                                                + ': '
                                                + formatInteger(
                                                    context
                                                        .parsed
                                                        .y
                                                )
                                            );
                                        },
                                    },
                                },
                            },

                            scales: {
                                x: {
                                    ticks: {
                                        autoSkip:
                                            true,

                                        maxTicksLimit:
                                            20,

                                        maxRotation:
                                            45,

                                        minRotation:
                                            45,
                                    },
                                },

                                y: {
                                    beginAtZero:
                                        false,

                                    ticks: {
                                        precision: 0,

                                        callback(
                                            value
                                        ) {
                                            return formatInteger(
                                                value
                                            );
                                        },
                                    },
                                },
                            },
                        },
                    }
                );
        }


        /*
         * График объявлений.
         */
        function openAdsChart() {

            destroyMoneyChart();

            chartModalTitle.textContent =
                'Объявления';


            /*
             * Пока нет данных.
             */
            if (
                !Array.isArray(
                    adsChartLabels
                )
                || adsChartLabels.length === 0
            ) {

                chartModalContent.innerHTML =
                    '<div class="money-chart-placeholder">'
                    + 'Нет данных для построения графика.'
                    + '</div>';

                chartModal.showModal();

                return;
            }


            chartModalContent.innerHTML =
                '<div class="money-chart-summary">'
                + buildAdsYearSummary()
                + '</div>'

                + '<div class="money-chart-canvas-wrap">'
                + '<canvas id="money-ads-chart"></canvas>'
                + '</div>';


            chartModal.showModal();


            const canvas =
                document.getElementById(
                    'money-ads-chart'
                );


            if (
                !canvas
                || typeof Chart === 'undefined'
            ) {

                chartModalContent.innerHTML =
                    '<div class="money-chart-placeholder">'
                    + 'Не удалось загрузить библиотеку графиков.'
                    + '</div>';

                return;
            }


            moneyChartInstance =
                new Chart(
                    canvas,
                    {
                        type: 'line',

                        data: {

                            labels:
                                adsChartLabels,

                            datasets: [
                                {
                                    label:
                                        'Сумма объявлений',

                                    data:
                                        adsChartValues,

                                    borderWidth: 2,

                                    pointRadius: 2,

                                    pointHoverRadius: 5,

                                    tension: 0.25,

                                    fill: false,
                                },
                            ],
                        },


                        options: {

                            responsive: true,

                            maintainAspectRatio:
                                false,

                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },


                            plugins: {

                                legend: {
                                    display: true,
                                },


                                tooltip: {

                                    callbacks: {

                                        label(
                                            context
                                        ) {

                                            return (
                                                ' '
                                                + formatMoney(
                                                    context
                                                        .parsed
                                                        .y
                                                )
                                            );
                                        },
                                    },
                                },
                            },


                            scales: {

                                x: {

                                    ticks: {

                                        autoSkip:
                                            true,

                                        maxTicksLimit:
                                            20,

                                        maxRotation:
                                            45,

                                        minRotation:
                                            45,
                                    },
                                },


                                y: {

                                    beginAtZero:
                                        true,

                                    ticks: {

                                        callback(
                                            value
                                        ) {

                                            return new Intl
                                                .NumberFormat(
                                                    'ru-RU'
                                                )
                                                .format(
                                                    value
                                                );
                                        },
                                    },
                                },
                            },
                        },
                    }
                );
        }


        /*
         * Кнопки графиков.
         */
        document
            .querySelectorAll(
                '[data-money-chart]'
            )
            .forEach(
                (button) => {

                    button.addEventListener(
                        'click',
                        () => {

                            const type =
                                button.dataset
                                    .moneyChart;


                            if (
                                type
                                === 'subscribers'
                            ) {

                                openSubscribersChart();

                                return;
                            }


                            if (
                                type
                                === 'ads'
                            ) {

                                openAdsChart();
                            }
                        }
                    );
                }
            );


        /*
         * Закрытие модального окна.
         */
        chartModalClose.addEventListener(
            'click',
            () => {

                chartModal.close();
            }
        );


        chartSaveButton.addEventListener(
            'click',
            () => {
                if (!moneyChartInstance) {
                    return;
                }

                const sourceCanvas =
                    moneyChartInstance.canvas;

                const exportCanvas =
                    document.createElement(
                        'canvas'
                    );

                exportCanvas.width =
                    sourceCanvas.width;

                exportCanvas.height =
                    sourceCanvas.height;

                const ctx =
                    exportCanvas.getContext(
                        '2d'
                    );

                /*
                * Белый фон.
                */
                ctx.fillStyle = '#ffffff';

                ctx.fillRect(
                    0,
                    0,
                    exportCanvas.width,
                    exportCanvas.height
                );

                /*
                * Сам график.
                */
                ctx.drawImage(
                    sourceCanvas,
                    0,
                    0
                );

                const image =
                    exportCanvas.toDataURL(
                        'image/png',
                        1
                    );

                const link =
                    document.createElement(
                        'a'
                    );

                const now =
                    new Date();

                const day =
                    String(
                        now.getDate()
                    ).padStart(
                        2,
                        '0'
                    );

                const month =
                    String(
                        now.getMonth() + 1
                    ).padStart(
                        2,
                        '0'
                    );

                const year =
                    now.getFullYear();

                if (
                    chartModalTitle.textContent
                    === 'Абоненты'
                ) {
                    fileName =
                        'trianda_'
                        + day
                        + month
                        + year
                        + '.png';
                }

                if (
                    chartModalTitle.textContent
                    === 'Объявления'
                ) {
                    fileName =
                        'ads_'
                        + day
                        + month
                        + year
                        + '.png';
                }

                link.href = image;

                link.download =
                    fileName;

                document.body.appendChild(
                    link
                );

                link.click();

                link.remove();
            }
        );


        /*
         * Уничтожаем Chart.js
         * после закрытия окна.
         */
        chartModal.addEventListener(
            'close',
            () => {

                destroyMoneyChart();

                chartModalContent.innerHTML =
                    '';
            }
        );


        /*
         * Inline-редактирование.
         */
        document
            .querySelectorAll(
                '.money-editable'
            )
            .forEach(
                (cell) => {

                    cell.addEventListener(
                        'click',
                        () => {

                            if (
                                activeCell
                                === cell
                            ) {
                                return;
                            }


                            /*
                             * Закрываем предыдущую
                             * редактируемую ячейку.
                             */
                            if (activeCell) {

                                restoreCell(
                                    activeCell
                                );
                            }


                            activeCell =
                                cell;


                            const originalValue =
                                cell.dataset.value
                                || '';


                            const input =
                                document.createElement(
                                    'input'
                                );


                            input.type =
                                'text';

                            input.className =
                                'money-edit-input';

                            input.value =
                                originalValue;


                            cell.textContent =
                                '';

                            cell.appendChild(
                                input
                            );


                            input.focus();

                            input.select();


                            /*
                             * Enter — сохранить.
                             * Escape — отменить.
                             */
                            input.addEventListener(
                                'keydown',
                                (event) => {

                                    if (
                                        event.key
                                        === 'Enter'
                                    ) {

                                        event
                                            .preventDefault();

                                        saveCell(
                                            cell,
                                            input.value
                                        );

                                        return;
                                    }


                                    if (
                                        event.key
                                        === 'Escape'
                                    ) {

                                        event
                                            .preventDefault();

                                        restoreCell(
                                            cell
                                        );

                                        activeCell =
                                            null;
                                    }
                                }
                            );


                            /*
                             * Потеря фокуса —
                             * отменяем изменение.
                             */
                            input.addEventListener(
                                'blur',
                                () => {

                                    if (
                                        activeCell
                                        !== cell
                                    ) {
                                        return;
                                    }


                                    restoreCell(
                                        cell
                                    );

                                    activeCell =
                                        null;
                                }
                            );
                        }
                    );
                }
            );


        /*
         * Сохранение ячейки.
         */
        function saveCell(
            cell,
            value
        ) {

            idInput.value =
                cell.dataset.id;

            fieldInput.value =
                cell.dataset.field;

            valueInput.value =
                value;

            editForm.submit();
        }


        /*
         * Возвращаем отображение
         * исходного значения.
         */
        function restoreCell(
            cell
        ) {

            const value =
                cell.dataset.value
                || '';


            /*
             * Обычные большие числа
             * отображаем с разделителем.
             */
            if (
                value !== ''
                && !value.includes('/')
                && !Number.isNaN(
                    Number(value)
                )
                && Number(value) >= 1000
            ) {

                cell.textContent =
                    Math.trunc(
                        Number(value)
                    ).toLocaleString(
                        'ru-RU'
                    );

                return;
            }


            cell.textContent =
                value;
        }

    }
);

</script>


<style>

.money-page {
    width: 100%;
}



.money-chart-modal__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.money-chart-modal__save {
    border: 1px solid #aaa;
    border-radius: 4px;

    background: #fff;

    padding: 5px 10px;

    font: inherit;

    cursor: pointer;
}

.money-chart-modal__save:hover {
    background: #eee;
}





.money-chart-summary {
    display: grid;
    grid-template-columns:
        repeat(
            auto-fit,
            minmax(100px, 1fr)
        );

    width: 100%;

    gap: 10px;

    margin-bottom: 18px;
}

.money-chart-year-total {
    display: flex;
    flex-direction: column;
    align-items: center;

    min-width: 0;

    text-align: center;
    white-space: nowrap;
}

.money-chart-year {
    margin-bottom: 3px;

    font-size: 12px;

    color: #666;
}

.money-chart-year-total strong {
    font-size: 14px;
    font-weight: 700;
}
/*
 * Верхняя панель.
 */
.money-toolbar {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    margin-bottom: 18px;
}


/*
 * Выбор месяца/года.
 */
.money-filter {
    display: flex;

    align-items: center;

    gap: 12px;
}


.money-filter label {
    display: flex;

    align-items: center;

    gap: 6px;

    font-size: 13px;
}


.money-filter select {
    padding: 5px 8px;

    font: inherit;
}


/*
 * Кнопки графиков.
 */
.money-chart-buttons {
    display: flex;

    align-items: center;

    gap: 8px;
}


.money-chart-button {
    padding: 6px 14px;

    border: 1px solid #aaa;

    border-radius: 4px;

    background: #fff;

    font: inherit;

    cursor: pointer;
}


.money-chart-button:hover {
    background: #eee;
}


/*
 * Таблица.
 */
.money-table-wrap {
    width: 100%;

    overflow-x: auto;
}


.money-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}


.money-table th {
    text-align: center;
    font-weight: 700;
    background: #ddfaea;
    border: 1px solid #bbb;
    padding: 6px 4px;
    white-space: nowrap;
}


.money-table td {
    text-align: center;
    border: 1px solid #ccc;
    padding: 5px 4px;
    min-width: 90px;
}


.money-table tbody tr:hover {
    background: rgba(
        127,
        127,
        127,
        .08
    );
}


/*
 * Редактируемые ячейки.
 */
.money-editable {
    cursor: pointer;
}


/*
 * Новая запись.
 */
.money-add-row {
    background: #b8e0ff;
}


.money-add-row:hover {
    background: #b8e0ff !important;
}


/*
 * Итог.
 */
.money-total-row {
    background: #fddfe3;

    font-weight: 700;
}


.money-total-row:hover {
    background: #fddfe3 !important;
}


/*
 * Input.
 */
.money-input,
.money-edit-input {
    box-sizing: border-box;

    width: 100%;

    min-width: 70px;

    padding: 4px;

    text-align: center;

    font: inherit;
}


.money-input {
    border: 1px solid #888;
}


.money-edit-input {
    border: 0;

    outline: 1px solid #2563eb;
}


/*
 * Модальное окно.
 */
.money-chart-modal {
    width: min(
        1100px,
        calc(100% - 40px)
    );

    max-width: 1100px;

    border: 0;

    border-radius: 8px;

    padding: 0;

    background: #fff;
}


.money-chart-modal::backdrop {
    background: rgba(
        0,
        0,
        0,
        .55
    );
}


.money-chart-modal__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 12px 16px;

    border-bottom: 1px solid #ddd;
}


.money-chart-modal__title {
    font-size: 16px;
}


.money-chart-modal__close {
    border: 0;

    background: transparent;

    padding: 0 5px;

    font-size: 28px;

    line-height: 1;

    cursor: pointer;
}


.money-chart-modal__content {
    padding: 20px;
}


/*
 * Сводка над графиком.
 */
.money-chart-summary {
    margin-bottom: 12px;

    font-size: 14px;
}


/*
 * Размер области графика.
 */
.money-chart-canvas-wrap {
    position: relative;

    width: 100%;

    height: 480px;
}


/*
 * Заглушка.
 */
.money-chart-placeholder {
    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 400px;

    color: #777;

    font-size: 14px;
}


/*
 * Мобильный экран.
 */
@media (
    max-width: 700px
) {

    .money-toolbar {
        flex-direction: column;

        align-items: stretch;
    }


    .money-filter {
        justify-content: flex-start;
    }


    .money-chart-buttons {
        justify-content: flex-end;
    }


    .money-chart-modal {
        width: calc(
            100% - 20px
        );
    }


    .money-chart-canvas-wrap {
        height: 380px;
    }

}


@media (
    max-width: 900px
) {

    .money-table {
        min-width: 850px;
    }

}

</style>