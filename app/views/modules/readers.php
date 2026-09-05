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
    class="reader-card<?= $isActive ? ' active' : '' ?>"
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

    <?php if (!empty($reader['autoreload'])): ?>
        <span
            class="reader-autoreload"
            title="Автоматический рестарт включён"
        >
            ↻
        </span>
    <?php endif; ?>

</div>

<?php endforeach; ?>

</div>


<?php if ($selected !== null): ?>

<?php
$isRunning = !empty(
    $selected['running']
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


    <button
        type="button"
        class="button reader-reboot"
        id="readerReboot"
    >
        Reboot
    </button>    


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





<script>
document.addEventListener('DOMContentLoaded', () => {
    const autoUpdate = document.getElementById('readerAutoUpdate');
    const logBody = document.getElementById('readerLogBody');
    const search = document.getElementById('readerLogSearch');
    const rows = document.getElementById('readerLogRows');
    const reboot = document.getElementById('readerReboot');
    const csrfToken = document.getElementById('readerCsrfToken').value;

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