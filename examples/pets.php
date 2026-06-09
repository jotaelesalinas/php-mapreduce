<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run `composer install` from the repository root before executing this example.\n");
    exit(1);
}

require_once $autoload;

use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\DataStreams\Core\ConsoleWriter;

// Goal: a veterinary clinic wants to total this month's invoice amount by
// animal species without reading the full dataset into memory first.
$invoices_this_month = [
    ['date' => '2026-06-03', 'owner' => 'Bono', 'species' => 'dog', 'amount' => 90.00],
    ['date' => '2026-06-05', 'owner' => 'Lenny', 'species' => 'cat', 'amount' => 65.00],
    ['date' => '2026-06-12', 'owner' => 'Bruce', 'species' => 'dog', 'amount' => 115.00],
    ['date' => '2026-06-20', 'owner' => 'Sting', 'species' => 'cat', 'amount' => 70.00],
];

// Keep only the fields needed for grouping and aggregation.
$result = MapReduce::create()
    ->input($invoices_this_month)
    ->map(static fn (mixed $invoice): mixed => [
        'species' => $invoice['species'],
        'amount' => $invoice['amount'],
    ])
    // Group by the field that identifies the aggregation bucket.
    ->groupBy(static fn (mixed $item): string => $item['species'])
    // Reduce each group into totals.
    ->reduce(static fn (mixed $carry, mixed $item): mixed => [
        'species' => $item['species'],
        'amount' => ($carry['amount'] ?? 0) + $item['amount'],
        'count' => ($carry['count'] ?? 0) + 1,
    ])
    // Send the reduced output to a writer so the example shows both sides.
    ->output(new ConsoleWriter())
    ->run();

var_dump($result);
