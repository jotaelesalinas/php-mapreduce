<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;

use function expect;

it('groups by an array key', function (): void {
    $result = MapReduce::create()
        ->setInput([
            ['type' => 'a', 'value' => 1],
            ['type' => 'b', 'value' => 2],
            ['type' => 'a', 'value' => 3],
        ])
        ->setMapper(fn (mixed $item): mixed => $item)
        ->setGroupBy('type')
        ->setReducer(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item['value'])
        ->run();

    expect($result)->toBe([
        'a' => 4,
        'b' => 2,
    ]);
});

it('groups by a callback', function (): void {
    $result = MapReduce::create()
        ->setInput([10, 11, 20])
        ->setMapper(fn (mixed $item): mixed => ['age' => $item])
        ->setGroupBy(fn (mixed $item): int => intdiv($item['age'], 10) * 10)
        ->setReducer(fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item['age'])
        ->run();

    expect($result)->toBe([
        10 => 21,
        20 => 20,
    ]);
});
