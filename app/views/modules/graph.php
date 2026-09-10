
<?php
$graphSnapshots =
    $data['snapshots'] ?? [];

$graphTariffs =
    $data['tariffs'] ?? [];

$latestSnapshot =
    $graphSnapshots
        ? $graphSnapshots[
            count($graphSnapshots) - 1
        ]
        : null;

$latestUpdate = (int) (
    $latestSnapshot['update']
    ?? 0
);

$latestTotal = (int) (
    $latestSnapshot['total']
    ?? 0
);

$graphDebts = array_map(
    static function (array $snapshot): float {
        return (float) (
            $snapshot['debt']
            ?? 0
        );
    },
    $graphSnapshots
);

$graphMaxDebt = 0.0;
$graphMaxDebtDate = null;

$graphMinDebt = 0.0;
$graphMinDebtDate = null;

$graphCurrentDebt = 0.0;
$graphCurrentPeriodIncome = 0.0;

if ($graphSnapshots) {
    $graphMaxDebt = max($graphDebts);
    $graphMinDebt = min($graphDebts);

    /*
     * Текущая задолженность —
     * задолженность последнего снимка.
     */
    $graphCurrentDebt = (float) (
        $latestSnapshot['debt']
        ?? 0
    );

    foreach ($graphSnapshots as $snapshot) {
        $debt = (float) (
            $snapshot['debt']
            ?? 0
        );

        $update = (int) (
            $snapshot['update']
            ?? 0
        );

        /*
         * Нам нужна именно ПОСЛЕДНЯЯ дата,
         * когда задолженность находилась
         * на максимальном уровне.
         *
         * Поэтому здесь нет проверки === null.
         */
        if ($debt === $graphMaxDebt) {
            $graphMaxDebtDate = $update;
        }

        /*
         * Для минимальной пока оставляем
         * первую найденную дату, как было раньше.
         */
        if (
            $graphMinDebtDate === null
            && $debt === $graphMinDebt
        ) {
            $graphMinDebtDate = $update;
        }
    }

    /*
     * Сколько задолженности погашено
     * с последнего максимума до текущего момента.
     */
    $graphCurrentPeriodIncome =
        $graphMaxDebt
        - $graphCurrentDebt;
}
?>

<section class="graph-page">

    <div class="page-heading graph-heading">
        <div>
            <h1>График абонентов</h1>

            <p class="page-heading__description">
                Изменение количества действующих
                договоров по пакетам.
            </p>
        </div>

        <?php if ($latestSnapshot !== null): ?>
            <div class="graph-heading__summary">

                <div>
                    <span>Всего с договором</span>

                    <strong>
                        <?= e(number_format(
                            $latestTotal,
                            0,
                            ',',
                            ' '
                        )) ?>
                    </strong>
                </div>

                <div>
                    <span>Заработано за период</span>
                    <strong>
                        <?= e(number_format(
                            $graphCurrentPeriodIncome,
                            2,
                            ',',
                            ' '
                        )) ?> р.
                    </strong>
                </div>

                <div>
                    <span>Обновление</span>

                    <strong>
                        <?= e(date(
                            'd.m.Y H:i',
                            $latestUpdate
                        )) ?>
                    </strong>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <?php if (!$graphSnapshots): ?>

        <div class="empty-state">
            Для построения графика пока
            недостаточно данных.
        </div>

    <?php else: ?>


        <div class="graph-card">

            <div class="graph-card__canvas">
                <canvas
                    id="subscriber-graph"
                ></canvas>
            </div>

            <div
                class="graph-legend"
                id="subscriber-graph-legend"
            ></div>

        </div>


        <div class="graph-card">

            <div class="graph-card__title">
                <div>
                    Задолженность
                </div>

                <div class="graph-card__stats">
                    <span>
                        Минимальная:
                        <strong>
                            <?= e(number_format(
                                $graphMinDebt,
                                2,
                                ',',
                                ' '
                            )) ?>

                            <?php if ($graphMinDebtDate): ?>
                                (<?= e(date(
                                    'd.m.Y',
                                    $graphMinDebtDate
                                )) ?>)
                            <?php endif; ?>
                        </strong>
                    </span>

                    <span>
                        Максимальная:
                        <strong>
                            <?= e(number_format(
                                $graphMaxDebt,
                                2,
                                ',',
                                ' '
                            )) ?>

                            <?php if ($graphMaxDebtDate): ?>
                                (<?= e(date(
                                    'd.m.Y',
                                    $graphMaxDebtDate
                                )) ?>)
                            <?php endif; ?>
                        </strong>
                    </span>
                </div>
            </div>

            <div class="graph-card__canvas">
                <canvas id="debt-graph"></canvas>
            </div>

            <div class="graph-legend">
                <div class="graph-legend__item">
                    <span
                        class="graph-legend__marker"
                        style="background:#dc2626"
                    ></span>

                    <span>
                        Общая задолженность
                    </span>
                </div>
            </div>

        </div>



        <script
            type="application/json"
            id="subscriber-graph-data"
        ><?= json_encode(
            [
                'snapshots' =>
                    $graphSnapshots,

                'tariffs' =>
                    $graphTariffs,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) ?></script>

        <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const canvas =
                    document.getElementById(
                        'subscriber-graph'
                    );

                const debtCanvas =
                    document.getElementById(
                        'debt-graph'
                    );                    

                const dataElement =
                    document.getElementById(
                        'subscriber-graph-data'
                    );

                const legendElement =
                    document.getElementById(
                        'subscriber-graph-legend'
                    );

                if (
                    !canvas
                    || !dataElement
                ) {
                    return;
                }

                const graphData =
                    JSON.parse(
                        dataElement.textContent
                    );

                const snapshots =
                    graphData.snapshots || [];

                const tariffs =
                    graphData.tariffs || [];

                if (!snapshots.length) {
                    return;
                }

                const colors = [
                    '#2563eb',
                    '#16a34a',
                    '#ea580c',
                    '#9333ea',
                    '#0891b2',
                    '#db2777',
                    '#65a30d',
                    '#d97706'
                ];

                const series =
                    tariffs.map(
                        function (
                            tariff,
                            index
                        ) {
                            return {
                                name: tariff,
                                color:
                                    colors[
                                        index
                                        % colors.length
                                    ],

                                values:
                                    snapshots.map(
                                        function (
                                            snapshot
                                        ) {
                                            return Number(
                                                (
                                                    snapshot
                                                    .packages
                                                    || {}
                                                )[tariff]
                                                || 0
                                            );
                                        }
                                    )
                            };
                        }
                    );

                series.push({
                    name:
                        'Всего с договором',

                    color:
                        '#111827',

                    total: true,

                    values:
                        snapshots.map(
                            function (
                                snapshot
                            ) {
                                return Number(
                                    snapshot.total
                                    || 0
                                );
                            }
                        )
                });

                function formatDate(timestamp) {
                    return new Date(
                        Number(timestamp)
                        * 1000
                    ).toLocaleDateString(
                        'ru-RU',
                        {
                            day: '2-digit',
                            month: '2-digit'
                        }
                    );
                }

                function renderLegend() {
                    if (!legendElement) {
                        return;
                    }

                    legendElement
                        .replaceChildren();

                    series.forEach(
                        function (item) {
                            const element =
                                document.createElement(
                                    'div'
                                );

                            element.className =
                                'graph-legend__item';

                            const marker =
                                document.createElement(
                                    'span'
                                );

                            marker.className =
                                'graph-legend__marker';

                            marker.style
                                .backgroundColor =
                                    item.color;

                            const name =
                                document.createElement(
                                    'span'
                                );

                            name.textContent =
                                item.name;

                            element.append(
                                marker,
                                name
                            );

                            legendElement
                                .appendChild(
                                    element
                                );
                        }
                    );
                }

                function drawSubscribers() {
                    const parent =
                        canvas.parentElement;

                    const cssWidth = parent.clientWidth;

                    const cssHeight = 440;

                    const dpr =
                        window.devicePixelRatio
                        || 1;

                    canvas.width =
                        cssWidth * dpr;

                    canvas.height =
                        cssHeight * dpr;

                    canvas.style.width =
                        cssWidth + 'px';

                    canvas.style.height =
                        cssHeight + 'px';

                    const ctx =
                        canvas.getContext(
                            '2d'
                        );

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
                        cssWidth,
                        cssHeight
                    );

                    const padding = {
                        top: 30,
                        right: 30,
                        bottom: 55,
                        left: 65
                    };

                    const width =
                        cssWidth
                        - padding.left
                        - padding.right;

                    const height =
                        cssHeight
                        - padding.top
                        - padding.bottom;

                    let maxValue = 0;

                    series.forEach(
                        function (item) {
                            item.values.forEach(
                                function (value) {
                                    maxValue =
                                        Math.max(
                                            maxValue,
                                            value
                                        );
                                }
                            );
                        }
                    );

                    if (maxValue <= 0) {
                        maxValue = 10;
                    }

                    const step =
                        Math.max(
                            1,
                            Math.ceil(
                                maxValue / 5 / 10
                            ) * 10
                        );

                    const yMax =
                        Math.ceil(
                            maxValue / step
                        ) * step;

                    ctx.font =
                        '12px sans-serif';

                    ctx.textAlign =
                        'right';

                    ctx.textBaseline =
                        'middle';

                    for (
                        let i = 0;
                        i <= 5;
                        i++
                    ) {
                        const value =
                            Math.round(
                                yMax
                                - (
                                    yMax
                                    * i / 5
                                )
                            );

                        const y =
                            padding.top
                            + (
                                height
                                * i / 5
                            );

                        ctx.beginPath();

                        ctx.strokeStyle =
                            '#e5e7eb';

                        ctx.lineWidth = 1;

                        ctx.moveTo(
                            padding.left,
                            y
                        );

                        ctx.lineTo(
                            padding.left
                            + width,
                            y
                        );

                        ctx.stroke();

                        ctx.fillStyle =
                            '#6b7280';

                        ctx.fillText(
                            new Intl.NumberFormat(
                                'ru-RU'
                            ).format(
                                value
                            ),

                            padding.left - 10,
                            y
                        );
                    }

                    const pointCount =
                        snapshots.length;

                    function xFor(index) {
                        if (
                            pointCount <= 1
                        ) {
                            return (
                                padding.left
                                + width / 2
                            );
                        }

                        return (
                            padding.left
                            + (
                                width
                                * index
                                / (
                                    pointCount
                                    - 1
                                )
                            )
                        );
                    }

                    function yFor(value) {
                        return (
                            padding.top
                            + height
                            - (
                                value
                                / yMax
                                * height
                            )
                        );
                    }

                    const maxLabels =
                        Math.max(
                            2,
                            Math.floor(
                                width / 90
                            )
                        );

                    const labelEvery =
                        Math.max(
                            1,
                            Math.ceil(
                                pointCount
                                / maxLabels
                            )
                        );

                    ctx.textAlign =
                        'center';

                    ctx.textBaseline =
                        'top';

                    snapshots.forEach(
                        function (
                            snapshot,
                            index
                        ) {
                            if (
                                index
                                % labelEvery
                                !== 0
                                && index
                                !== pointCount - 1
                            ) {
                                return;
                            }

                            const x =
                                xFor(index);

                            ctx.fillStyle =
                                '#6b7280';

                            ctx.fillText(
                                formatDate(
                                    snapshot.update
                                ),
                                x,
                                padding.top
                                + height
                                + 14
                            );
                        }
                    );

                    series.forEach(
                        function (item) {
                            ctx.beginPath();

                            ctx.strokeStyle =
                                item.color;

                            ctx.lineWidth =
                                item.total
                                    ? 3
                                    : 2;

                            ctx.lineJoin =
                                'round';

                            ctx.lineCap =
                                'round';

                            item.values.forEach(
                                function (
                                    value,
                                    index
                                ) {
                                    const x =
                                        xFor(index);

                                    const y =
                                        yFor(value);

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

                            if (
                                pointCount <= 40
                            ) {
                                item.values
                                    .forEach(
                                        function (
                                            value,
                                            index
                                        ) {
                                            const x =
                                                xFor(
                                                    index
                                                );

                                            const y =
                                                yFor(
                                                    value
                                                );

                                            ctx.beginPath();

                                            ctx.fillStyle =
                                                item.color;

                                            ctx.arc(
                                                x,
                                                y,
                                                item.total
                                                    ? 3.5
                                                    : 2.5,
                                                0,
                                                Math.PI
                                                * 2
                                            );

                                            ctx.fill();
                                        }
                                    );
                            }
                        }
                    );
                }



                function drawDebt() {
                    if (!debtCanvas) {
                        return;
                    }

                    const parent =
                        debtCanvas.parentElement;

                    const cssWidth = parent.clientWidth;

                    const cssHeight = 360;

                    const dpr =
                        window.devicePixelRatio
                        || 1;

                    debtCanvas.width =
                        cssWidth * dpr;

                    debtCanvas.height =
                        cssHeight * dpr;

                    debtCanvas.style.width =
                        cssWidth + 'px';

                    debtCanvas.style.height =
                        cssHeight + 'px';

                    const ctx =
                        debtCanvas.getContext(
                            '2d'
                        );

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
                        cssWidth,
                        cssHeight
                    );

                    const padding = {
                        top: 30,
                        right: 30,
                        bottom: 55,
                        left: 85
                    };

                    const width =
                        cssWidth
                        - padding.left
                        - padding.right;

                    const height =
                        cssHeight
                        - padding.top
                        - padding.bottom;

                    const values =
                        snapshots.map(
                            function (snapshot) {
                                return Number(
                                    snapshot.debt
                                    || 0
                                );
                            }
                        );

                    let maxValue =
                        Math.max(
                            ...values,
                            0
                        );

                    if (maxValue <= 0) {
                        maxValue = 100;
                    }

                    /*
                    * Округляем верхнюю границу
                    * до удобного значения.
                    */
                    const roughStep =
                        maxValue / 5;

                    const magnitude =
                        Math.pow(
                            10,
                            Math.floor(
                                Math.log10(
                                    Math.max(
                                        roughStep,
                                        1
                                    )
                                )
                            )
                        );

                    const step =
                        Math.ceil(
                            roughStep
                            / magnitude
                        ) * magnitude;

                    const yMax =
                        step * 5;

                    ctx.font =
                        '12px sans-serif';

                    ctx.textAlign =
                        'right';

                    ctx.textBaseline =
                        'middle';

                    for (
                        let i = 0;
                        i <= 5;
                        i++
                    ) {
                        const value =
                            yMax
                            - (
                                step * i
                            );

                        const y =
                            padding.top
                            + (
                                height
                                * i / 5
                            );

                        ctx.beginPath();

                        ctx.strokeStyle =
                            '#e5e7eb';

                        ctx.lineWidth = 1;

                        ctx.moveTo(
                            padding.left,
                            y
                        );

                        ctx.lineTo(
                            padding.left
                            + width,
                            y
                        );

                        ctx.stroke();

                        ctx.fillStyle =
                            '#6b7280';

                        ctx.fillText(
                            new Intl.NumberFormat(
                                'ru-RU',
                                {
                                    maximumFractionDigits: 0
                                }
                            ).format(
                                value
                            ),

                            padding.left - 10,
                            y
                        );
                    }

                    const pointCount =
                        snapshots.length;

                    function xFor(index) {
                        if (pointCount <= 1) {
                            return (
                                padding.left
                                + width / 2
                            );
                        }

                        return (
                            padding.left
                            + (
                                width
                                * index
                                / (
                                    pointCount
                                    - 1
                                )
                            )
                        );
                    }

                    function yFor(value) {
                        return (
                            padding.top
                            + height
                            - (
                                value
                                / yMax
                                * height
                            )
                        );
                    }

                    /*
                    * Подписи дат.
                    */
                    const maxLabels =
                        Math.max(
                            2,
                            Math.floor(
                                width / 90
                            )
                        );

                    const labelEvery =
                        Math.max(
                            1,
                            Math.ceil(
                                pointCount
                                / maxLabels
                            )
                        );

                    ctx.textAlign =
                        'center';

                    ctx.textBaseline =
                        'top';

                    snapshots.forEach(
                        function (
                            snapshot,
                            index
                        ) {
                            if (
                                index
                                % labelEvery
                                !== 0
                                && index
                                !== pointCount - 1
                            ) {
                                return;
                            }

                            ctx.fillStyle =
                                '#6b7280';

                            ctx.fillText(
                                formatDate(
                                    snapshot.update
                                ),
                                xFor(index),
                                padding.top
                                + height
                                + 14
                            );
                        }
                    );

                    /*
                    * Линия задолженности.
                    */
                    ctx.beginPath();

                    ctx.strokeStyle =
                        '#dc2626';

                    ctx.lineWidth = 3;

                    ctx.lineJoin =
                        'round';

                    ctx.lineCap =
                        'round';

                    values.forEach(
                        function (
                            value,
                            index
                        ) {
                            const x =
                                xFor(index);

                            const y =
                                yFor(value);

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

                    /*
                    * Точки показываем,
                    * пока выборок не слишком много.
                    */
                    if (pointCount <= 50) {
                        values.forEach(
                            function (
                                value,
                                index
                            ) {
                                ctx.beginPath();

                                ctx.fillStyle =
                                    '#dc2626';

                                ctx.arc(
                                    xFor(index),
                                    yFor(value),
                                    3.5,
                                    0,
                                    Math.PI * 2
                                );

                                ctx.fill();
                            }
                        );
                    }
                }



                renderLegend();
                drawSubscribers();
                drawDebt();

                let resizeTimer = null;

                window.addEventListener(
                    'resize',
                    function () {
                        clearTimeout(
                            resizeTimer
                        );

                    resizeTimer =
                        setTimeout(
                            function () {
                                drawSubscribers();
                                drawDebt();
                            },
                            100
                        );
                    }
                );
            }
        );
        </script>

    <?php endif; ?>

</section>












