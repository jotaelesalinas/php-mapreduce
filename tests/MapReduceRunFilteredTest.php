<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;

use function expect;

it('runs with pre and post filters', function (): void {
    $result = MapReduce::create()
        ->setInput([1, 2, 3, 4, 5, 6])
        ->setPreFilter(fn (mixed $item): bool => $item % 2 === 0)
        ->setMapper(fn (mixed $item): mixed => $item * 2)
        ->setPostFilter(fn (mixed $item): bool => $item > 5)
        ->setReducer(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->run();

    expect($result)->toBe([20]);
});

it('skips null mapped values', function (): void {
    $result = MapReduce::create()
        ->setInput([1, 2, 3])
        ->setMapper(fn (mixed $item): mixed => $item === 2 ? null : $item)
        ->setReducer(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->run();

    expect($result)->toBe([4]);
});
