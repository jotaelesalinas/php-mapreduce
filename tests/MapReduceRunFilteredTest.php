<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;

use function expect;

it('runs with pre and post filters', function (): void {
    $result = MapReduce::create()
        ->input([1, 2, 3, 4, 5, 6])
        ->filterInput(fn (mixed $item): bool => $item % 2 === 0)
        ->map(fn (mixed $item): mixed => $item * 2)
        ->filterMapped(fn (mixed $item, mixed $group = null): bool => $group === null && $item > 5)
        ->reduce(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->run();

    expect($result)->toBe([20]);
});

it('skips null mapped values', function (): void {
    $result = MapReduce::create()
        ->input([1, 2, 3])
        ->map(fn (mixed $item): mixed => $item === 2 ? null : $item)
        ->reduce(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->run();

    expect($result)->toBe([4]);
});
