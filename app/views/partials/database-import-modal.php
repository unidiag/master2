<dialog
    class="modal database-import-modal"
    id="database-import-modal"
>
    <form
        id="database-import-form"
        enctype="multipart/form-data"
    >
        <div class="modal-head">
            <h2>Импорт базы абонентов</h2>

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
            name="ajax"
            value="database_import"
        >

        <label>
            Файл базы

            <input
                class="input"
                type="file"
                name="db"
                id="database-import-file"
                required
            >
        </label>

        <div
            class="database-import-progress"
            id="database-import-progress"
            hidden
        >
            <div class="database-import-progress__header">
                <span id="database-import-progress-text">
                    Загрузка…
                </span>

                <strong
                    id="database-import-progress-percent"
                >
                    0%
                </strong>
            </div>

            <div class="database-import-progress__track">
                <div
                    class="database-import-progress__bar"
                    id="database-import-progress-bar"
                ></div>
            </div>
        </div>

        <div
            class="database-import-result"
            id="database-import-result"
            hidden
        ></div>
    </form>
</dialog>
