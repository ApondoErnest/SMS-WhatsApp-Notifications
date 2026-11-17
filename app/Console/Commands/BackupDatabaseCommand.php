<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--keep= : Override retain count}';

    protected $description = 'Create a local database backup and prune older backups';

    public function handle(): int
    {
        if (! config('security.backups.enabled')) {
            $this->warn('Database backups are disabled (DB_BACKUP_ENABLED=false).');

            return self::SUCCESS;
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $diskName = (string) config('security.backups.disk', 'local');
        $disk = Storage::disk($diskName);
        $dir = $this->resolveBackupSubdirectory($diskName);

        if ($dir !== '') {
            $disk->makeDirectory($dir);
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "{$connection}_{$timestamp}";
        $relativePath = $dir === '' ? $filename : "{$dir}/{$filename}";

        try {
            if ($driver === 'sqlite') {
                $this->backupSqlite($disk, $relativePath.'.sqlite');
            } elseif ($driver === 'mysql') {
                $this->backupMysql($disk, $relativePath.'.sql');
            } else {
                $this->error("Unsupported database driver for backups: {$driver}");

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $retain = (int) ($this->option('keep') ?: config('security.backups.retain', 14));
        $this->pruneOldBackups($disk, $dir, max(1, $retain));

        $written = $disk->path($relativePath.($driver === 'sqlite' ? '.sqlite' : '.sql'));
        $this->info('Database backup created: '.$written);

        return self::SUCCESS;
    }

    /**
     * Relative subdirectory inside the backup disk root.
     * Absolute paths belong in DB_BACKUP_ROOT, not DB_BACKUP_PATH.
     */
    private function resolveBackupSubdirectory(string $diskName): string
    {
        $raw = (string) config('security.backups.path', '');
        $dir = trim($raw, '/.');

        if ($dir === '') {
            return '';
        }

        // e.g. DB_BACKUP_PATH=/var/backups/visite-notify → would nest as …/var/backups/…
        if (str_starts_with($raw, '/') || preg_match('#^(var|home|usr|etc)/#', $dir) === 1) {
            $this->warn('DB_BACKUP_PATH looks like a filesystem path; writing to the disk root instead. Set the folder with DB_BACKUP_ROOT only.');

            return '';
        }

        $root = rtrim(str_replace('\\', '/', (string) config("filesystems.disks.{$diskName}.root", '')), '/');
        if ($root !== '' && ($dir === ltrim($root, '/') || str_ends_with($root, '/'.$dir))) {
            $this->warn('DB_BACKUP_PATH duplicates DB_BACKUP_ROOT; writing to the disk root instead.');

            return '';
        }

        return $dir;
    }

    private function backupSqlite($disk, string $relativePath): void
    {
        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === ':memory:' || ! is_file($database)) {
            throw new \RuntimeException('SQLite database file not found (file-backed SQLite required for backups).');
        }

        $absolute = $disk->path($relativePath);
        File::ensureDirectoryExists(dirname($absolute));
        if (! copy($database, $absolute)) {
            throw new \RuntimeException('Failed to copy SQLite database file.');
        }
        $this->relaxPermissions($absolute);
    }

    private function backupMysql($disk, string $relativePath): void
    {
        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port', 3306);
        $database = (string) config('database.connections.mysql.database');
        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        $absolute = $disk->path($relativePath);
        File::ensureDirectoryExists(dirname($absolute));

        $command = [
            'mysqldump',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ];

        $process = new Process($command);
        $process->setTimeout(600);
        if ($password !== '') {
            $process->setEnv(['MYSQL_PWD' => $password] + $_ENV);
        }
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'mysqldump failed.');
        }

        if (file_put_contents($absolute, $process->getOutput()) === false) {
            throw new \RuntimeException('Failed to write MySQL dump file.');
        }

        $this->relaxPermissions($absolute);
    }

    private function relaxPermissions(string $absolute): void
    {
        @chmod(dirname($absolute), 0755);
        @chmod($absolute, 0644);
    }

    private function pruneOldBackups($disk, string $dir, int $retain): void
    {
        $files = collect($disk->files($dir === '' ? '' : $dir))
            ->filter(fn (string $path) => preg_match('/\.(sql|sqlite)$/', $path) === 1)
            ->sort()
            ->values();

        $extra = $files->count() - $retain;
        if ($extra <= 0) {
            return;
        }

        foreach ($files->take($extra) as $path) {
            $disk->delete($path);
        }
    }
}
