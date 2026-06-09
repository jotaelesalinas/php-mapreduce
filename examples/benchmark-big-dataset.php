<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run `composer install` from the repository root before executing this example.\n");
    exit(1);
}

require_once $autoload;

use JLSalinas\SimpleMapReduce\MapReduce;

const COVERTYPE_SOURCE_URL = 'https://archive.ics.uci.edu/ml/machine-learning-databases/covtype/covtype.data.gz';
const BASE_ROWS = 581012;
const REPEAT_FACTOR = 1;

// Goal: compare an eager in-memory pass with a streaming map/reduce pass on a
// larger UCI Covertype dataset while keeping the aggregation itself simple.
$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-simple-mapreduce';
if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
    throw new RuntimeException('Unable to create temporary working directory.');
}

$sourceFile = $workDir . DIRECTORY_SEPARATOR . 'covtype.data.gz';
$expandedFile = $workDir . DIRECTORY_SEPARATOR . 'covtype-expanded.csv';

downloadFile(COVERTYPE_SOURCE_URL, $sourceFile);
expandDataset($sourceFile, $expandedFile, REPEAT_FACTOR);

$eager = benchmark('load all into memory', static function () use ($expandedFile): array {
    $rows = [];
    $handle = fopen($expandedFile, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open expanded dataset.');
    }

    try {
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false || count($row) < 55) {
                continue;
            }

            $rows[] = [
                'cover_type' => (int) $row[54],
                'elevation' => (int) $row[0],
                'slope' => (int) $row[3],
            ];
        }
    } finally {
        fclose($handle);
    }

    $result = [];
    foreach ($rows as $row) {
        $coverType = $row['cover_type'];
        $result[$coverType]['count'] = ($result[$coverType]['count'] ?? 0) + 1;
        $result[$coverType]['elevation_sum'] = ($result[$coverType]['elevation_sum'] ?? 0) + $row['elevation'];
        $result[$coverType]['slope_sum'] = ($result[$coverType]['slope_sum'] ?? 0) + $row['slope'];
    }

    foreach ($result as $coverType => $stats) {
        $result[$coverType]['avg_elevation'] = $stats['elevation_sum'] / $stats['count'];
        $result[$coverType]['avg_slope'] = $stats['slope_sum'] / $stats['count'];
    }

    return $result;
});

$streaming = benchmark('stream with map/reduce', static function () use ($expandedFile): array {
    $rows = rowsFromCsv($expandedFile);

    $result = MapReduce::create()
        ->input($rows)
        ->map(static fn (array $row): array => [
            'cover_type' => (int) $row['cover_type'],
            'elevation' => (int) $row['elevation'],
            'slope' => (int) $row['slope'],
        ])
        ->groupBy(static fn (array $item): string => (string) $item['cover_type'])
        ->reduce(static fn (mixed $carry, array $item): array => [
            'cover_type' => $item['cover_type'],
            'count' => ($carry['count'] ?? 0) + 1,
            'elevation_sum' => ($carry['elevation_sum'] ?? 0) + $item['elevation'],
            'slope_sum' => ($carry['slope_sum'] ?? 0) + $item['slope'],
        ])
        ->run();

    foreach ($result as $coverType => $stats) {
        $result[$coverType]['avg_elevation'] = $stats['elevation_sum'] / $stats['count'];
        $result[$coverType]['avg_slope'] = $stats['slope_sum'] / $stats['count'];
    }

    return $result;
});

printf("Source dataset: %s\n", COVERTYPE_SOURCE_URL);
printf("Base rows: %d\n", BASE_ROWS);
printf("Expanded rows: %d\n\n", BASE_ROWS * REPEAT_FACTOR);

printBenchmark($eager);
printBenchmark($streaming);

echo "Top cover types by count:\n";
printTopCoverTypes($streaming['result']);

function downloadFile(string $url, string $target): void
{
    if (is_file($target) && filesize($target) > 0) {
        return;
    }

    $data = @file_get_contents($url);
    if ($data === false) {
        throw new RuntimeException('Unable to download dataset: ' . $url);
    }

    file_put_contents($target, $data);
}

function expandDataset(string $sourceFile, string $expandedFile, int $repeatFactor): void
{
    if (is_file($expandedFile)) {
        return;
    }

    $sourceRows = @gzfile($sourceFile);
    if ($sourceRows === false) {
        throw new RuntimeException('Unable to read source dataset.');
    }

    $handle = fopen($expandedFile, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Unable to create expanded dataset.');
    }

    try {
        for ($i = 0; $i < $repeatFactor; $i++) {
            foreach ($sourceRows as $line) {
                fwrite($handle, $line);
            }
        }
    } finally {
        fclose($handle);
    }
}

function rowsFromCsv(string $path): Generator
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open CSV file.');
    }

    try {
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false || count($row) < 55) {
                continue;
            }

            yield [
                'cover_type' => $row[54],
                'elevation' => $row[0],
                'slope' => $row[3],
            ];
        }
    } finally {
        fclose($handle);
    }
}

function benchmark(string $label, callable $callback): array
{
    gc_collect_cycles();
    memory_reset_peak_usage();

    $start = hrtime(true);
    $result = $callback();
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;
    $peakMb = memory_get_peak_usage(true) / 1024 / 1024;

    return [
        'label' => $label,
        'elapsed_ms' => $elapsedMs,
        'peak_mb' => $peakMb,
        'result' => $result,
    ];
}

function printBenchmark(array $benchmark): void
{
    printf("%s\n", $benchmark['label']);
    printf("  time: %.2f ms\n", $benchmark['elapsed_ms']);
    printf("  peak memory: %.2f MB\n", $benchmark['peak_mb']);
    printf("  groups: %d\n\n", count($benchmark['result']));
}

function printTopCoverTypes(array $result): void
{
    uasort($result, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

    foreach (array_slice($result, 0, 3, true) as $coverType => $stats) {
        printf(
            "  cover type %s: count=%d, avg elevation=%.2f, avg slope=%.2f\n",
            (string) $coverType,
            $stats['count'],
            $stats['avg_elevation'],
            $stats['avg_slope'],
        );
    }
}
