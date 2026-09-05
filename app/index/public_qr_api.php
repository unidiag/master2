<?php

declare(strict_types=1);

/** @var \PDO $pdo */

/*
 * Публичный API QR ящиков (?box=NNNN).
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */



/*
 * Публичный API QR ящиков.
 *
 * Пример:
 * /index.php?box=0014
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['box'])
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );
    header('Cache-Control: no-store');

    $qrcode = trim(
        (string) $_GET['box']
    );

    /*
     * QR всегда состоит ровно из четырёх цифр.
     */
    if (!preg_match('/^\d{4}$/', $qrcode)) {
        http_response_code(400);

        echo json_encode(
            [
                'error' => 'invalid_qrcode',
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    $qrRepository = new QrRepository($pdo);

    $qr = $qrRepository->findByQrcode(
        $qrcode
    );

    if ($qr === null) {
        http_response_code(404);

        echo json_encode(
            [
                'error' => 'not_found',
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    echo json_encode(
        [
            'address' => (string) $qr['address'],
            'entrance' => (int) $qr['entrance'],
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}
