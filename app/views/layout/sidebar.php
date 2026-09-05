<?php

declare(strict_types=1);

/** @var string $module */

?>

<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <nav>
            <?php

                $current_user = current_user();

                $navigation = [
                    ['zayavki', 'Заявки', '☑'],
                    ['podkluchki', 'Подключения', '🔌'],
                    ['otkluchki', 'Отключки', '⊘'],
                    ['database', 'Абоненты', '👥'],
                    ['graph', 'График', '⌁'],
                    ['stat', 'Статистика', '▦'],
                    ['debtors', 'Должники', '₽'],
                    ['karandash', 'Карандаш', '✎'],
                    ['analog', 'Аналог', '▤'],
                    ['digital', 'Цифра', '▥'],
                    ['sms', 'SMS', '✉'],
                ];

                if ($current_user === 'admin') {
                    $navigation[] = [
                        'readers',
                        'Ридеры',
                        '◉',
                    ];
                    $navigation[] = [
                        'terminal',
                        'Terminal',
                        '⌨',
                    ];
                }
                
                if ($current_user  === 'admin' || $current_user === 'kassa') {
                    $navigation[] = [
                        'stroka',
                        'Строка',
                        '▰',
                    ];
                }

                if ($current_user !== 'sanya') {
                    $navigation[] = [
                        'money',
                        'Money',
                        '₽',
                    ];
                }

            ?>

            <?php foreach ($navigation as [$key, $label, $icon]): ?>
                <a class="nav-item <?= $module === $key ? 'active' : '' ?>" href="<?= e(url(['module' => $key])) ?>"><span><?= $icon ?></span><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <button class="backdrop" type="button" data-menu-close aria-label="Закрыть меню"></button>
