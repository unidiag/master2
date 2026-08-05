<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Master',
        'timezone' => 'Europe/Minsk',
        'base_path' => '',
        'per_page' => 30,
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'master',
        'user' => 'master',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8',
    ],

    // Создать: php -r "echo password_hash('пароль', PASSWORD_DEFAULT), PHP_EOL;"
    'auth' => [
        'enabled' => true,
        'remember_days' => 30,

        'users' => [
            'admin' => [
                'password_hash' => '$2y$10$DFcRVme6axQHZ0umPYyZQeplhP8Ieikfin8KkaQlZIXPhZQ6ZVHcW', // 654321
            ],

            'sanya' => [
                'password_hash' => 'ХЕШ_ПАРОЛЯ_SANYA',
            ],

            'kassa' => [
                'password_hash' => 'ХЕШ_ПАРОЛЯ_KASSA',
            ],
        ],
    ],

    'telegram' => [
        'enabled' => true,
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_ids' => ['298461914'],
    ],
];
