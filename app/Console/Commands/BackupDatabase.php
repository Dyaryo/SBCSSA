<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Database dump for both supported drivers.
 *
 * MySQL credentials are never passed on the command line (that would expose
 * them in the process list); they go into a short-lived options file that is
 * deleted in a finally block. The dump is then sanity-checked for a real
 * header before it is reported as a success.
 */
class BackupDatabase extends Command
{
    protected $signature = 'system:backup
        {--keep=30 : Delete backups older than this many days (0 disables pruning)}
        {--dir= : Destination directory (default ./backups)}';

    protected $description = 'Back up the database (MySQL dump or SQLite copy) into ./backups';

    public function handle(): int
    {
        $directory = $this->option('dir') ?: base_path('backups');

        if (! File::isDirectory($directory) && ! File::makeDirectory($directory, 0755, true)) {
            $this->error("Cannot create backup directory: {$directory}");

            return self::FAILURE;
        }

        $driver = config('database.default');
        $stamp = now()->format('Ymd-His');

        $result = match ($driver) {
            'sqlite' => $this->backupSqlite($directory, $stamp),
            'mysql', 'mariadb' => $this->backupMysql($directory, $stamp),
            default => [false, "Unsupported database driver: {$driver}"],
        };

        [$ok, $message] = $result;

        if (! $ok) {
            $this->error($message);

            return self::FAILURE;
        }

        $this->info($message);

        $keep = (int) $this->option('keep');

        if ($keep > 0) {
            $this->prune($directory, $keep);
        }

        $this->line('Copy the backups folder to a second disk or USB drive weekly.');

        return self::SUCCESS;
    }

    private function backupSqlite(string $directory, string $stamp): array
    {
        $source = config('database.connections.sqlite.database');

        if (! $source || ! is_file($source)) {
            return [false, "SQLite database file not found: {$source}"];
        }

        $target = $directory.DIRECTORY_SEPARATOR."database-{$stamp}.sqlite";

        if (! @copy($source, $target)) {
            return [false, "Could not copy {$source} to {$target}"];
        }

        return [true, sprintf('Saved %s (%s)', $target, $this->humanSize(filesize($target)))];
    }

    private function backupMysql(string $directory, string $stamp): array
    {
        $connection = config('database.connections.'.config('database.default'));
        $database = $connection['database'] ?? null;

        if (! $database) {
            return [false, 'No database name configured.'];
        }

        $binary = $this->findDumpBinary();

        if ($binary === null) {
            return [false, 'mysqldump was not found. Set MYSQLDUMP_PATH in .env or install the MySQL client tools.'];
        }

        $target = $directory.DIRECTORY_SEPARATOR."esabong-{$stamp}.sql";
        $options = storage_path('framework/backup-'.bin2hex(random_bytes(6)).'.cnf');

        $escape = fn (string $value): string => addcslashes($value, "\\\"");

        File::put($options, implode("\n", [
            '[client]',
            'user="'.$escape((string) ($connection['username'] ?? '')).'"',
            'password="'.$escape((string) ($connection['password'] ?? '')).'"',
            'host="'.$escape((string) ($connection['host'] ?? '127.0.0.1')).'"',
            'port='.(int) ($connection['port'] ?? 3306),
            '',
        ]));

        try {
            $command = sprintf(
                // No --routines / --events: the app user intentionally holds no
                // global privileges, and reading mysql.proc would fail the dump.
                '%s --defaults-extra-file=%s --no-tablespaces --single-transaction --quick --default-character-set=utf8mb4 %s > %s 2>&1',
                escapeshellarg($binary),
                escapeshellarg($options),
                escapeshellarg($database),
                escapeshellarg($target)
            );

            $output = [];
            $code = 0;
            exec($command, $output, $code);
        } finally {
            @unlink($options);
        }

        if ($code !== 0) {
            // Errors are appended after the dump header, so report the tail.
            $detail = is_file($target) ? trim((string) @file_get_contents($target)) : '';
            @unlink($target);

            return [false, "mysqldump failed (exit {$code}). ".mb_substr($detail, -400)];
        }

        $head = is_file($target) ? (string) @file_get_contents($target, false, null, 0, 512) : '';
        $tail = is_file($target) ? (string) @file_get_contents($target, false, null, max(0, filesize($target) - 400)) : '';

        if (! str_contains($head, 'MySQL dump') && ! str_contains($head, 'MariaDB dump')) {
            @unlink($target);

            return [false, 'The dump file does not look like a MySQL dump; it was discarded.'];
        }

        if (stripos($tail, 'error') !== false || str_contains($tail, 'Access denied')) {
            @unlink($target);

            return [false, 'The dump reported an error and was discarded: '.mb_substr($tail, -300)];
        }

        return [true, sprintf('Saved %s (%s)', $target, $this->humanSize(filesize($target)))];
    }

    private function findDumpBinary(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysqldump.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $which = trim((string) @shell_exec('where mysqldump 2>NUL'));

        if ($which !== '') {
            $first = strtok($which, "\r\n");

            if ($first && is_file(trim($first))) {
                return trim($first);
            }
        }

        return null;
    }

    private function prune(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $removed = 0;

        foreach (File::glob($directory.DIRECTORY_SEPARATOR.'*.{sql,sqlite}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->line("Pruned {$removed} backup(s) older than {$keepDays} days.");
        }
    }

    private function humanSize(int|false $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes > 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024, 1).' KB';
    }
}
