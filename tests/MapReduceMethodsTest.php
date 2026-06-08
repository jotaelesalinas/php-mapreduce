<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;
use InvalidArgumentException;

use function expect;

it('creates and runs from config data', function (): void {
    $result = MapReduce::createAndRun([
        'input' => [1, 2, 3],
        'mapper' => fn (mixed $item): mixed => $item * 2,
        'reducer' => fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item,
    ]);

    expect($result)->toBe([12]);
});

it('rejects unknown config data', function (): void {
    expect(fn (): MapReduce => MapReduce::create(['unknown' => true]))
        ->toThrow(InvalidArgumentException::class, "Wrong data field 'unknown'.");
});
