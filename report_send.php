<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/bootstrap.php';

header(
    'Content-Type: application/json; charset=UTF-8'
);

if (empty($_SESSION['auth_username'])) {
    http_response_code(403);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Доступ запрещён.',
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$distributorName = trim(
    (string) (
        $_POST['distributor']
        ?? ''
    )
);

if ($distributorName === '') {
    http_response_code(400);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Не указан дистрибьютор.',
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$distributors = isset(
    $config['digital']['distributors']
) && is_array(
    $config['digital']['distributors']
)
    ? $config['digital']['distributors']
    : [];

$distributor = null;

foreach ($distributors as $item) {
    if (!is_array($item)) {
        continue;
    }

    $name = trim(
        (string) (
            $item['name']
            ?? ''
        )
    );

    if ($name === $distributorName) {
        $distributor = $item;
        break;
    }
}

if ($distributor === null) {
    http_response_code(404);

    echo json_encode(
        [
            'ok' => false,
            'error' => 'Дистрибьютор не найден.',
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$dateEnd = trim(
    (string) (
        $_POST['date_end']
        ?? ''
    )
);

try {
    $reportService = new ReportService(
        $pdo,
        $config,
        __DIR__
    );

    $result = $reportService->send(
        $distributor,
        $dateEnd !== ''
            ? $dateEnd
            : null,
        (string) $_SESSION['auth_username'],
        client_ip(),
        trim(
            (string) (
                $_SERVER['HTTP_USER_AGENT']
                ?? ''
            )
        )
    );
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' =>
                'Ошибка формирования отчёта: '
                . $exception->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if (empty($result['ok'])) {
    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' =>
                'Ошибка отправки: '
                . (
                    $result['error']
                    ?? 'Неизвестная ошибка.'
                ),
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

echo json_encode(
    [
        'ok' => true,
        'message' =>
            'Отчёт отправлен на '
            . (
                $result['email']
                ?? ''
            ),
    ],
    JSON_UNESCAPED_UNICODE
);
