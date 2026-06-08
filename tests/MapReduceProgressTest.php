<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;

use function expect;

it('reports progress per processed item', function (): void {
    $events = [];

    $result = MapReduce::crear()
        ->entrada([1, 2, 3])
        ->map(static fn (mixed $item): mixed => $item * 2)
        ->reduce(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->progreso(static function (int $count, mixed $original, mixed $mapped) use (&$events): void {
            $events[] = [$count, $original, $mapped];
        })
        ->ejecutar();

    expect($result)->toBe([12]);
    expect($events)->toBe([
        [1, 1, 2],
        [2, 2, 4],
        [3, 3, 6],
    ]);
});
