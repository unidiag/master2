<?php
declare(strict_types=1);

/** @var string $title */
/** @var string $module */
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title><?= e($title) ?> — <?= e($config['app']['name'] ?? 'Master') ?></title>
<?php
$cssFile = __DIR__ . '/../../../assets/app.css';
$cssVersion = is_file($cssFile)
    ? filemtime($cssFile)
    : time();


$jsFile = __DIR__ . '/../../../assets/app.js';
$jsVersion = is_file($jsFile)
    ? filemtime($jsFile)
    : time();    
?>

<?php if ($module === 'terminal'): ?>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@xterm/xterm/css/xterm.css"
>

<script
    src="https://cdn.jsdelivr.net/npm/@xterm/xterm/lib/xterm.js"
    defer
></script>

<script
    src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit/lib/addon-fit.js"
    defer
></script>
<?php endif; ?>



<link
    rel="stylesheet"
    href="assets/app.css?v=<?= e((string) $cssVersion) ?>"
>
    <script src="assets/app.js?v=<?= e((string) $jsVersion) ?>" defer></script>
</head>
<body>
