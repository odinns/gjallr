<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Import;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class SqlDumpLoader
{
    public function loadIntoCurrentDatabase(string $sqlDumpPath, ?string $detectedPrefix = null): void
    {
        $connection = (array) Config::get('database.connections.'.Config::get('database.default'));

        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('SQL dump loading currently expects a mysql default connection.');
        }

        $this->dropExistingSourceTables($detectedPrefix);

        $command = $this->buildMysqlImportCommand($sqlDumpPath, $connection);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('SQL dump import failed: '.$process->getErrorOutput().$process->getOutput());
        }
    }

    private function dropExistingSourceTables(?string $detectedPrefix): void
    {
        $like = ($detectedPrefix ?? 'wp_').'%';
        $key = 'Tables_in_'.DB::getDatabaseName();
        $tables = collect(DB::select('SHOW TABLES LIKE ?', [$like]))
            ->map(fn (object $row): string => (string) $row->{$key})
            ->values();

        if ($tables->isEmpty()) {
            return;
        }

        $quoted = $tables->map(fn (string $table): string => '`'.$table.'`')->implode(', ');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS '.$quoted);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function buildMysqlImportCommand(string $sqlDumpPath, array $connection): string
    {
        $parts = ['mysql'];

        if (($connection['host'] ?? null) !== null && $connection['host'] !== '') {
            $parts[] = '--host='.escapeshellarg((string) $connection['host']);
        }

        if (($connection['port'] ?? null) !== null && $connection['port'] !== '') {
            $parts[] = '--port='.escapeshellarg((string) $connection['port']);
        }

        if (($connection['username'] ?? null) !== null && $connection['username'] !== '') {
            $parts[] = '--user='.escapeshellarg((string) $connection['username']);
        }

        if (($connection['password'] ?? '') !== '') {
            $parts[] = '--password='.escapeshellarg((string) $connection['password']);
        }

        if (($connection['unix_socket'] ?? '') !== '') {
            $parts[] = '--socket='.escapeshellarg((string) $connection['unix_socket']);
        }

        $parts[] = escapeshellarg((string) $connection['database']);

        $mysqlCommand = implode(' ', $parts);

        if (Str::endsWith($sqlDumpPath, '.gz')) {
            return 'gzip -dc '.escapeshellarg($sqlDumpPath).' | '.$mysqlCommand;
        }

        return $mysqlCommand.' < '.escapeshellarg($sqlDumpPath);
    }
}
