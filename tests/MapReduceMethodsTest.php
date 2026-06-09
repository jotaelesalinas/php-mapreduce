<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;
use InvalidArgumentException;

use function expect;

it('creates and runs from config data', function (): void {
    $result = MapReduce::createAndRun([
        'input' => [1, 2, 3],
        'filterInput' => fn (mixed $item): bool => $item > 1,
        'map' => fn (mixed $item): mixed => $item * 2,
        'filterMapped' => fn (mixed $item, mixed $group = null): bool => $item >= 4,
        'reduce' => fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item,
    ]);

    expect($result)->toBe([10]);
});

it('rejects unknown config data', function (): void {
    expect(fn (): MapReduce => MapReduce::create(['unknown' => true]))
        ->toThrow(InvalidArgumentException::class, "Wrong data field 'unknown'.");
});

it('rejects repeated method calls', function (): void {
    expect(fn (): MapReduce => MapReduce::create()
        ->input([1])
        ->input([2]))->toThrow(InvalidArgumentException::class, "Method 'input' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->map(fn (mixed $item): mixed => $item)
        ->map(fn (mixed $item): mixed => $item))->toThrow(InvalidArgumentException::class, "Method 'map' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->filterInput(fn (mixed $item): bool => true)
        ->filterInput(fn (mixed $item): bool => true))->toThrow(InvalidArgumentException::class, "Method 'filterInput' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->filterMapped(fn (mixed $item, mixed $group = null): bool => true)
        ->filterMapped(fn (mixed $item, mixed $group = null): bool => true))->toThrow(InvalidArgumentException::class, "Method 'filterMapped' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->groupBy('type')
        ->groupBy('type'))->toThrow(InvalidArgumentException::class, "Method 'groupBy' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->reduce(fn (mixed $carry, mixed $item): mixed => $item)
        ->reduce(fn (mixed $carry, mixed $item): mixed => $item))->toThrow(InvalidArgumentException::class, "Method 'reduce' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->progress(static function (int $count, mixed $original, mixed $mapped): void {})
        ->progress(static function (int $count, mixed $original, mixed $mapped): void {}))->toThrow(InvalidArgumentException::class, "Method 'progress' cannot be called more than once.");

    expect(fn (): MapReduce => MapReduce::create()
        ->output(new class implements \JLSalinas\SimpleMapReduce\Writer {
            public function write(mixed $item): void {}
            public function close(): void {}
        })
        ->output(new class implements \JLSalinas\SimpleMapReduce\Writer {
            public function write(mixed $item): void {}
            public function close(): void {}
        }))->toThrow(InvalidArgumentException::class, "Method 'output' cannot be called more than once.");
});
