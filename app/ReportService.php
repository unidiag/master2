<?php



declare(strict_types=1);




class ReportService
{
    private $pdo;
    private $config;
    private $baseDir;

    public function __construct(
        PDO $pdo,
        array $config,
        string $baseDir
    ) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->baseDir = $baseDir;
    }

    public function send(
        array $distributor,
        ?string $dateEnd = null,
        string $username = '',
        string $ip = '',
        string $userAgent = ''
    ): array {
        $distributorName = trim(
            (string) (
                $distributor['name']
                ?? ''
            )
        );

        if ($distributorName === '') {
            return [
                'ok' => false,
                'error' => 'Не указано имя дистрибьютора.',
            ];
        }

        $recipientMail = trim(
            (string) (
                $distributor['mail']
                ?? ''
            )
        );

        if (
            $recipientMail === ''
            || !filter_var(
                $recipientMail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return [
                'ok' => false,
                'error' =>
                    'У дистрибьютора не указан корректный e-mail.',
            ];
        }

        $reportTpl = trim(
            (string) (
                $distributor['report_tpl']
                ?? ''
            )
        );

        if (
            !preg_match(
                '/^[a-zA-Z0-9_-]+\.html$/',
                $reportTpl
            )
        ) {
            return [
                'ok' => false,
                'error' =>
                    'Некорректный шаблон отчёта.',
            ];
        }

        $templateFile =
            $this->baseDir
            . '/runtime/reports_tpl/'
            . $reportTpl;

        if (!is_file($templateFile)) {
            return [
                'ok' => false,
                'error' =>
                    'Шаблон отчёта не найден.',
            ];
        }

        $html = file_get_contents(
            $templateFile
        );

        if ($html === false) {
            return [
                'ok' => false,
                'error' =>
                    'Не удалось прочитать шаблон.',
            ];
        }

        $subscribers =
            subscribers_count_by_date(
                $this->pdo,
                $this->config,
                $dateEnd
            );

        $distributorFullname = trim(
            (string) (
                $distributor['fullname']
                ?? $distributorName
            )
        );

        $stampFile =
            $this->baseDir
            . '/assets/stamp_signature_500x500.png';

        $stampData = '';

        if (is_file($stampFile)) {
            $stampContent =
                file_get_contents(
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
            $this->baseDir
            . '/runtime/reports_tpl/header.html';

        if (!is_file($headerFile)) {
            return [
                'ok' => false,
                'error' =>
                    'Файл header.html не найден.',
            ];
        }

        $headerHtml =
            file_get_contents(
                $headerFile
            );

        if ($headerHtml === false) {
            return [
                'ok' => false,
                'error' =>
                    'Не удалось прочитать header.html.',
            ];
        }

        $signatureFile =
            $this->baseDir
            . '/runtime/reports_tpl/signature.html';

        if (!is_file($signatureFile)) {
            return [
                'ok' => false,
                'error' =>
                    'Файл signature.html не найден.',
            ];
        }

        $signatureHtml =
            file_get_contents(
                $signatureFile
            );

        if ($signatureHtml === false) {
            return [
                'ok' => false,
                'error' =>
                    'Не удалось прочитать signature.html.',
            ];
        }

        $html = str_replace(
            [
                '{{header}}',
                '{{signature}}',
            ],
            [
                $headerHtml,
                $signatureHtml,
            ],
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
                (string) (
                    $subscribers['total_start']
                    + $beta
                ),
                (string) (
                    $subscribers['total_end']
                    + $beta
                ),
                (string) (
                    $subscribers['total_avg']
                    + $beta
                ),
                (string) $subscribers['total_connected'],
                (string) $subscribers['total_disconnected'],
            ],
            $html
        );

        try {
            $dompdf =
                new \Dompdf\Dompdf();

            $dompdf->loadHtml(
                $html,
                'UTF-8'
            );

            $dompdf->setPaper(
                'A4',
                'portrait'
            );

            $dompdf->render();

            $pdfData =
                $dompdf->output();
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'error' =>
                    'Ошибка формирования PDF: '
                    . $exception->getMessage(),
            ];
        }

        $fileName =
            'TriAnda_'
            . $subscribers['date_start']
            . '-'
            . $subscribers['date_end']
            . '.pdf';

        $subject =
            'Отчёт о количестве абонентов за '
            . $subscribers['date_start']
            . ' - '
            . $subscribers['date_end'];

        $mailBody =
            '<p>Добрый день, '
            . htmlspecialchars(
                $distributorFullname,
                ENT_QUOTES,
                'UTF-8'
            )
            . '!</p>'
            . '<p>'
            . 'Направляем отчёт о количестве абонентов '
            . 'за период с '
            . htmlspecialchars(
                $subscribers['date_start'],
                ENT_QUOTES,
                'UTF-8'
            )
            . ' по '
            . htmlspecialchars(
                $subscribers['date_end'],
                ENT_QUOTES,
                'UTF-8'
            )
            . '.</p>'
            . '<p>Отчёт находится во вложении.</p>'
            . '<br /><br /><hr /><p>'
            . 'С уважением,<br />'
            . 'ООО «ТриАнда»<br />'
            . '8 (02133) 6-83-88'
            . '</p>';

        $result = send_mail_smtp(
            $this->config,
            $recipientMail,
            $subject,
            $mailBody,
            $pdfData,
            $fileName
        );

        $success =
            !empty($result['ok'])
                ? 1
                : 0;

        $error = '';

        if (
            !$success
            && isset($result['error'])
        ) {
            $error = trim(
                (string) $result['error']
            );
        }

        $statement =
            $this->pdo->prepare(
                '
                INSERT INTO master_reports (
                    ip,
                    user_agent,
                    username,
                    distributor,
                    email,
                    date_start,
                    date_end,
                    success,
                    error
                )
                VALUES (
                    :ip,
                    :user_agent,
                    :username,
                    :distributor,
                    :email,
                    :date_start,
                    :date_end,
                    :success,
                    :error
                )
                '
            );

        $statement->execute([
            ':ip' =>
                $ip,
            ':user_agent' =>
                $userAgent,
            ':username' =>
                $username,
            ':distributor' =>
                $distributorName,
            ':email' =>
                $recipientMail,
            ':date_start' =>
                DateTimeImmutable::createFromFormat(
                    'd.m.Y',
                    $subscribers['date_start']
                )->format('Y-m-d'),
            ':date_end' =>
                DateTimeImmutable::createFromFormat(
                    'd.m.Y',
                    $subscribers['date_end']
                )->format('Y-m-d'),
            ':success' =>
                $success,
            ':error' =>
                $error !== ''
                    ? $error
                    : null,
        ]);

        return [
            'ok' => (bool) $success,
            'error' => $error,
            'email' => $recipientMail,
            'distributor' => $distributorName,
            'date_start' =>
                $subscribers['date_start'],
            'date_end' =>
                $subscribers['date_end'],
        ];
    }

    public function wasSent(
        string $distributor,
        string $dateEnd
    ): bool {
        $statement =
            $this->pdo->prepare(
                '
                SELECT id
                FROM master_reports
                WHERE distributor = :distributor
                AND date_end = :date_end
                AND success = 1
                LIMIT 1
                '
            );

        $statement->execute([
            ':distributor' =>
                $distributor,
            ':date_end' =>
                $dateEnd,
        ]);

        return $statement->fetchColumn()
            !== false;
    }
}