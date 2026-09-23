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

    'stat' => [
        'nearby_debtor_min_debt' => 30, // сумма задолженности, которая начинает играть в системе
    ],

    // Создать: php -r "echo password_hash('пароль', PASSWORD_DEFAULT), PHP_EOL;"
    'auth' => [
        'enabled' => true,
        'remember_days' => 30,
        'remember_secret' => 'СЮДА_ДЛИННАЯ_СЛУЧАЙНАЯ_СТРОКА', // php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

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
        'chat_ids' => ['12312312312'],
    ],

    'channels' => [
        'devices' => [
            '192.168.1.30',
            '192.168.1.31',
        ],
        'username' => 'admin',
        'password' => '',
    ],

    'digital' => [
        'astras' => [
            'admin:password@192.168.1.15:8000',
            'admin:password@192.168.1.16:8000',
            'admin:password@192.168.1.17:8000',
        ],
        'distributors' => [
            [
                'name' => 'Собственный',
                'fullname' => "ООО «ТриАнда»",
                'mail' => 'info@trianda.by',
                'phone' => "+375213368388",
                'report_tpl' => "",
            ],
            [
                'name' => 'Бета Телесеть',
                'fullname' => 'Унитарное предприятие «БетаТелесеть»',
                'mail' => 'manager@betatv.by',
                'phone' => "+375172087252",
                'report_tpl' => "base",
            ],
        ],
    ],


    'mail' => [
        'host' => 'smtp.yandex.ru',
        'port' => 465,
        'username' => 'info@exmple.by',
        'password' => 'ПАРОЛЬ_ПРИЛОЖЕНИЯ',
        'from' => 'info@example.by',
        'from_name' => 'Иван Иванов',
    ],   



    'mts_sms' => [
        'enabled' => true,
        'base_url' => 'https://api.communicator.mts.by',
        'login' => 'login',
        'password' => 'password',
        'client_id' => 1234,
        'alpha_name' => 'ALPHA',
        'ttl' => 300,

        /*
        * SMS-уведомления о новых заявках
        * и подключениях.
        */
        'notification_phone' => '+375291234567', 
    ],

    'stroka' => [
        'reboot_url' => 'http://192.168.1.16:8088/?reboot=1',
        'reboot_timeout' => 5,
    ],

];
