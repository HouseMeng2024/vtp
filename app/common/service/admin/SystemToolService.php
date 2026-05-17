<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use RuntimeException;
use think\facade\Cache;

class SystemToolService
{
    /**
     * 获取系统缓存和数据库备份概览。
     */
    public function overview(): array
    {
        return [
            'cache'   => [
                'driver' => (string) config('cache.default', 'file'),
                'path'   => runtime_path('cache'),
            ],
            'backups' => $this->backups(),
        ];
    }

    /**
     * 清理 ThinkPHP 缓存。
     */
    public function clearCache(): void
    {
        Cache::clear();
        $this->removeDirectory(runtime_path('temp'));
    }

    /**
     * 获取数据库备份文件列表。
     */
    public function backups(): array
    {
        $dir = $this->backupDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $items = [];

        foreach ($files as $file) {
            $items[] = [
                'name'        => basename($file),
                'size'        => filesize($file) ?: 0,
                'create_time' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
            ];
        }

        usort($items, fn (array $a, array $b) => strcmp($b['name'], $a['name']));

        return $items;
    }

    /**
     * 创建当前数据库 SQL 备份。
     */
    public function createBackup(): array
    {
        $config = $this->databaseConfig();
        $name = $config['database'] . '_' . date('Ymd_His') . '.sql';
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $name;
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump -h%s -P%s -u%s --default-character-set=%s --single-transaction --quick --skip-lock-tables %s > %s 2>&1',
            escapeshellarg((string) $config['password']),
            escapeshellarg((string) $config['hostname']),
            escapeshellarg((string) $config['hostport']),
            escapeshellarg((string) $config['username']),
            escapeshellarg((string) $config['charset']),
            escapeshellarg((string) $config['database']),
            escapeshellarg($path)
        );

        $this->runCommand($command, '数据库备份失败');

        return [
            'name'        => $name,
            'size'        => filesize($path) ?: 0,
            'create_time' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
        ];
    }

    /**
     * 使用指定备份恢复当前数据库。
     */
    public function restoreBackup(string $name): void
    {
        $path = $this->backupPath($name);
        $config = $this->databaseConfig();
        $command = sprintf(
            'MYSQL_PWD=%s mysql -h%s -P%s -u%s --default-character-set=%s %s < %s 2>&1',
            escapeshellarg((string) $config['password']),
            escapeshellarg((string) $config['hostname']),
            escapeshellarg((string) $config['hostport']),
            escapeshellarg((string) $config['username']),
            escapeshellarg((string) $config['charset']),
            escapeshellarg((string) $config['database']),
            escapeshellarg($path)
        );

        $this->runCommand($command, '数据库恢复失败');
    }

    /**
     * 删除指定数据库备份。
     */
    public function deleteBackup(string $name): void
    {
        $path = $this->backupPath($name);

        if (!unlink($path)) {
            throw new RuntimeException('备份删除失败');
        }
    }

    /**
     * 获取数据库备份下载文件路径。
     */
    public function downloadPath(string $name): string
    {
        return $this->backupPath($name);
    }

    /**
     * 获取备份目录并确保目录存在。
     */
    private function backupDir(): string
    {
        $dir = runtime_path('database_backup');

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('备份目录创建失败');
        }

        return $dir;
    }

    /**
     * 获取并校验备份文件路径。
     */
    private function backupPath(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.sql$/', $name)) {
            throw new RuntimeException('备份文件名错误');
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $name;

        if (!is_file($path)) {
            throw new RuntimeException('备份文件不存在');
        }

        return $path;
    }

    /**
     * 获取当前默认 MySQL 连接配置。
     */
    private function databaseConfig(): array
    {
        $connection = (string) config('database.default', 'mysql');
        $config = (array) config('database.connections.' . $connection, []);

        if (($config['type'] ?? '') !== 'mysql' || empty($config['database'])) {
            throw new RuntimeException('只支持 MySQL 数据库备份');
        }

        return $config;
    }

    /**
     * 执行本机数据库命令。
     */
    private function runCommand(string $command, string $message): void
    {
        $output = [];
        $code = 0;
        exec($command, $output, $code);

        if ($code !== 0) {
            throw new RuntimeException($message . '：' . implode("\n", $output));
        }
    }

    /**
     * 递归删除目录内容。
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                rmdir($path);
                continue;
            }

            unlink($path);
        }
    }
}
