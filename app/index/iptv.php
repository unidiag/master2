<?php

declare(strict_types=1);

/** @var string $module */
/** @var PDO $pdo */
/** @var array $config */
/** @var SubscriberRepository $subscribers */

/**
 * Сопряжение IPTV-аккаунта с лицевым счётом Master2.
 * Подключается после app/index/context.php.
 * Требует IptvService с методами user(), setField(), setPackages(), deleteSessions().
 */
if ($module === 'iptv') {
    $allowedUsers = $config['iptv']['allowed_users'] ?? ['admin'];
    if (!is_array($allowedUsers) || !in_array(current_user(), $allowedUsers, true)) {
        http_response_code(403);
        exit('Доступ к сопряжению IPTV запрещён.');
    }

    $iptvData = [
        'login' => '',
        'personal' => '',
        'user' => null,
        'subscriber' => null,
        'operations' => [],
        'error' => '',
        'success' => '',
    ];

    $iptvLogin = '';
    $iptvPersonal = '';
    $iptvOperation = '';
    $isPairing = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

    // Журналируем операцию до первого изменяющего вызова внешнего API.
    $iptvAuditId = null;
    $iptvAuditStage = '';
    $iptvAuditStarted = false;

    $iptvAuditCreate = static function (
        PDO $pdo,
        string $personal,
        string $login,
        string $action,
        string $billingBefore,
        ?string $balanceBefore,
        string $pairAmount,
        string $packages,
        string $operator
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO master_iptv
                (personal, iptv_login, action, status, billing_before, billing_after,
                 balance_before, pair_amount, packages, operator, comment)
             VALUES
                (:personal, :login, :action, :status, :billing_before, :billing_after,
                 :balance_before, :pair_amount, :packages, :operator, :comment)'
        );
        $stmt->execute([
            ':personal' => $personal,
            ':login' => $login,
            ':action' => $action,
            ':status' => 'error',
            ':billing_before' => $billingBefore,
            ':billing_after' => $billingBefore,
            ':balance_before' => $balanceBefore,
            ':pair_amount' => $pairAmount,
            ':packages' => $packages,
            ':operator' => $operator,
            ':comment' => 'Операция подготовлена; внешние изменения ещё не начинались.',
        ]);
        return (int) $pdo->lastInsertId();
    };

    $iptvAuditUpdate = static function (
        PDO $pdo,
        int $id,
        string $status,
        string $comment,
        ?string $billingAfter = null,
        ?string $balanceAfter = null
    ): void {
        $stmt = $pdo->prepare(
            'UPDATE master_iptv SET
                status = :status,
                comment = :comment,
                billing_after = COALESCE(:billing_after, billing_after),
                balance_after = COALESCE(:balance_after, balance_after)
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':comment' => $comment,
            ':billing_after' => $billingAfter,
            ':balance_after' => $balanceAfter,
        ]);
    };

    try {
        $iptvService = new IptvService($config['iptv'] ?? []);

        // В поиске используется iptv_phone. POST поддерживает оба названия поля,
        // поскольку старые формы сопряжения/отвязки отправляют login.
        if ($isPairing) {
            verify_csrf();
            $iptvOperation = trim((string) ($_POST['iptv_op'] ?? ''));
            if (!in_array($iptvOperation, ['pair', 'unlink'], true)) {
                throw new InvalidArgumentException('Неизвестная операция IPTV.');
            }
            $iptvLogin = trim((string) ($_POST['iptv_phone'] ?? $_POST['login'] ?? ''));
            $iptvPersonal = trim((string) ($_POST['personal'] ?? ''));
        } else {
            $iptvLogin = trim((string) ($_GET['iptv_phone'] ?? $_GET['login'] ?? ''));
            $iptvPersonal = trim((string) ($_GET['personal'] ?? ''));
        }

        if (strlen($iptvLogin) > 32 || strlen($iptvPersonal) > 20) {
            throw new InvalidArgumentException('Превышена допустимая длина параметра.');
        }
        if ($iptvLogin !== '' && !preg_match('/^\+?375\d{9}$/D', $iptvLogin)) {
            throw new InvalidArgumentException('Введите логин IPTV в формате +375XXXXXXXXX.');
        }
        if ($iptvPersonal !== '' && !preg_match('/^[1-9][0-9]*$/D', $iptvPersonal)) {
            throw new InvalidArgumentException('Лицевой счёт должен быть положительным числом.');
        }
        if ($iptvPersonal !== '' && $iptvLogin === '') {
            throw new InvalidArgumentException('Сначала найдите аккаунт на IPTV платформе.');
        }

        $iptvData['login'] = $iptvLogin;
        $iptvData['personal'] = $iptvPersonal;

        if ($iptvLogin !== '') {
            $account = $iptvService->user($iptvLogin);
            if (!is_array($account['user'] ?? null)) {
                throw new RuntimeException('IPTV API вернул некорректные данные аккаунта.');
            }
            $iptvData['user'] = $account['user'];
            $billingId = trim((string) ($account['user']['billing_id'] ?? '0'));

            // Для существующей связки автоматически отображаем лицевой счёт.
            if (
                !$isPairing && $iptvPersonal === '' &&
                preg_match('/^[1-9][0-9]*$/D', $billingId) &&
                strlen($billingId) <= 20
            ) {
                $iptvPersonal = $billingId;
                $iptvData['personal'] = $iptvPersonal;
            }

            // При unlink не требуем, чтобы личный счёт сохранялся в Master2.
            if ($iptvPersonal !== '' && $iptvOperation !== 'unlink') {
                $exists = $pdo->prepare(
                    'SELECT 1 FROM master_database WHERE personal = :personal LIMIT 1'
                );
                $exists->execute([':personal' => $iptvPersonal]);
                if (!$exists->fetchColumn()) {
                    throw new RuntimeException('Лицевой счёт не найден в базе Master2.');
                }

                $history = $subscribers->paymentHistory($iptvPersonal);
                if (!is_array($history)) {
                    throw new RuntimeException('Не удалось получить историю лицевого счёта.');
                }
                if (!array_key_exists('debt', $history) || !is_numeric($history['debt'])) {
                    throw new RuntimeException('Не удалось определить задолженность абонента.');
                }
                $iptvData['subscriber'] = $history;

                // Объединяем историю Master2 и платежи ЕРИП только для отображения.
                // Платежи ЕРИП могут повторять события оплаты из истории Master2.
                $operations = is_array($history['operations'] ?? null)
                    ? $history['operations'] : [];
                $toTimestamp = static function ($value): int {
                    if ($value === null || $value === '') {
                        return 0;
                    }
                    if (is_numeric($value)) {
                        return (int) $value;
                    }
                    $timestamp = strtotime((string) $value);
                    return $timestamp === false ? 0 : $timestamp;
                };
                foreach ($operations as &$operation) {
                    $operation['source'] = 'master';
                    $operation['update'] = $toTimestamp($operation['update'] ?? null);
                }
                unset($operation);

                $stmt = $pdo->prepare(
                    'SELECT id, payment_at, amount, operation_id
                     FROM erip_payments
                     WHERE account = :personal
                     ORDER BY payment_at DESC, id DESC
                     LIMIT 50'
                );
                $stmt->execute([':personal' => $iptvPersonal]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $payment) {
                    $timestamp = $toTimestamp($payment['payment_at'] ?? null);
                    if ($timestamp <= 0) {
                        continue;
                    }
                    $operations[] = [
                        'type' => 'erip',
                        'source' => 'erip',
                        'update' => $timestamp,
                        'amount' => (float) $payment['amount'],
                        'current_debt' => null,
                        'operation_id' => $payment['operation_id'] ?? '',
                        'erip_id' => $payment['id'],
                    ];
                }
                usort($operations, static function (array $a, array $b): int {
                    $dateCompare = (int) ($b['update'] ?? 0) <=> (int) ($a['update'] ?? 0);
                    return $dateCompare !== 0 ? $dateCompare : strcmp(
                        (string) ($a['source'] ?? ''),
                        (string) ($b['source'] ?? '')
                    );
                });
                $iptvData['operations'] = array_slice($operations, 0, 10);
            }
        }

        if ($isPairing && $iptvOperation === 'unlink') {
            if ($iptvLogin === '' || !is_array($iptvData['user'])) {
                throw new RuntimeException('Аккаунт IPTV не найден.');
            }
            $currentBilling = trim((string) ($iptvData['user']['billing_id'] ?? ''));
            if (!preg_match('/^[1-9][0-9]*$/D', $currentBilling)) {
                throw new RuntimeException('Аккаунт IPTV не сопряжён.');
            }

            // Проверяем все настройки и текущий баланс ДО первого POST к IPTV.
            $unpairPackets = trim((string) ($config['iptv']['unpair_packets'] ?? ''));
            if (!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D', $unpairPackets)) {
                throw new RuntimeException('Некорректная настройка iptv.unpair_packets.');
            }
            $pairAmount = $config['iptv']['pair_amount'] ?? null;
            if (
                !is_numeric($pairAmount) || (float) $pairAmount < 0 ||
                (float) $pairAmount > 9999999999.99 ||
                round((float) $pairAmount, 2) != (float) $pairAmount
            ) {
                throw new RuntimeException('Некорректная настройка iptv.pair_amount.');
            }
            $currentBalance = $iptvData['user']['balance'] ?? null;
            if (!is_numeric($currentBalance)) {
                throw new RuntimeException('Не удалось определить текущий баланс IPTV.');
            }
            $currentCents = (int) round((float) $currentBalance * 100);
            $pairCents = (int) round((float) $pairAmount * 100);
            $newCents = $currentCents - $pairCents;
            if (abs($newCents) > 999999999999) {
                throw new RuntimeException('Результирующий баланс IPTV выходит за допустимые пределы.');
            }
            $newBalance = number_format($newCents / 100, 2, '.', '');

            $iptvAuditId = $iptvAuditCreate(
                $pdo, $currentBilling, $iptvLogin, 'unlink', $currentBilling,
                number_format($currentCents / 100, 2, '.', ''),
                number_format($pairCents / 100, 2, '.', ''),
                $unpairPackets, current_user()
            );
            $iptvAuditStage = 'Установка пакетов при отвязке';
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage);
            $iptvAuditStarted = true;

            // Последовательность POST не транзакционна. При сбое проверьте
            // состояние аккаунта, прежде чем повторять всю операцию.
            $iptvService->setPackages($iptvLogin, $unpairPackets);
            $iptvAuditStage = 'Уменьшение баланса IPTV';
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage);
            $iptvService->setField($iptvLogin, 'balance', $newBalance);

            $balanceCheck = $iptvService->user($iptvLogin);
            $actualBalance = $balanceCheck['user']['balance'] ?? null;
            if (
                !is_numeric($actualBalance) ||
                (int) round((float) $actualBalance * 100) !== $newCents
            ) {
                throw new RuntimeException(
                    'Не удалось подтвердить уменьшение баланса IPTV. Проверьте аккаунт перед повторной попыткой.'
                );
            }
            $iptvData['user'] = $balanceCheck['user'];
            $iptvAuditUpdate(
                $pdo, $iptvAuditId, 'partial', 'Баланс уменьшен; ожидается отвязка',
                null, $newBalance
            );

            $iptvAuditStage = 'Сброс billing_id';
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage);
            $iptvService->setField($iptvLogin, 'billing_id', '0');
            $updated = $iptvService->user($iptvLogin);
            if (!is_array($updated['user'] ?? null) ||
                (string) ($updated['user']['billing_id'] ?? '') !== '0'
            ) {
                throw new RuntimeException('Не удалось подтвердить отвязку аккаунта IPTV.');
            }
            $iptvData['user'] = $updated['user'];
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', 'Аккаунт отвязан; сброс сессий', '0');

            // Сессии сбрасываются только после подтверждения отвязки.
            try {
                $iptvAuditStage = 'Удаление refresh-сессий';
                $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage);
                $iptvService->deleteSessions($iptvLogin);
            } catch (Throwable $exception) {
                error_log('[IPTV] session reset failed: ' . $exception->getMessage());
                throw new RuntimeException(
                    'Аккаунт IPTV отвязан, но удалить его refresh-сессии не удалось. '
                    . 'Не повторяйте отвязку; выполните сброс сессий отдельно.'
                );
            }

            $iptvAuditUpdate(
                $pdo, $iptvAuditId, 'success',
                'Отвязка, уменьшение баланса, смена пакетов и сброс сессий завершены.',
                '0', $newBalance
            );
            $iptvAuditId = null;
            $iptvData['success'] = sprintf(
                'Аккаунт IPTV отвязан от лицевого счёта %s. '
                . 'Баланс уменьшен на %s руб. Установлены пакеты: %s. '
                . 'Refresh-сессии удалены.',
                $currentBilling,
                number_format((float) $pairAmount, 2, ',', ' '),
                $unpairPackets
            );
            error_log(sprintf(
                '[IPTV] unlinked login=%s personal=%s by=%s',
                $iptvLogin, $currentBilling, current_user()
            ));
            $iptvPersonal = '';
            $iptvData['personal'] = '';
            $iptvData['subscriber'] = null;
            $iptvData['operations'] = [];
        }

        if ($isPairing && $iptvOperation === 'pair') {
            if (
                $iptvLogin === '' || $iptvPersonal === '' ||
                !is_array($iptvData['subscriber']) || !is_array($iptvData['user'])
            ) {
                throw new RuntimeException('Для сопряжения нужно найти оба аккаунта.');
            }

            $currentBilling = trim((string) ($iptvData['user']['billing_id'] ?? ''));
            if ($currentBilling !== '0') {
                throw new RuntimeException(
                    'Аккаунт IPTV уже сопряжён (billing_id = ' . $currentBilling . ').'
                );
            }

            // Валидируем настройки ДО установки billing_id и баланса.
            $pairPackets = trim((string) ($config['iptv']['pair_packets'] ?? ''));
            if (!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D', $pairPackets)) {
                throw new RuntimeException('Некорректная настройка iptv.pair_packets.');
            }
            $pairAmount = $config['iptv']['pair_amount'] ?? null;
            if (
                !is_numeric($pairAmount) || (float) $pairAmount < 0 ||
                (float) $pairAmount > 9999999999.99 ||
                round((float) $pairAmount, 2) != (float) $pairAmount
            ) {
                throw new RuntimeException('Некорректная настройка iptv.pair_amount.');
            }
            $pairBalance = number_format((float) $pairAmount, 2, '.', '');

            $originalBalance = $iptvData['user']['balance'] ?? null;
            $iptvAuditId = $iptvAuditCreate(
                $pdo, $iptvPersonal, $iptvLogin, 'pair', $currentBilling,
                is_numeric($originalBalance) ? number_format((float) $originalBalance, 2, '.', '') : null,
                $pairBalance, $pairPackets, current_user()
            );
            $iptvAuditStage = 'Установка billing_id';
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage);
            $iptvAuditStarted = true;

            $iptvService->setField($iptvLogin, 'billing_id', $iptvPersonal);
            $updated = $iptvService->user($iptvLogin);
            if (!is_array($updated['user'] ?? null) ||
                (string) ($updated['user']['billing_id'] ?? '') !== $iptvPersonal
            ) {
                throw new RuntimeException(
                    'Не удалось подтвердить сопряжение IPTV. Проверьте аккаунт перед повторной попыткой.'
                );
            }
            $iptvData['user'] = $updated['user'];
            $iptvAuditStage = 'Установка начального баланса';
            $iptvAuditUpdate($pdo, $iptvAuditId, 'partial', $iptvAuditStage, $iptvPersonal);

            try {
                $iptvService->setField($iptvLogin, 'balance', $pairBalance);
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'Лицевой счёт ' . $iptvPersonal . ' привязан, но установка баланса не удалась: '
                    . $exception->getMessage()
                );
            }
            $updated = $iptvService->user($iptvLogin);
            if (!is_array($updated['user'] ?? null)) {
                throw new RuntimeException('Не удалось получить аккаунт IPTV после установки баланса.');
            }
            $iptvData['user'] = $updated['user'];
            if (
                (string) ($updated['user']['billing_id'] ?? '') !== $iptvPersonal ||
                !is_numeric($updated['user']['balance'] ?? null) ||
                (int) round((float) $updated['user']['balance'] * 100) !==
                    (int) round((float) $pairBalance * 100)
            ) {
                throw new RuntimeException('Сопряжение выполнено, но итоговый Billing ID или баланс не подтверждён.');
            }

            $iptvAuditStage = 'Установка пакетов сопряжения';
            $iptvAuditUpdate(
                $pdo, $iptvAuditId, 'partial', $iptvAuditStage, $iptvPersonal, $pairBalance
            );
            try {
                $iptvService->setPackages($iptvLogin, $pairPackets);
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'Лицевой счёт сопряжён и баланс установлен, но подключить пакеты IPTV не удалось: '
                    . $exception->getMessage()
                );
            }

            $iptvAuditUpdate(
                $pdo, $iptvAuditId, 'success',
                'Сопряжение, установка баланса и пакетов завершены.',
                $iptvPersonal, $pairBalance
            );
            $iptvAuditId = null;
            $iptvData['success'] = sprintf(
                'IPTV-аккаунт сопряжён с лицевым счётом %s. Баланс: %s руб. Пакеты: %s.',
                $iptvPersonal,
                number_format((float) $pairBalance, 2, ',', ' '),
                $pairPackets
            );
            error_log(sprintf(
                '[IPTV] paired login=%s personal=%s balance=%s by=%s',
                $iptvLogin, $iptvPersonal, $pairBalance, current_user()
            ));
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        error_log('[IPTV] ' . $message);

        if ($iptvAuditId !== null) {
            try {
                $iptvAuditUpdate(
                    $pdo,
                    $iptvAuditId,
                    $iptvAuditStarted ? 'partial' : 'error',
                    'Ошибка на этапе «' . $iptvAuditStage . '»: ' . $message
                );
            } catch (Throwable $auditException) {
                error_log('[IPTV] audit update failed: ' . $auditException->getMessage());
            }
        }

        if (
            strpos($message, 'HTTP 409') !== false &&
            strpos($message, 'Billing ID is already assigned to another user') !== false
        ) {
            $message = 'Данный лицевой счёт уже привязан к другому IPTV-аккаунту';
        }
        $iptvData['error'] = $message;
    }
}
