<?php

declare(strict_types=1);



function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(array $params = []): string
{
    $query = http_build_query($params);
    return 'index.php' . ($query !== '' ? '?' . $query : '');
}

function redirect(array $params = []): void
{
    header('Location: ' . url($params), true, 303);
    exit;
}



function telegram_html($value): string
{
    return htmlspecialchars(
        trim((string) $value),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function format_unix_time($value, $format = 'd.m.Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = (int) $value;

    if ($timestamp <= 0) {
        return '—';
    }

    return date($format, $timestamp);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('Срок действия формы истёк. Вернитесь назад и повторите действие.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($items) ? $items : [];
}

function post_string(string $name, int $maxLength = 255): string
{
    $value = trim((string)($_POST[$name] ?? ''));
    return mb_substr($value, 0, $maxLength);
}

function get_string(string $name, int $maxLength = 255): string
{
    $value = trim((string)($_GET[$name] ?? ''));
    return mb_substr($value, 0, $maxLength);
}

function positive_int($value, int $default = 1): int
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $int === false ? $default : $int;
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d.m.Y H:i', $timestamp) : $value;
}

function is_done(array $row): bool
{
    return trim((string)($row['result'] ?? '')) !== '';
}

function current_user(): string
{
    return trim(
        (string) ($_SESSION['auth_username'] ?? '')
    );
}



//  █████╗ ██╗   ██╗████████╗██╗  ██╗
// ██╔══██╗██║   ██║╚══██╔══╝██║  ██║
// ███████║██║   ██║   ██║   ███████║
// ██╔══██║██║   ██║   ██║   ██╔══██║
// ██║  ██║╚██████╔╝   ██║   ██║  ██║
// ╚═╝  ╚═╝ ╚═════╝    ╚═╝   ╚═╝  ╚═╝

function auth_session_cookie_options(int $expires = 0): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}


function remember_cookie_name(): string
{
    return 'master2_remember';
}

function remember_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' =>
            !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function create_remember_token(
    string $username,
    int $expires,
    string $secret
): string {
    $payload = $username . '|' . $expires;

    $signature = hash_hmac(
        'sha256',
        $payload,
        $secret
    );

    return base64_encode(
        $payload . '|' . $signature
    );
}

function set_remember_cookie(
    string $username,
    int $rememberDays,
    string $secret
): void {
    if ($username === '' || $secret === '') {
        return;
    }

    $expires =
        time()
        + ($rememberDays * 86400);

    $token = create_remember_token(
        $username,
        $expires,
        $secret
    );

    setcookie(
        remember_cookie_name(),
        $token,
        remember_cookie_options($expires)
    );
}

function delete_remember_cookie(): void
{
    setcookie(
        remember_cookie_name(),
        '',
        remember_cookie_options(
            time() - 3600
        )
    );
}

function restore_remembered_user(
    array $config
): bool {
    $cookieName = remember_cookie_name();

    $token = (string) (
        $_COOKIE[$cookieName]
        ?? ''
    );

    if ($token === '') {
        return false;
    }

    $decoded = base64_decode(
        $token,
        true
    );

    if ($decoded === false) {
        delete_remember_cookie();

        return false;
    }

    $parts = explode(
        '|',
        $decoded,
        3
    );

    if (count($parts) !== 3) {
        delete_remember_cookie();

        return false;
    }

    [
        $username,
        $expiresRaw,
        $signature,
    ] = $parts;

    $username = trim($username);
    $expires = (int) $expiresRaw;

    if (
        $username === ''
        || $expires <= time()
        || $signature === ''
    ) {
        delete_remember_cookie();

        return false;
    }

    $secret = (string) (
        $config['remember_secret']
        ?? ''
    );

    if ($secret === '') {
        return false;
    }

    $users = $config['users'] ?? [];

    if (
        !is_array($users)
        || !isset($users[$username])
        || !is_array($users[$username])
    ) {
        delete_remember_cookie();

        return false;
    }

    $payload =
        $username
        . '|'
        . $expires;

    $expectedSignature = hash_hmac(
        'sha256',
        $payload,
        $secret
    );

    if (
        !hash_equals(
            $expectedSignature,
            $signature
        )
    ) {
        delete_remember_cookie();

        return false;
    }

    session_regenerate_id(true);

    $_SESSION['auth_username'] =
        $username;

    $_SESSION['auth_remember'] =
        true;

    return true;
}

function refresh_auth_session_cookie(int $rememberDays): void
{
    if (
        session_status() !== PHP_SESSION_ACTIVE
        || empty($_SESSION['auth_remember'])
    ) {
        return;
    }

    $expires = time() + ($rememberDays * 86400);

    setcookie(
        session_name(),
        session_id(),
        auth_session_cookie_options($expires)
    );
}


function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' =>
                    time() - 3600,

                'path' =>
                    $cookie['path']
                    ?: '/',

                'domain' =>
                    $cookie['domain']
                    ?: '',

                'secure' =>
                    (bool) $cookie['secure'],

                'httponly' =>
                    (bool) $cookie['httponly'],

                'samesite' =>
                    'Lax',
            ]
        );
    }

    delete_remember_cookie();

    session_destroy();

    redirect([]);
}


function require_auth(
    array $config,
    TelegramService $telegram,
    array $telegramConfig
): void {
    if (!($config['enabled'] ?? false)) {
        return;
    }

    $rememberDays = max(
        1,
        min(365, (int) ($config['remember_days'] ?? 30))
    );

    $rememberLifetime = $rememberDays * 86400;


    /*
    * Выход нужно обрабатывать до проверки текущей авторизации.
    */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $authAction = trim(
            (string) ($_POST['auth_action'] ?? '')
        );

        if ($authAction === 'logout') {
            verify_csrf();
            logout_user();
        }
    }

    $rememberSecret = (string) (
        $config['remember_secret']
        ?? ''
    );

    /*
    * Если обычная PHP-сессия пропала,
    * пробуем восстановить пользователя
    * из долговременной remember-cookie.
    */
    if (
        empty($_SESSION['auth_username'])
        && restore_remembered_user($config)
    ) {
        /*
        * Сессия восстановлена.
        */
    }

    if (!empty($_SESSION['auth_username'])) {
        if (!empty($_SESSION['auth_remember'])) {
            set_remember_cookie(
                (string) $_SESSION['auth_username'],
                $rememberDays,
                $rememberSecret
            );
        }

        refresh_auth_session_cookie(
            $rememberDays
        );

        return;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $authAction = trim(
            (string) ($_POST['auth_action'] ?? '')
        );

        if ($authAction === 'login') {
            $username = trim(
                (string) ($_POST['username'] ?? '')
            );

            $password = (string) (
                $_POST['password'] ?? ''
            );

            $remember = isset($_POST['remember'])
                && (string) $_POST['remember'] === '1';

            $users = $config['users'] ?? [];

            $user = is_array($users)
                ? ($users[$username] ?? null)
                : null;

            $passwordHash = is_array($user)
                ? (string) ($user['password_hash'] ?? '')
                : '';

            if (
                $username !== ''
                && $passwordHash !== ''
                && password_verify($password, $passwordHash)
            ) {
                session_regenerate_id(true);

                $_SESSION['auth_username'] = $username;
                $_SESSION['auth_remember'] = $remember;

                if ($remember) {
                    setcookie(
                        session_name(),
                        session_id(),
                        auth_session_cookie_options(
                            time() + $rememberLifetime
                        )
                    );

                    set_remember_cookie(
                        $username,
                        $rememberDays,
                        (string) (
                            $config['remember_secret']
                            ?? ''
                        )
                    );
                } else {
                    setcookie(
                        session_name(),
                        session_id(),
                        auth_session_cookie_options()
                    );

                    delete_remember_cookie();
                }

                /*
                 * Отправляем уведомление о входе.
                 * Ошибка Telegram не должна мешать авторизации.
                 */
                try {
                    $telegramEnabled = (bool) (
                        $telegramConfig['enabled'] ?? false
                    );

                    $chatIds = $telegramConfig['chat_ids'] ?? [];

                    if (
                        $telegramEnabled
                        && is_array($chatIds)
                        && $chatIds
                    ) {
                        $ip = client_ip();

                        $userAgent = trim(
                            (string) (
                                $_SERVER['HTTP_USER_AGENT'] ?? ''
                            )
                        );

                        if (
                            mb_strlen($userAgent, 'UTF-8') > 200
                        ) {
                            $userAgent = mb_substr(
                                $userAgent,
                                0,
                                200,
                                'UTF-8'
                            );
                        }

                        $message =
                            "🔐 <b>Вход на сайт Master2</b>\n\n"
                            . '<b>Пользователь:</b> '
                            . telegram_html($username)
                            . "\n"
                            . '<b>Время:</b> '
                            . telegram_html(
                                date('d.m.Y H:i:s')
                            );

                        if ($ip !== '') {
                            $message .=
                                "\n"
                                . '<b>IP:</b> '
                                . telegram_html($ip);
                        }

                        if ($userAgent !== '') {
                            $message .=
                                "\n"
                                . '<b>Устройство:</b> '
                                . telegram_html($userAgent);
                        }

                        $telegram->sendToMany(
                            $chatIds,
                            $message
                        );
                    }
                } catch (Throwable $exception) {
                    error_log(
                        'Telegram login notification error: '
                        . $exception->getMessage()
                    );
                }

                redirect([
                    'module' =>
                        get_string('module', 30)
                        ?: 'zayavki',
                ]);
            }

            $error = 'Неверное имя пользователя или пароль.';
        }
    }

    http_response_code(401);

    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <title>Авторизация</title>

        <link
            rel="stylesheet"
            href="assets/app.css?v=1"
        >
    </head>
    <body>
        <main class="auth-page">
            <form method="post" class="auth-form">
                <h1>Вход</h1>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <input
                    type="hidden"
                    name="auth_action"
                    value="login"
                >

                <label>
                    Пользователь

                    <input
                        class="input"
                        type="text"
                        name="username"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </label>

                <label>
                    Пароль

                    <input
                        class="input"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </label>

                <label class="auth-remember">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        <?= isset($_POST['remember']) ? 'checked' : '' ?>
                    >

                    <span>Запомнить меня</span>
                </label>                

                <button
                    class="button primary full"
                    type="submit"
                >
                    Войти
                </button>
            </form>
        </main>
    </body>
    </html>
    <?php

    exit;
}





function telegram_notify(
    TelegramService $telegram,
    array $telegramConfig,
    string $message
): void {
    if (!($telegramConfig['enabled'] ?? false)) {
        return;
    }

    $chatIds = $telegramConfig['chat_ids'] ?? [];

    if (!is_array($chatIds) || !$chatIds) {
        return;
    }

    try {
        $results = $telegram->sendToMany(
            $chatIds,
            $message
        );

        foreach ($results as $chatId => $sent) {
            if (!$sent) {
                error_log(
                    'Telegram notification failed for chat_id: '
                    . $chatId
                );
            }
        }
    } catch (Throwable $exception) {
        error_log(
            'Telegram notification error: '
            . $exception->getMessage()
        );
    }
}



function send_notification_sms(
    PDO $pdo,
    array $config,
    string $message,
    string $abonent = '',
    string $address = ''
): void {
    $smsConfig = $config['mts_sms'] ?? [];

    if (!(bool) ($smsConfig['enabled'] ?? false)) {
        return;
    }

    $phone = trim(
        (string) ($smsConfig['notification_phone'] ?? '')
    );

    if ($phone === '') {
        return;
    }

    try {
        $smsService = new MtsSmsService(
            $smsConfig
        );

        $messageId = $smsService->send(
            $phone,
            $message
        );

        /*
         * Сохраняем служебное SMS
         * в общий журнал master_sms.
         */
        $statement = $pdo->prepare(
            '
            INSERT INTO master_sms (
                abonent,
                address,
                phone,
                message,
                message_id
            ) VALUES (
                :abonent,
                :address,
                :phone,
                :message,
                :message_id
            )
            '
        );

        $statement->execute([
            'abonent' => $abonent,
            'address' => $address,
            'phone' => preg_replace(
                '/\D+/',
                '',
                $phone
            ),
            'message' => $message,
            'message_id' => $messageId,
        ]);
    } catch (Throwable $exception) {
        /*
         * Ошибка SMS не должна мешать
         * созданию заявки или подключения.
         */
        error_log(
            'SMS notification error: '
            . $exception->getMessage()
        );
    }
}









