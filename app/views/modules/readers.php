<?php

declare(strict_types=1);

/** @var array $data */


/*
 * Этот файл подключается из app/view.php.
 *
 * Ожидается:
 *
 * $data['readers']
 * $data['selected']
 * $data['log']
 * $data['reader']
 * $data['rows']
 * $data['log_search']
 */

$readers = $data['readers'] ?? [];
$selected = $data['selected'] ?? null;
$selectedReader = (int)($data['reader'] ?? 0);
$logRows = (int)($data['rows'] ?? 50);
$logSearch = (string)($data['log_search'] ?? '');
?>



<style>
.reader-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 8px;
    margin-bottom: 16px;
}

.reader-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 52px;
    padding: 10px 12px;
    border: 1px solid var(--border, #d7dce3);
    border-radius: 8px;
    background: var(--card, #fff);
    text-decoration: none;
    color: inherit;
}

.reader-card:hover {
    border-color: #7b8794;
}

.reader-card.active {
    border-color: #3273dc;
    box-shadow: 0 0 0 1px #3273dc;
}

.reader-number.offline {
    background: #b42318;
}

.reader-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 7px;
    border-radius: 17px;
    background: #16833e;
    color: #fff;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.reader-number:hover {
    filter: brightness(1.1);
    color: #fff;
}

.reader-name {
    min-width: 0;
    flex: 1;
}

.reader-name strong {
    display: block;
}

.reader-meta {
    margin-top: 3px;
    font-size: 12px;
    opacity: .7;
}

.reader-autoreload {
    font-size: 17px;
}

.reader-status {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}

.reader-status-item {
    padding: 6px 10px;
    border-radius: 6px;
    background: rgba(127, 127, 127, .12);
    font-size: 13px;
}

.reader-status-item.ok {
    color: #16833e;
}

.reader-status-item.error {
    color: #b42318;
}

.reader-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.reader-log {
    width: 100%;
    overflow: auto;
    padding: 10px 12px;
    border: 1px solid #c9ced6;
    border-radius: 8px;
    background: #f5f5f5;
    color: #222;
    font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
    font-size: 12px;
    line-height: 1.25;
    min-height: 350px;
    max-height: 70vh;
}

.reader-log-line {
    margin: 0;
    padding: 0;
    line-height: 1.25;
    white-space: pre;
}

.reader-log-search {
    background: #ffeb3b;
    color: #111;
    font-weight: 700;
    padding: 0 2px;
    border-radius: 2px;
}

.reader-reboot {
    margin-left: auto;
    background: #b42318;
    color: #fff;
    border-color: #b42318;
}

.reader-reboot:hover {
    background: #8f1c13;
    border-color: #8f1c13;
}

.reader-card-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.reader-settings {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    width: 30px;
    height: 30px;

    padding: 0;
    border: 0;
    border-radius: 6px;

    background: transparent;
    color: inherit;

    font-size: 19px;
    cursor: pointer;

    opacity: .65;
}

.reader-settings:hover {
    opacity: 1;
    background: rgba(127, 127, 127, .14);
}

.reader-config-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
}

.reader-config-modal[hidden] {
    display: none;
}

.reader-config-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .55);
}

.reader-config-dialog {
    position: absolute;

    top: 10vh;
    left: 15vw;

    width: 70vw;
    height: 80vh;

    display: flex;
    flex-direction: column;

    border-radius: 10px;

    background: var(--card, #fff);
    color: inherit;

    overflow: hidden;
}

.reader-config-header {
    display: flex;
    align-items: center;

    padding: 12px 16px;

    border-bottom: 1px solid var(--border, #d7dce3);
}

.reader-config-header strong {
    flex: 1;
    font-size: 18px;
}

.reader-config-close {
    border: 0;
    background: transparent;
    color: inherit;

    font-size: 28px;
    cursor: pointer;
}

.reader-config-body {
    flex: 1;

    padding: 16px;

    overflow: auto;
}

.reader-config-info {
    display: grid;

    grid-template-columns:
        120px
        minmax(180px, 1fr)
        170px
        220px;

    gap: 12px;

    margin-bottom: 15px;
}

.reader-config-info label {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.reader-config-oscam {
    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 10px;

    height: calc(100% - 80px);
}

.reader-config-oscam label,
.reader-config-wicardd label {
    display: flex;
    flex-direction: column;

    min-height: 260px;
}

.reader-config-oscam textarea,
.reader-config-wicardd textarea {
    flex: 1;

    width: 100%;

    resize: none;

    padding: 8px;

    border: 1px solid var(--border, #c9ced6);
    border-radius: 6px;

    background: #111;
    color: #ddd;

    font-family:
        Menlo,
        Monaco,
        Consolas,
        "Courier New",
        monospace;

    font-size: 12px;
    line-height: 1.25;
}

.reader-config-wicardd {
    height: calc(100% - 80px);
}

.reader-config-footer {
    display: flex;
    align-items: center;
    gap: 8px;

    padding: 12px 16px;

    border-top: 1px solid var(--border, #d7dce3);
}

#readerConfigStatus {
    flex: 1;
    font-size: 13px;
}

.reader-actions {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
}

.reader-enabled {
    margin-left: auto;
}

.reader-enabled.on {
    background: #16833e;
    border-color: #16833e;
    color: #fff;
}

.reader-enabled.on:hover {
    background: #116b32;
    border-color: #116b32;
}

.reader-enabled.off {
    background: #7b8794;
    border-color: #7b8794;
    color: #fff;
}

.reader-enabled.off:hover {
    background: #626d77;
    border-color: #626d77;
}

.reader-reboot:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.reader-card-disabled {
    opacity: 0.7;
    background-color: #c5c5c6;
}

@media (max-width: 900px) {

    .reader-config-info {
        grid-template-columns: 1fr 1fr;
    }

    .reader-config-oscam {
        grid-template-columns: 1fr;
        height: auto;
    }

}

@media (max-width: 1000px) {
    .reader-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .reader-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="reader-grid">

<?php foreach ($readers as $reader): ?>

<?php
$id = (int)($reader['reader'] ?? 0);

$isRunning = !empty(
    $reader['running']
);

$isActive = (
    $id === $selectedReader
);

$name = trim(
    (string)($reader['name'] ?? '')
);

$type = trim(
    (string)($reader['oscam_type'] ?? '')
);
?>

<div
    class="reader-card <?= empty($reader['enabled']) ? 'reader-card-disabled' : '' ?> <?= $isActive ? ' active' : '' ?>"
    data-reader="<?= (int)$reader['reader'] ?>"
>

    <a
        class="reader-number<?= $isRunning ? '' : ' offline' ?>"
        href="<?= e(url([
            'module' => 'readers',
            'reader' => $id,
        ])) ?>"
    >
        <?= e((string) $id) ?>
    </a>

    <span class="reader-name">

        <strong>
            <?= e(
                $name !== ''
                    ? $name
                    : 'Reader #' . $id
            ) ?>
        </strong>

        <span class="reader-meta"><?= e($type) ?></span>

    </span>

    <div class="reader-card-actions">

        <?php if (!empty($reader['autoreload'])): ?>
            <span
                class="reader-autoreload"
                title="Автоматический рестарт включён"
            >
                ↻
            </span>
        <?php endif; ?>

        <button
            type="button"
            class="reader-settings"
            data-reader="<?= $id ?>"
            title="Редактировать Reader #<?= $id ?>"
        >
            ⚙
        </button>

    </div>

</div>

<?php endforeach; ?>

</div>


<?php if ($selected !== null): ?>

<?php
$isRunning = !empty(
    $selected['running']
);

$isEnabled = !empty(
    $selected['enabled']
);
?>



<form
    class="reader-toolbar"
    onsubmit="return false;"
>
    <input
        type="hidden"
        name="module"
        value="readers"
    >

    <input
        type="hidden"
        id="readerCsrfToken"
        value="<?= e(csrf_token()) ?>"
    >

    <input
        type="hidden"
        name="reader"
        value="<?= e((string) $selectedReader) ?>"
    >

    <label style="display:flex;align-items:center;gap:6px;">
        <input
            type="checkbox"
            id="readerAutoUpdate"
            checked
        >
        Автообновление
    </label>

    <input
        class="input"
        type="search"
        id="readerLogSearch"
        name="log_search"
        value="<?= e($logSearch) ?>"
        placeholder="Поиск по логу"
        autocomplete="off"
    >

    <select
        class="input select"
        id="readerLogRows"
        name="rows"
    >
        <?php foreach ([50, 500, 5000] as $value): ?>
            <option
                value="<?= $value ?>"
                <?= $logRows === $value ? 'selected' : '' ?>
            >
                <?= $value ?> строк
            </option>
        <?php endforeach; ?>
    </select>



    <div class="reader-actions">
        <button
            type="button"
            class="button reader-enabled <?= $isEnabled ? 'on' : 'off' ?>"
            id="readerEnabled"
            data-enabled="<?= $isEnabled ? '1' : '0' ?>"
        >
            <?= $isEnabled ? 'Включен' : 'Выключен' ?>
        </button>

        <button
            type="button"
            class="button reader-reboot"
            <?= $isEnabled ? '' : 'disabled' ?>
            id="readerReboot"
        >
            Reboot
        </button>    
    </div>


</form>


<div
    id="readerLogBody"
    class="reader-log"
>
<?php if (empty($data['log'])): ?>
<div class="reader-log-line">log file not found or access denied</div>
<?php else: ?>
<?php foreach ($data['log'] as $line): ?>
<div class="reader-log-line"><?= $readerService->formatLogLine(
    (string) $line,
    $logSearch
) ?></div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php else: ?>

<div class="empty-state">
    <strong>Reader не найден</strong>
</div>

<?php endif; ?>


<div
    class="reader-config-modal"
    id="readerConfigModal"
    hidden
>
    <div class="reader-config-backdrop"></div>

    <div class="reader-config-dialog">

        <div class="reader-config-header">

            <strong id="readerConfigTitle">
                Настройки ридера
            </strong>

            <button
                type="button"
                class="reader-config-close"
                id="readerConfigClose"
            >
                ×
            </button>

        </div>


        <div class="reader-config-body">

            <div class="reader-config-info">

                <label>
                    Reader

                    <input
                        type="text"
                        id="readerConfigNumber"
                        class="input"
                        disabled
                    >
                </label>


                <label>
                    Имя

                    <input
                        type="text"
                        id="readerConfigName"
                        class="input"
                        autocomplete="off"
                    >
                </label>


                <label>
                    Авторестарт

                    <select
                        id="readerConfigAutoreload"
                        class="input select"
                    >
                        <option value="0">нет</option>
                        <option value="1">да</option>
                    </select>
                </label>


                <label>
                    OSCam

                    <select
                        id="readerConfigOscamType"
                        class="input select"
                    ></select>
                </label>

            </div>


            <div
                class="reader-config-oscam"
                id="readerConfigOscam"
            >

                <label>
                    <span>oscam.conf</span>

                    <textarea
                        id="readerOscamConf"
                    ></textarea>
                </label>


                <label>
                    <span>oscam.server</span>

                    <textarea
                        id="readerOscamServer"
                    ></textarea>
                </label>


                <label>
                    <span>oscam.user</span>

                    <textarea
                        id="readerOscamUser"
                    ></textarea>
                </label>

            </div>


            <div
                class="reader-config-wicardd"
                id="readerConfigWicardd"
                hidden
            >

                <label>
                    <span>wicardd.conf</span>

                    <textarea
                        id="readerWicarddConf"
                    ></textarea>
                </label>

            </div>

        </div>


        <div class="reader-config-footer">

            <span id="readerConfigStatus"></span>

            <button
                type="button"
                class="button"
                id="readerConfigCancel"
            >
                Отмена
            </button>

            <button
                type="button"
                class="button"
                id="readerConfigSave"
            >
                Сохранить
            </button>

        </div>

    </div>
</div>













<script>
document.addEventListener('DOMContentLoaded', () => {
    const autoUpdate = document.getElementById('readerAutoUpdate');
    const logBody = document.getElementById('readerLogBody');
    const search = document.getElementById('readerLogSearch');
    const rows = document.getElementById('readerLogRows');
    const enabledButton = document.getElementById('readerEnabled');
    const reboot = document.getElementById('readerReboot');
    const csrfToken = document.getElementById('readerCsrfToken').value;


    const configModal =
        document.getElementById(
            'readerConfigModal'
        );

    const configTitle =
        document.getElementById(
            'readerConfigTitle'
        );

    const configNumber =
        document.getElementById(
            'readerConfigNumber'
        );

    const configName =
        document.getElementById(
            'readerConfigName'
        );

    const configAutoreload =
        document.getElementById(
            'readerConfigAutoreload'
        );

    const configOscamType =
        document.getElementById(
            'readerConfigOscamType'
        );

    const oscamConf =
        document.getElementById(
            'readerOscamConf'
        );

    const oscamServer =
        document.getElementById(
            'readerOscamServer'
        );

    const oscamUser =
        document.getElementById(
            'readerOscamUser'
        );

    const wicarddConf =
        document.getElementById(
            'readerWicarddConf'
        );

    const oscamBlock =
        document.getElementById(
            'readerConfigOscam'
        );

    const wicarddBlock =
        document.getElementById(
            'readerConfigWicardd'
        );

    const configStatus =
        document.getElementById(
            'readerConfigStatus'
        );

    const configSave =
        document.getElementById(
            'readerConfigSave'
        );

    let configReader = 0;



    const reader = <?= (int)$selectedReader ?>;

    let timer = null;
    let lastHtml = logBody.innerHTML;

    async function updateLog() {
        const params = new URLSearchParams({
            module: 'readers',
            reader: String(reader),
            ajax: 'log',
            rows: rows.value,
            log_search: search.value
        });

        try {
            const response = await fetch(
                '?' + params.toString(),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) {
                return;
            }

            const html = await response.text();

            if (html !== lastHtml) {
                logBody.innerHTML = html;
                lastHtml = html;
            }
        } catch (e) {
            console.error('Reader log update failed', e);
        }
    }

    function startAutoUpdate() {
        stopAutoUpdate();

        timer = setInterval(
            updateLog,
            1500
        );
    }

    function stopAutoUpdate() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    autoUpdate.addEventListener('change', () => {
        if (autoUpdate.checked) {
            updateLog();
            startAutoUpdate();
        } else {
            stopAutoUpdate();
        }
    });

    rows.addEventListener('change', () => {
        updateLog();
    });

    let searchTimer = null;

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(
            updateLog,
            250
        );
    });

    if (autoUpdate.checked) {
        updateLog();
        startAutoUpdate();
    }

    function updateConfigType() {

        const isWicardd =
            configOscamType.value ===
            'wicardd-x64';

        oscamBlock.hidden =
            isWicardd;

        wicarddBlock.hidden =
            !isWicardd;
    }

    configOscamType.addEventListener(
        'change',
        updateConfigType
    );




    document
        .querySelectorAll(
            '.reader-settings'
        )
        .forEach(button => {

            button.addEventListener(
                'click',
                event => {

                    event.preventDefault();
                    event.stopPropagation();

                    openReaderConfig(
                        Number(
                            button.dataset.reader
                        )
                    );

                }
            );

        });




        function closeReaderConfig() {
            configModal.hidden = true;
            configReader = 0;
        }

        document
            .getElementById(
                'readerConfigClose'
            )
            .addEventListener(
                'click',
                closeReaderConfig
            );

        document
            .getElementById(
                'readerConfigCancel'
            )
            .addEventListener(
                'click',
                closeReaderConfig
            );

        configModal
            .querySelector(
                '.reader-config-backdrop'
            )
            .addEventListener(
                'click',
                closeReaderConfig
            );       

            configSave.addEventListener(
                'click',
                async () => {

                    if (configReader <= 0) {
                        return;
                    }

                    configSave.disabled = true;

                    configStatus.textContent =
                        'Сохранение...';

                    try {

                        const body =
                            new URLSearchParams({
                                csrf_token:
                                    csrfToken,

                                name:
                                    configName.value,

                                autoreload:
                                    configAutoreload.value,

                                oscam_type:
                                    configOscamType.value,

                                oscam_conf:
                                    oscamConf.value,

                                oscam_server:
                                    oscamServer.value,

                                oscam_user:
                                    oscamUser.value,

                                wicardd_conf:
                                    wicarddConf.value
                            });

                        const response = await fetch(
                            '?module=readers'
                            + '&reader='
                            + encodeURIComponent(
                                configReader
                            )
                            + '&ajax=config-save',
                            {
                                method: 'POST',

                                headers: {
                                    'X-Requested-With':
                                        'XMLHttpRequest',

                                    'Content-Type':
                                        'application/x-www-form-urlencoded;charset=UTF-8'
                                },

                                body:
                                    body.toString()
                            }
                        );

                        const data =
                            await response.json();

                        if (
                            !response.ok
                            || !data.ok
                        ) {
                            throw new Error(
                                data.error
                                || 'Ошибка сохранения'
                            );
                        }

                        configStatus.textContent =
                            'Сохранено';

                        setTimeout(
                            () => {
                                window.location.reload();
                            },
                            400
                        );

                    } catch (error) {

                        configStatus.textContent =
                            error.message;

                    } finally {

                        configSave.disabled =
                            false;
                    }
                }
            );

            async function openReaderConfig(reader) {

                configReader = reader;

                configStatus.textContent = '';

                configModal.hidden = false;

                showPageLoader();

                try {

                    const response = await fetch(
                        '?module=readers'
                        + '&reader='
                        + encodeURIComponent(reader)
                        + '&ajax=config',
                        {
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest'
                            }
                        }
                    );

                    const data = await response.json();

                    if (
                        !response.ok
                        || !data.ok
                    ) {
                        throw new Error(
                            data.error
                            || 'Ошибка загрузки'
                        );
                    }

                    const c = data.config;

                    configTitle.textContent =
                        'Редактирование Reader #'
                        + c.reader;

                    configNumber.value =
                        c.reader;

                    configName.value =
                        c.name || '';

                    configAutoreload.value =
                        String(c.autoreload || 0);

                    configOscamType.innerHTML = '';

                    const oscamTypes =
                        Array.isArray(
                            c.oscam_types
                        )
                            ? c.oscam_types
                            : [];

                    const currentOscamType =
                        c.oscam_type || '';

                    if (
                        currentOscamType !== ''
                        && !oscamTypes.includes(
                            currentOscamType
                        )
                    ) {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            currentOscamType;

                        option.textContent =
                            currentOscamType
                            + ' (текущий)';

                        option.selected = true;

                        configOscamType.appendChild(
                            option
                        );
                    }

                    oscamTypes.forEach(
                        (type) => {

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                type;

                            option.textContent =
                                type;

                            option.selected =
                                type === currentOscamType;

                            configOscamType.appendChild(
                                option
                            );
                        }
                    );

                    oscamConf.value =
                        c.oscam_conf || '';

                    oscamServer.value =
                        c.oscam_server || '';

                    oscamUser.value =
                        c.oscam_user || '';

                    wicarddConf.value =
                        c.wicardd_conf || '';

                    updateConfigType();

                } catch (error) {

                    configStatus.textContent =
                        error.message;

                } finally {

                    hidePageLoader();

                }
            }




    enabledButton.addEventListener(
        'click',
        async () => {
            const currentEnabled =
                enabledButton.dataset.enabled === '1';

            const newEnabled =
                !currentEnabled;

            enabledButton.disabled = true;

            try {
                const body =
                    new URLSearchParams({
                        csrf_token:
                            csrfToken,

                        enabled:
                            newEnabled
                                ? '1'
                                : '0'
                    });

                const response = await fetch(
                    '?module=readers'
                    + '&reader='
                    + encodeURIComponent(reader)
                    + '&ajax=enabled',
                    {
                        method: 'POST',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',

                            'Content-Type':
                                'application/x-www-form-urlencoded;charset=UTF-8'
                        },

                        body:
                            body.toString()
                    }
                );

                const data =
                    await response.json();

                if (
                    !response.ok
                    || !data.ok
                ) {
                    throw new Error(
                        data.error
                        || 'Ошибка изменения состояния'
                    );
                }

                const enabled =
                    Number(data.enabled) === 1;

                const card = document.querySelector(
                    '.reader-card[data-reader="'
                    + CSS.escape(String(reader))
                    + '"]'
                );

                if (card) {
                    card.classList.toggle(
                        'reader-card-disabled',
                        !enabled
                    );
                }


                reboot.disabled = !enabled;

                enabledButton.dataset.enabled =
                    enabled ? '1' : '0';

                enabledButton.textContent =
                    enabled
                        ? 'Включен'
                        : 'Выключен';

                enabledButton.classList.toggle(
                    'on',
                    enabled
                );

                enabledButton.classList.toggle(
                    'off',
                    !enabled
                );
            } catch (error) {
                console.error(
                    'Reader enabled change failed',
                    error
                );

                alert(
                    'Ошибка изменения состояния Reader\n\n'
                    + error.message
                );
            } finally {
                enabledButton.disabled =
                    false;
            }
        }
    );



    reboot.addEventListener('click', async () => {
        if (!confirm('Действительно перезапустить oscam?')) {
            return;
        }

        try {
            reboot.disabled = true;

            const body = new URLSearchParams({
                csrf_token: csrfToken
            });

            const response = await fetch(
                '?module=readers'
                + '&reader=' + encodeURIComponent(reader)
                + '&ajax=reboot',
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: body.toString()
                }
            );

            const text = await response.text();

            if (!response.ok) {
                alert(
                    'Ошибка перезапуска OSCam\n\n'
                    + 'HTTP ' + response.status
                    + '\n\n'
                    + text
                );
                return;
            }

            await updateLog();

        } catch (e) {
            console.error('Reader reboot failed', e);

            alert(
                'Ошибка запроса Reboot\n\n'
                + e.message
            );
        } finally {
            reboot.disabled = false;
        }
    });

});
</script>