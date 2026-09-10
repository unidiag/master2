    <section class="karandash-page">
        <div class="page-heading">
            <div>
                <h1>Карандаш</h1>

                <div class="page-heading__counter">
                    <?= e(number_format(
                        (int) ($data['total'] ?? 0),
                        0,
                        ',',
                        ' '
                    )) ?>
                    записей
                </div>
            </div>
        </div>

        <?php
        $karandashHouses = $data['houses'] ?? [];
        ?>

        <?php if (!$karandashHouses): ?>
            <div class="empty-state">
                <strong>На карандаше никого нет</strong>
                <span>
                    Записи появятся после добавления абонентов
                    из раздела статистики.
                </span>
            </div>
        <?php else: ?>
            <div class="karandash-houses">
                <?php foreach ($karandashHouses as $houseData): ?>
                    <?php
                    $houseName = (string) (
                        $houseData['house'] ?? ''
                    );

                    $items = $houseData['items'] ?? [];
                    ?>

                    <section class="karandash-house">
                        <a
                            class="karandash-house__heading"
                            href="<?= e(url([
                                'module' => 'stat',
                                'house' => $houseName,
                            ])) ?>"
                        >
                            <div>
                                <h2><?= e($houseName) ?></h2>

                                <span>
                                    <?= e(number_format(
                                        count($items),
                                        0,
                                        ',',
                                        ' '
                                    )) ?>
                                    записей
                                </span>
                            </div>

                            <span>→</span>
                        </a>

                        <div class="karandash-list">
                            <?php foreach ($items as $item): ?>

                                <?php
                                $descr = trim(
                                    (string) ($item['descr'] ?? '')
                                );

                                $karandashCardClass = $descr === ''
                                    ? ' karandash-card--empty'
                                    : '';
                                ?>

                                <article
                                    class="karandash-card karandash-card--editable<?= $karandashCardClass ?>"
                                    role="button"
                                    tabindex="0"
                                    data-karandash-edit='<?= e(json_encode([
                                        'address' => (string) ($item['address'] ?? ''),
                                        'descr' => (string) ($item['descr'] ?? ''),
                                        'apartment' => (string) ($item['apartment'] ?? ''),
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                >
                                    <div class="karandash-card__head">
                                        <strong>
                                            <?php if (
                                                (string) (
                                                    $item['apartment'] ?? ''
                                                ) !== ''
                                            ): ?>
                                                кв.
                                                <?= e(
                                                    (string) $item['apartment']
                                                ) ?>
                                            <?php else: ?>
                                                <?= e(
                                                    (string) (
                                                        $item['address'] ?? ''
                                                    )
                                                ) ?>
                                            <?php endif; ?>
                                        </strong>

                                        <time
                                            datetime="<?= e(date(
                                                DATE_ATOM,
                                                (int) (
                                                    $item['update'] ?? 0
                                                )
                                            )) ?>"
                                        >
                                            <?= e(format_unix_time(
                                                (string) (
                                                    $item['update'] ?? ''
                                                ),
                                                'd.m.Y H:i'
                                            )) ?>
                                        </time>
                                    </div>

                                    <div class="karandash-card__address">
                                        <?= e(
                                            (string) (
                                                $item['account']
                                                ?: 'Абонент не найден'
                                            )
                                        ) ?>
                                    </div>

                                    <?php
                                    $descr = trim(
                                        (string) ($item['descr'] ?? '')
                                    );
                                    ?>

                                    <?php if ($descr !== ''): ?>
                                        <p class="karandash-card__descr">
                                            <?= nl2br(e($descr)) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (
                                        (string) ($item['time'] ?? '')
                                        !==
                                        (string) ($item['update'] ?? '')
                                    ): ?>
                                        <div class="karandash-card__created">
                                            Добавлено:
                                            <?= e(format_unix_time(
                                                (string) (
                                                    $item['time'] ?? ''
                                                ),
                                                'd.m.Y H:i'
                                            )) ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<dialog class="modal" id="karandash-edit-modal">
    <form method="post">
        <div class="modal-head">
            <h2>Изменить запись</h2>

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
            value="karandash"
        >

        <input
            type="hidden"
            name="address"
            id="karandash-edit-address"
            value=""
        >

        <input
            type="hidden"
            name="house"
            value=""
        >

        <input
            type="hidden"
            name="personal"
            value=""
        >

        <div class="karandash-address">
            <small>Адрес</small>

            <strong id="karandash-edit-address-label">
                —
            </strong>
        </div>

        <label>
            Причина

            <textarea
                class="input"
                id="karandash-edit-descr"
                name="descr"
                rows="6"
                maxlength="2000"
                placeholder="Почему абонент находится на карандаше"
            ></textarea>
        </label>

        <div class="modal-actions">
            <?php if (current_user() === 'admin'): ?>
                <button
                    class="button danger"
                    type="submit"
                    name="action"
                    value="karandash_delete"
                    formnovalidate
                    onclick="return confirm(
                        'Удалить эту запись с карандаша?'
                    )"
                >
                    Удалить
                </button>
            <?php endif; ?>

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
                name="action"
                value="karandash_add"
            >
                Сохранить
            </button>
        </div>
    </form>
</dialog>













