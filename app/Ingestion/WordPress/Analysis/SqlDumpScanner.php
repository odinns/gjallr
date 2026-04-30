<?php

declare(strict_types=1);

namespace App\Ingestion\WordPress\Analysis;

use Illuminate\Support\Str;
use RuntimeException;

final class SqlDumpScanner
{
    /**
     * @var array<int, string>
     */
    private array $coreTableSuffixes;

    /**
     * @var array<int, string>
     */
    private array $optionKeys;

    /**
     * @var array<int, string>
     */
    private array $suspiciousMarkers;

    public function __construct()
    {
        $this->coreTableSuffixes = (array) config('gjallr.analysis.wordpress_core_tables', []);
        $this->optionKeys = (array) config('gjallr.analysis.option_keys', []);
        $this->suspiciousMarkers = (array) config('gjallr.analysis.suspicious_markers', []);
    }

    public function scan(string $path): SqlDumpAnalysis
    {
        $handle = $this->openStream($path);

        $tables = [];
        $wordpressTables = [];
        $optionValues = [];
        $suspiciousFindings = [];
        $capabilities = [
            'comments' => false,
            'menus' => false,
            'seo_meta' => false,
            'terms' => false,
        ];
        $detectedPrefix = null;
        $lineNumber = 0;

        while (($line = $this->readLine($handle, $path)) !== false) {
            $lineNumber++;

            if (preg_match('/^CREATE TABLE `([^`]+)`/i', $line, $matches) === 1) {
                $tableName = $matches[1];
                $tables[$tableName] = true;

                foreach ($this->coreTableSuffixes as $suffix) {
                    if (Str::endsWith($tableName, $suffix)) {
                        $wordpressTables[$tableName] = true;

                        if ($suffix === 'options' && $detectedPrefix === null) {
                            $detectedPrefix = substr($tableName, 0, -strlen('options'));
                        }

                        if ($suffix === 'comments') {
                            $capabilities['comments'] = true;
                        }

                        if (in_array($suffix, ['terms', 'term_taxonomy', 'term_relationships'], true)) {
                            $capabilities['terms'] = true;
                        }
                    }
                }
            }

            foreach ($this->optionKeys as $optionKey) {
                if (isset($optionValues[$optionKey]) || ! str_contains($line, "'".$optionKey."'")) {
                    continue;
                }

                if (preg_match(
                    "/\\(\\d+,\\s*'".preg_quote($optionKey, '/')."',\\s*'((?:[^'\\\\]|\\\\.|'')*)',\\s*'(?:[^'\\\\]|\\\\.|'')*'\\)/",
                    $line,
                    $matches
                ) === 1) {
                    $optionValues[$optionKey] = $this->normalizeSqlString($matches[1]);
                }
            }

            if (! $capabilities['menus'] && str_contains($line, "'nav_menu_item'")) {
                $capabilities['menus'] = true;
            }

            if (! $capabilities['terms'] && (str_contains($line, 'term_taxonomy') || str_contains($line, 'wp_terms'))) {
                $capabilities['terms'] = true;
            }

            if (! $capabilities['seo_meta'] && (str_contains($line, 'yoast') || str_contains($line, 'meta_description') || str_contains($line, 'easy-wp-meta-description'))) {
                $capabilities['seo_meta'] = true;
            }

            foreach ($this->suspiciousMarkers as $marker) {
                if (str_contains(Str::lower($line), Str::lower($marker))) {
                    $suspiciousFindings[] = sprintf(
                        'SQL dump line %d contains suspicious marker "%s"',
                        $lineNumber,
                        $marker,
                    );
                    break;
                }
            }

        }

        $this->closeStream($handle, $path);

        return new SqlDumpAnalysis(
            tables: array_keys($tables),
            wordpressTables: array_keys($wordpressTables),
            detectedPrefix: $detectedPrefix,
            optionValues: $optionValues,
            suspiciousFindings: array_values(array_unique($suspiciousFindings)),
            capabilities: $capabilities,
        );
    }

    /**
     * @return resource
     */
    private function openStream(string $path)
    {
        if (Str::endsWith($path, '.gz')) {
            $handle = gzopen($path, 'rb');

            if ($handle === false) {
                throw new RuntimeException('Unable to open gzip SQL dump: '.$path);
            }

            return $handle;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open SQL dump: '.$path);
        }

        return $handle;
    }

    /**
     * @param  resource  $handle
     */
    private function readLine($handle, string $path): string|false
    {
        return Str::endsWith($path, '.gz') ? gzgets($handle) : fgets($handle);
    }

    /**
     * @param  resource  $handle
     */
    private function closeStream($handle, string $path): void
    {
        Str::endsWith($path, '.gz') ? gzclose($handle) : fclose($handle);
    }

    private function normalizeSqlString(string $value): string
    {
        return stripslashes(str_replace("''", "'", $value));
    }
}
