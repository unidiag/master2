<?php

declare(strict_types=1);

/*
 * AJAX-обработчики app-index.php.
 *
 * Код перенесён из исходного app-index.php без изменения логики.
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && get_string('ajax', 30) === 'subscriber_lookup'
) {
    $address = get_string('address', 100);

    header(
        'Content-Type: application/json; charset=utf-8'
    );
    header('Cache-Control: no-store');

    $subscriber = $subscribers->findLatestByAddress(
        $address
    );

    $ticketsCount = $tickets->countByAddress(
        $address
    );

    echo json_encode(
        [
            'found' => $subscriber !== null,
            'subscriber' => $subscriber,
            'tickets_count' => $ticketsCount,
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && post_string(
        'ajax',
        30
    ) === 'database_import'
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-store'
    );

    try {
        verify_csrf();

        if (
            !isset($_FILES['db'])
            || !is_array($_FILES['db'])
        ) {
            throw new RuntimeException(
                'Файл не передан.'
            );
        }

        $file = $_FILES['db'];

        $error = (int) (
            $file['error']
            ?? UPLOAD_ERR_NO_FILE
        );

        if ($error !== UPLOAD_ERR_OK) {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'Файл превышает допустимый размер.';
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $message = 'Файл был загружен не полностью.';
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $message = 'Файл не выбран.';
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = 'На сервере отсутствует временная директория.';
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'Не удалось записать файл на диск.';
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $message = 'Загрузка файла остановлена расширением PHP.';
                    break;

                default:
                    $message = 'Ошибка загрузки файла.';
                    break;
            }

            throw new RuntimeException(
                $message
            );
        }

        $tmpName = (string) (
            $file['tmp_name']
            ?? ''
        );

        if (
            $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            throw new RuntimeException(
                'Получен некорректный загруженный файл.'
            );
        }

        $result =
            $subscribers->importDatabaseFile(
                $tmpName
            );

        echo json_encode(
            [
                'success' => true,
                'inserted' =>
                    $result['inserted'],
                'skipped' =>
                    $result['skipped'],
                'update' =>
                    $result['update'],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    } catch (Throwable $exception) {
        http_response_code(422);

        echo json_encode(
            [
                'success' => false,
                'error' =>
                    $exception->getMessage(),
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    }

    exit;
}



if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && post_string(
        'ajax',
        30
    ) === 'save_apartment_group'
) {
    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store');

    try {
        verify_csrf();

        $house = post_string(
            'house',
            255
        );

        $groupSize = (int) (
            $_POST['group_size']
            ?? 4
        );

        if ($house === '') {
            throw new InvalidArgumentException(
                'Название дома не указано.'
            );
        }

        if (
            $groupSize < 0
            || $groupSize > 6
        ) {
            throw new InvalidArgumentException(
                'Допустимое значение — от 0 до 6.'
            );
        }

        $housesRepository->saveSize(
            $house,
            $groupSize
        );

        echo json_encode(
            [
                'success' => true,
                'house' => $house,
                'group_size' => $groupSize,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    } catch (Throwable $exception) {
        http_response_code(422);

        echo json_encode(
            [
                'success' => false,
                'error' => $exception->getMessage(),
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    exit;
}

$subscriberDebt = 0.0;
