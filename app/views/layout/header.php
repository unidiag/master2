<?php
declare(strict_types=1);

/** @var string $title */
/** @var string $module */
?><header class="topbar">
    <button class="icon-button menu-button" type="button" data-menu-toggle aria-label="Открыть меню">☰</button>
    <a class="brand" href="<?= e(url(['module' => 'zayavki'])) ?>">Master2</a>
    <div class="topbar-title"><?= e($title) ?></div>
    <form
        method="post"
        class="user-badge user-badge--logout"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="auth_action"
            value="logout"
        >

        <button
            type="submit"
            class="user-badge__button"
            title="Выйти"
            aria-label="Выйти из системы"
        >
            <?= e(current_user()) ?>
        </button>
    </form>
</header>
