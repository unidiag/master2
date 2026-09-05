<?php

declare(strict_types=1);

require_once __DIR__ . '/SmsButton.php';

/** @var string $title */
/** @var string $module */
/** @var string $status */
/** @var array $content */
/** @var array $flashes */
/** @var string $search */
/** @var string $action */
/** @var array $config */
/** @var array $data */
/** @var int $page */
/** @var int $perPage */
/** @var int $house */
/** @var array $apartments */
/** @var array $payments */
/** @var string $personal */
/** @var string $subscriber */
/** @var string $subscriberAddress */
/** @var string $subscriberPhone */
/** @var string $subscriberTariff */
/** @var string $subscriberOnKarandash */
/** @var string $subscriberKarandashDescr */
/** @var string $houseDescr */
/** @var float $subscriberDebt */
/** @var bool $withoutCharges */
/** @var bool $withoutPayments */
/** @var array $qrRows */
/** @var string $house */


$subscriberDebt = isset($subscriberDebt)
    ? (float) $subscriberDebt
    : 0.0;

$houseDescr = isset($houseDescr)
    ? trim((string) $houseDescr)
    : '';

$withoutCharges = isset($withoutCharges)
    ? (bool) $withoutCharges
    : false;

$withoutPayments = isset($withoutPayments)
    ? (bool) $withoutPayments
    : false;

?>

<?php
$viewDir = __DIR__ . '/views';

require $viewDir . '/layout/head.php';
require $viewDir . '/layout/loader.php';
require $viewDir . '/layout/header.php';
require $viewDir . '/layout/sidebar.php';
require $viewDir . '/layout/content-open.php';
require $viewDir . '/layout/flashes.php';
require $viewDir . '/layout/toolbar.php';

$moduleViews = [
    'stroka' => 'stroka.php',
    'zayavki' => 'zayavki.php',
    'podkluchki' => 'podkluchki.php',
    'otkluchki' => 'otkluchki.php',
    'sms' => 'sms.php',
    'database' => 'database.php',
    'terminal' => 'terminal.php',
    'karandash' => 'karandash.php',
    'analog' => 'analog.php',
    'digital' => 'digital.php',
    'debtors' => 'debtors.php',
    'graph' => 'graph.php',
    'stat' => 'stat.php',
    'money' => 'money.php',
    'readers' => 'readers.php',
];

if (isset($moduleViews[$module])) {
    require $viewDir . '/modules/' . $moduleViews[$module];
}

require $viewDir . '/layout/pagination.php';
require $viewDir . '/layout/content-close.php';

if (in_array($module, ['zayavki', 'podkluchki'], true)) {
    require $viewDir . '/partials/work-modals.php';
}

if (
    $module === 'database'
    && $action !== 'history'
) {
    require $viewDir . '/partials/database-import-modal.php';
}

require $viewDir . '/layout/footer.php';
