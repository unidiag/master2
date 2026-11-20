<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/bootstrap.php';


if (empty($_SESSION['auth_username'])) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

$distributorName = trim(
    (string) (
        $_GET['distributor']
        ?? ''
    )
);

if ($distributorName === '') {
    http_response_code(400);
    exit('Не указан дистрибьютор.');
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
    exit('Дистрибьютор не найден.');
}

$reportTpl = trim(
    (string) (
        $distributor['report_tpl']
        ?? ''
    )
);

if ($reportTpl === '') {
    http_response_code(404);
    exit('Для дистрибьютора не задан шаблон отчёта.');
}

if (!preg_match(
    '/^[a-zA-Z0-9_-]+\.html$/',
    $reportTpl
)) {
    http_response_code(400);
    exit('Некорректное имя шаблона.');
}

$templateFile =
    __DIR__
    . '/runtime/reports_tpl/'
    . $reportTpl;

if (!is_file($templateFile)) {
    http_response_code(404);

    exit(
        'Шаблон отчёта не найден: '
        . htmlspecialchars(
            $reportTpl,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$html = file_get_contents(
    $templateFile
);

if ($html === false) {
    http_response_code(500);
    exit('Не удалось прочитать шаблон.');
}

$dateEnd = trim(
    (string) (
        $_GET['date_end']
        ?? ''
    )
);

$subscribers =
    subscribers_count_by_date(
        $pdo,
        $config,
        $dateEnd !== ''
            ? $dateEnd
            : null
    );

$distributorFullname = trim(
    (string) (
        $distributor['fullname']
        ?? $distributor['name']
        ?? ''
    )
);

$stampFile =
    __DIR__
    . '/assets/stamp_signature_500x500.png';

$stampData = '';

if (is_file($stampFile)) {
    $stampContent = file_get_contents(
        $stampFile
    );

    if ($stampContent !== false) {
        $stampData =
            'data:image/png;base64,'
            . base64_encode(
                $stampContent
            );
    }
}




$headerFile =
    __DIR__
    . '/runtime/reports_tpl/header.html';

if (!is_file($headerFile)) {
    http_response_code(500);
    exit('Файл header.html не найден.');
}

$headerHtml = file_get_contents(
    $headerFile
);

if ($headerHtml === false) {
    http_response_code(500);
    exit('Не удалось прочитать header.html.');
}



$signatureHtml = file_get_contents(__DIR__ . '/runtime/reports_tpl/nosign.html');

if (!isset($_GET['nosign'])) {
    $signatureFile =
        __DIR__
        . '/runtime/reports_tpl/signature.html';

    if (!is_file($signatureFile)) {
        http_response_code(500);
        exit('Файл signature.html не найден.');
    }

    $signatureHtml = file_get_contents(
        $signatureFile
    );

    if ($signatureHtml === false) {
        http_response_code(500);
        exit('Не удалось прочитать signature.html.');
    }
}



$html = str_replace(
    ['{{header}}', '{{signature}}'],
    [$headerHtml, $signatureHtml],
    $html
);

$beta = 600;

$html = str_replace(
    [
        '{{stamp_signature}}',
        '{{distributor_fullname}}',
        '{{date_start}}',
        '{{date_end}}',
        '{{total_start}}',
        '{{total_end}}',
        '{{total_avg}}',
        '{{beta_total_start}}',
        '{{beta_total_end}}',
        '{{beta_total_avg}}',
        '{{total_connected}}',
        '{{total_disconnected}}',
    ],
    [
        $stampData,
        $distributorFullname,
        $subscribers['date_start'],
        $subscribers['date_end'],
        (string) $subscribers['total_start'],
        (string) $subscribers['total_end'],
        (string) $subscribers['total_avg'],
        (string) $subscribers['total_start']+$beta,
        (string) $subscribers['total_end']+$beta,
        (string) $subscribers['total_avg']+$beta,
        (string) $subscribers['total_connected'],
        (string) $subscribers['total_disconnected'],
    ],
    $html
);



$dompdf = new \Dompdf\Dompdf();

$dompdf->loadHtml(
    $html,
    'UTF-8'
);

$dompdf->setPaper(
    'A4',
    'portrait'
);

$dompdf->render();

$fileName =
    'report-'
    . preg_replace(
        '/[^a-zA-Z0-9_-]+/',
        '-',
        $reportTpl
    )
    . '.pdf';

$pdfData = $dompdf->output();

$dompdf->stream(
    $fileName,
    [
        'Attachment' => false,
    ]
);

exit;