<?php

declare(strict_types=1);


// MariaDB [master]> describe master_readers;
// +------------+------------------+------+-----+---------+----------------+
// | Field      | Type             | Null | Key | Default | Extra          |
// +------------+------------------+------+-----+---------+----------------+
// | reader     | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
// | name       | varchar(50)      | YES  |     | NULL    |                |
// | autoreload | int(11)          | YES  |     | NULL    |                |
// | oscam_type | varchar(50)      | YES  |     | NULL    |                |
// +------------+------------------+------+-----+---------+----------------+
// 4 rows in set (0.001 sec)


final class ReaderService
{
    private PDO $pdo;

    private string $runtimeDir;

    private string $logDir;

    public function __construct(
        PDO $pdo,
        string $runtimeDir,
        string $logDir = '/var/log/oscam'
    ) {
        $this->pdo = $pdo;
        $this->runtimeDir = rtrim($runtimeDir, '/');
        $this->logDir = rtrim($logDir, '/');
    }

    /**
     * Все ридеры.
     */
    public function all(): array
    {
        $stmt = $this->pdo->query(
            '
            SELECT *
            FROM master_readers
            ORDER BY reader
            '
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $reader = (int)($row['reader'] ?? 0);

            $row['running'] = $this->isRunning($reader);
            $row['binary_exists'] = is_file(
                $this->binaryPath($reader)
            );
            $row['config_exists'] = is_dir(
                $this->configPath($reader)
            );
            $row['log_exists'] = is_file(
                $this->logPath($reader)
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Один reader.
     */
    public function find(int $reader): ?array
    {
        if ($reader <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            '
            SELECT *
            FROM master_readers
            WHERE reader = :reader
            LIMIT 1
            '
        );

        $stmt->execute([
            'reader' => $reader,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['running'] = $this->isRunning($reader);
        $row['binary_exists'] = is_file(
            $this->binaryPath($reader)
        );
        $row['config_exists'] = is_dir(
            $this->configPath($reader)
        );
        $row['log_exists'] = is_file(
            $this->logPath($reader)
        );

        return $row;
    }

    /**
     * Последние строки лога.
     *
     * Возвращаем от новых к старым,
     * как это было в старом модуле.
     */
    public function log(
        int $reader,
        int $rows = 50,
        string $search = ''
    ): array {
        if ($reader <= 0) {
            return [];
        }

        $rows = max(1, min(5000, $rows));

        $path = $this->logPath($reader);

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        /*
         * Пока читаем весь файл так же,
         * как это делал старый модуль.
         *
         * Позже можно заменить на tail,
         * если логи очень большие.
         */
        $lines = @file(
            $path,
            FILE_IGNORE_NEW_LINES
        );

        if (!is_array($lines)) {
            return [];
        }

        $lines = array_reverse($lines);

        $search = trim($search);

        if ($search !== '') {
            $lines = array_values(
                array_filter(
                    $lines,
                    static function (string $line) use ($search): bool {
                        return stripos(
                            $line,
                            $search
                        ) !== false;
                    }
                )
            );
        }

        return array_slice(
            $lines,
            0,
            $rows
        );
    }

    /**
     * Запущен ли oscam_<reader>.
     */
    public function isRunning(int $reader): bool
    {
        if ($reader <= 0) {
            return false;
        }

        $process = 'oscam_' . $reader;

        $command =
            'pgrep -x '
            . escapeshellarg($process)
            . ' >/dev/null 2>&1';

        exec(
            $command,
            $output,
            $code
        );

        return $code === 0;
    }

    public function binaryPath(int $reader): string
    {
        return
            $this->runtimeDir
            . '/bin/oscam_'
            . $reader;
    }

    public function configPath(int $reader): string
    {
        return
            $this->runtimeDir
            . '/config/'
            . $reader;
    }

    public function logPath(int $reader): string
    {
        return
            $this->logDir
            . '/'
            . $reader
            . '.log';
    }


    public function formatLogLine(
        string $line,
        string $search = ''
    ): string
    {
        $line = preg_replace(
            '/\x1B\[[0-9;]*[A-Za-z]/',
            '',
            $line
        ) ?? $line;

        $line = htmlspecialchars(
            $line,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $line = preg_replace_callback(
            '/\((\d+)\s*ms\)/i',
            static function (array $match): string {
                $ms = (int) $match[1];

                if ($ms > 3000) {
                    $color = 'red';
                } elseif ($ms > 1000) {
                    $color = '#f73228';
                } elseif ($ms > 500) {
                    $color = 'blue';
                } else {
                    $color = 'green';
                }

                return sprintf(
                    '<span style="color:%s">(%d ms)</span>',
                    $color,
                    $ms
                );
            },
            $line
        ) ?? $line;

        $line = str_replace(
            'not decoded',
            '<strong style="color:red">not decoded</strong>',
            $line
        );

        $line = str_replace(
            'decoded cache',
            '<strong style="color:green">decoded cache</strong>',
            $line
        );

        $line = str_replace(
            'decoded',
            '<strong style="color:green">decoded</strong>',
            $line
        );

        $line = str_replace(
            'written',
            '<span style="color:#ffa12e">written</span>',
            $line
        );

        if ($search !== '') {
            $searchEscaped = htmlspecialchars(
                $search,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $line = preg_replace(
                '/' . preg_quote($searchEscaped, '/') . '/iu',
                '<mark class="reader-log-search">$0</mark>',
                $line
            ) ?? $line;
        }

        return $line;
    }


    public function saveConfig(
        int $reader,
        string $name,
        int $autoreload,
        string $oscamType,
        string $oscamConf,
        string $oscamServer,
        string $oscamUser,
        string $wicarddConf
    ): void {

        if ($reader <= 0) {
            throw new RuntimeException(
                'Invalid reader'
            );
        }

        if ($this->find($reader) === null) {
            throw new RuntimeException(
                'Reader not found'
            );
        }

        $stmt = $this->pdo->prepare(
            '
            UPDATE master_readers
            SET
                name = :name,
                autoreload = :autoreload,
                oscam_type = :oscam_type
            WHERE reader = :reader
            '
        );

        $this->updateOscamSymlink(
            $reader,
            $oscamType
        );

        $stmt->execute([
            'name' => $name,
            'autoreload' => $autoreload ? 1 : 0,
            'oscam_type' => $oscamType,
            'reader' => $reader,
        ]);


        $dir = $this->configPath($reader);

        if (!is_dir($dir)) {
            if (
                !mkdir(
                    $dir,
                    0775,
                    true
                )
                && !is_dir($dir)
            ) {
                throw new RuntimeException(
                    'Cannot create config directory'
                );
            }
        }


        $this->writeConfigFile(
            $dir . '/oscam.conf',
            $oscamConf
        );

        $this->writeConfigFile(
            $dir . '/oscam.server',
            $oscamServer
        );

        $this->writeConfigFile(
            $dir . '/oscam.user',
            $oscamUser
        );

        $this->writeConfigFile(
            $dir . '/wicardd.conf',
            $wicarddConf
        );
    }


    private function writeConfigFile(
        string $file,
        string $contents
    ): void {

        $contents = str_replace(
            "\r",
            '',
            $contents
        );

        if (
            file_put_contents(
                $file,
                $contents,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'Cannot write '
                . basename($file)
            );
        }
    }


    public function setEnabled(
        int $reader,
        bool $enabled
    ): bool {
        if ($reader <= 0) {
            throw new RuntimeException(
                'Invalid reader'
            );
        }

        if ($this->find($reader) === null) {
            throw new RuntimeException(
                'Reader not found'
            );
        }

        /*
        * Сначала физически меняем состояние OSCam.
        *
        * enabled = 1 -> запускаем
        * enabled = 0 -> останавливаем
        */
        if ($enabled) {
            $ok = $this->start($reader);
        } else {
            $ok = $this->stop($reader);
        }

        if (!$ok) {
            throw new RuntimeException(
                $enabled
                    ? 'Cannot start OSCam'
                    : 'Cannot stop OSCam'
            );
        }

        /*
        * Состояние процесса изменилось успешно —
        * только теперь фиксируем enabled в БД.
        */
        $stmt = $this->pdo->prepare(
            '
            UPDATE master_readers
            SET enabled = :enabled
            WHERE reader = :reader
            '
        );

        $stmt->execute([
            'enabled' => $enabled ? 1 : 0,
            'reader' => $reader,
        ]);

        return true;
    }

    public function config(int $reader): array
    {
        $row = $this->find($reader);

        if ($row === null) {
            throw new RuntimeException(
                'Reader not found'
            );
        }

        $dir = $this->configPath($reader);

        return [
            'reader' => $reader,

            'name' => (string) (
                $row['name']
                ?? ''
            ),

            'autoreload' => (int) (
                $row['autoreload']
                ?? 0
            ),

            'oscam_type' => (string) (
                $row['oscam_type']
                ?? ''
            ),

            'oscam_types' =>
                $this->availableOscamTypes(),

            'oscam_conf' =>
                $this->readConfigFile(
                    $dir . '/oscam.conf'
                ),

            'oscam_server' =>
                $this->readConfigFile(
                    $dir . '/oscam.server'
                ),

            'oscam_user' =>
                $this->readConfigFile(
                    $dir . '/oscam.user'
                ),

            'wicardd_conf' =>
                $this->readConfigFile(
                    $dir . '/wicardd.conf'
                ),
        ];
    }


    private function availableOscamTypes(): array
    {
        $dir =
            $this->runtimeDir
            . '/templates';

        if (!is_dir($dir)) {
            return [];
        }

        $result = [];

        foreach (
            scandir($dir) ?: []
            as $file
        ) {
            if (
                $file === '.'
                || $file === '..'
            ) {
                continue;
            }

            $path =
                $dir
                . '/'
                . $file;

            if (!is_file($path)) {
                continue;
            }

            $result[] = $file;
        }

        natcasesort($result);

        return array_values(
            $result
        );
    }    



    private function updateOscamSymlink(
        int $reader,
        string $oscamType
    ): void {
        $template =
            $this->runtimeDir
            . '/templates/'
            . $oscamType;

        if (
            $oscamType === ''
            || !is_file($template)
        ) {
            throw new RuntimeException(
                'OSCam template not found: '
                . $oscamType
            );
        }

        $binDir =
            $this->runtimeDir
            . '/bin';

        if (!is_dir($binDir)) {
            if (
                !mkdir(
                    $binDir,
                    0775,
                    true
                )
                && !is_dir($binDir)
            ) {
                throw new RuntimeException(
                    'Cannot create bin directory'
                );
            }
        }

        $link =
            $binDir
            . '/oscam_'
            . $reader;

        if (
            is_link($link)
            || is_file($link)
        ) {
            if (!unlink($link)) {
                throw new RuntimeException(
                    'Cannot remove old OSCam binary'
                );
            }
        }

        $target =
            '../templates/'
            . $oscamType;

        if (!symlink($target, $link)) {
            throw new RuntimeException(
                'Cannot create OSCam symlink'
            );
        }
    }


    private function readConfigFile(string $file): string
    {
        if (
            !is_file($file)
            || !is_readable($file)
        ) {
            return '';
        }

        $data = file_get_contents($file);

        return $data === false
            ? ''
            : $data;
    }









    public function stop(int $reader): bool
    {
        if ($reader <= 0) {
            return false;
        }

        $process = 'oscam_' . $reader;

        exec(
            'sudo /usr/bin/killall '
            . escapeshellarg($process)
            . ' >/dev/null 2>&1'
        );

        for ($i = 0; $i < 20; $i++) {
            usleep(100000);

            if (!$this->isRunning($reader)) {
                return true;
            }
        }

        return !$this->isRunning($reader);
    }


    public function start(int $reader): bool
    {
        if ($reader <= 0) {
            return false;
        }

        $binary = $this->binaryPath($reader);
        $config = $this->configPath($reader);

        if (!is_file($binary) || !is_executable($binary)) {
            return false;
        }

        if (!is_dir($config)) {
            return false;
        }

        /*
        * Если уже работает — второй процесс
        * не запускаем.
        */
        if ($this->isRunning($reader)) {
            return true;
        }

        $options = '';

        if ($reader !== 10 && $reader !== 16) {
            $options = ' -S';
        }

        $command =
            'sudo '
            . escapeshellarg($binary)
            . $options
            . ' -c '
            . escapeshellarg($config)
            . ' >/dev/null 2>&1 &';

        exec($command);

        for ($i = 0; $i < 50; $i++) {
            usleep(100000);

            if ($this->isRunning($reader)) {
                return true;
            }
        }

        return false;
    }

    public function reboot(int $reader): bool
    {
        if ($reader <= 0) {
            return false;
        }

        $this->stop($reader);

        usleep(500000);

        return $this->start($reader);
    }


}



