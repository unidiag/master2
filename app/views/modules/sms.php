 <?php
 


/** @var string $module */
/** @var array $data */

if ($module === 'sms'): ?>

        <form
            method="post"
            id="sms-verification-form"
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
                value="sms_verification"
            >

            <input
                type="hidden"
                name="phone"
                id="sms-verification-phone"
                value=""
            >

            <input
                type="hidden"
                name="return_url"
                value="<?= e(
                    $_SERVER['REQUEST_URI']
                    ?? '/index.php?module=sms'
                ) ?>"
            >
        </form>

        <?php endif; ?>
        <?php if ($module === 'sms'): ?>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById(
                'sms-verification-button'
            );

            const form = document.getElementById(
                'sms-verification-form'
            );

            const phoneInput = document.getElementById(
                'sms-verification-phone'
            );

            if (!button || !form || !phoneInput) {
                return;
            }

            button.addEventListener('click', function () {
                const phone = window.prompt(
                    'Введите номер телефона в международном формате:\n' +
                    '+375297153616 или 375297153616'
                );

                /*
                * Нажата "Отмена".
                */
                if (phone === null) {
                    return;
                }

                const value = phone.trim();

                if (value === '') {
                    return;
                }

                /*
                * На клиенте принимаем только:
                *
                * 375297153616
                * +375297153616
                *
                * То есть "+" допустим только первым символом.
                */
                if (!/^\+?\d{12}$/.test(value)) {
                    window.alert(
                        'Введите номер из 12 цифр ' +
                        'в международном формате.\n\n' +
                        'Например: +375297153616'
                    );

                    return;
                }

                phoneInput.value = value;

                form.submit();
            });
        });
        </script>

        <?php endif; ?>





    <div class="subscriber-list">

        <?php foreach ($data['rows'] as $row): ?>
            <?php
            $smsStatusText = trim(
                (string) ($row['status_text'] ?? '')
            );

            $checkedAt = trim(
                (string) ($row['checked_at'] ?? '')
            );

            $sentAt = trim(
                (string) ($row['sent_at'] ?? '')
            );
            ?>

            <div class="subscriber-card<?= !empty($row['deleted_at']) ? ' sms-row--deleted' : '' ?>">

                <div>
                    <strong>
                        <?= e(
                            (string) (
                                $row['abonent']
                                ?: 'Без имени'
                            )
                        ) ?>
                    </strong>

                    <div class="muted">
                        <?php if (($row['address'] ?? '') !== ''): ?>
                            <a
                                class="address-link"
                                href="<?= e(url([
                                    'module' => 'stat',
                                    'house' => $row['address'],
                                ])) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?= e($row['address']) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($row['message'])): ?>
                        <div class="sms-message">
                            <?= nl2br(e((string) $row['message'])) ?>
                        </div>
                    <?php endif; ?>

                </div>

                <div>
                    <span class="label">
                        Телефон
                    </span>

                    <?= e(
                        (string) (
                            $row['phone']
                            ?? ''
                        )
                    ) ?>
                </div>

                <div>
                    <span class="label">
                        Отправлено
                    </span>

                    <?= e(
                        $sentAt !== ''
                            ? format_datetime($sentAt)
                            : '—'
                    ) ?>
                </div>

                <div>
                    <span class="label">
                        Статус [<?php echo $row['status']?>]
                    </span>

                    <?php if ((int) ($row['status'] ?? 0) === 2): ?>
                        <strong style="color: green;">
                            Доставлено
                        </strong>

                    <?php elseif ((int) ($row['status'] ?? 0) === 1): ?>
                        <strong style="color: orange;">
                            В доставке..
                        </strong>

                    <?php elseif ((int) ($row['status'] ?? 0) === 3): ?>
                        <strong style="color: red;">
                            Недоступен
                        </strong>

                    <?php else: ?>
                        <span class="muted">
                            Не проверялся
                        </span>
                    <?php endif; ?>

                    <?php if ($checkedAt !== ''): ?>
                        <div class="muted">
                            <?= e(
                                format_datetime(
                                    $checkedAt
                                )
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="sms_status"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= e(
                                (string) $row['id']
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="return_url"
                            value="<?= e(
                                $_SERVER['REQUEST_URI']
                                ?? '/index.php?module=sms'
                            ) ?>"
                        >

                        <button
                            class="button"
                            type="submit"
                        >
                            Статус
                        </button>

                    </form>
                </div>

                <div>
                    <form
                        method="post"
                        onsubmit="return confirm('Удалить SMS?');"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="sms_delete"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= e(
                                (string) $row['id']
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="return_url"
                            value="<?= e(
                                $_SERVER['REQUEST_URI']
                                ?? '/index.php?module=sms'
                            ) ?>"
                        >

                        <button
                            class="button danger"
                            type="submit"
                        >
                            Удалить
                        </button>
                    </form>
                </div>



            </div>

        <?php endforeach; ?>

        <?php if (!$data['rows']): ?>

            <div class="empty-state">
                <strong>
                    SMS не найдены
                </strong>

                <span>
                    Здесь появятся отправленные сообщения.
                </span>
            </div>

        <?php endif; ?>

    </div>


