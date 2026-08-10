<?php

declare(strict_types=1);

function render_sms_button(
    string $address,
    string $phone,
    string $personal,
    float $debt,
    string $house = ''
): void {
    $address = trim($address);
    $phone = trim($phone);
    $personal = trim($personal);
    $house = trim($house);

    $modalId = 'sms-modal-' . md5(
        $address . '|' . $personal
    );

    $debtFormatted = number_format(
        $debt,
        2,
        ',',
        ''
    );

    $defaultMessage =
        'Задолженность по л/с '
        . $personal
        . ' составляет '
        . $debtFormatted
        . ' руб.';
    ?>

    <button
        class="button"
        type="button"
        data-modal-open="<?= e($modalId) ?>"
        onclick="event.preventDefault(); event.stopPropagation();"
    >
       ✉ SMS
    </button>

    <dialog
        class="modal"
        id="<?= e($modalId) ?>"
        onclick="event.stopPropagation();"
    >
        <form
            method="post"
            onclick="event.stopPropagation();"
        >
            <div class="modal-head">
                <h2>Отправить SMS</h2>

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
                value="sms_send"
            >

            <input
                type="hidden"
                name="return_url"
                value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>"
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
                value="<?= e($address) ?>"
            >

            <label>
                Номер телефона

                <input
                    class="input"
                    type="text"
                    name="phone"
                    value="<?= e($phone) ?>"
                    maxlength="100"
                    required
                    autocomplete="tel"
                >
            </label>

            <label>
                Текст сообщения

                <textarea
                    class="input"
                    name="message"
                    rows="6"
                    maxlength="1000"
                    required
                ><?= e($defaultMessage) ?></textarea>
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
                    Отправить
                </button>
            </div>
        </form>
    </dialog>

    <?php
}