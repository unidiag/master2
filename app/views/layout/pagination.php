<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $module */
/** @var string $search */
/** @var bool $withoutCharges */
/** @var bool $withoutPayments */
/** @var int $perPage */
/** @var int $page */


if (
    $module !== 'stat'
    && isset($data['total'])
    && $data['total'] > $perPage
):
    $pages = (int) ceil(
        $data['total'] / $perPage
    );

    $paginationParams = [
        'module' => $module,
        'search' => $search,
        'status' => $status,
    ];

    if (
        $module === 'database'
        && $withoutCharges
    ) {
        $paginationParams['without_charges'] = '1';
    }

    if (
        $module === 'database'
        && $withoutPayments
    ) {
        $paginationParams['without_payments'] = '1';
    }
?>

    <nav class="pagination">

        <?php if ($page > 1): ?>
            <a
                class="button"
                href="<?= e(url(
                    $paginationParams + [
                        'page' => $page - 1,
                    ]
                )) ?>"
            >
                ← Назад
            </a>
        <?php endif; ?>

        <span>
            Страница <?= e($page) ?>
            из <?= e($pages) ?>
        </span>

        <?php if ($page < $pages): ?>
            <a
                class="button"
                href="<?= e(url(
                    $paginationParams + [
                        'page' => $page + 1,
                    ]
                )) ?>"
            >
                Далее →
            </a>
        <?php endif; ?>

    </nav>

<?php endif; ?>
