<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;
use InvalidArgumentException;

use function expect;

it('requires input', function (): void {
    expect(fn (): array => MapReduce::create()
        ->map(fn (mixed $item): mixed => $item)
        ->reduce(fn (mixed $carry, mixed $item): mixed => $item)
        ->run())->toThrow(InvalidArgumentException::class, 'Missing input.');
});

it('requires mapper', function (): void {
    expect(fn (): array => MapReduce::create()
        ->input([1, 2])
        ->reduce(fn (mixed $carry, mixed $item): mixed => $item)
        ->run())->toThrow(InvalidArgumentException::class, 'Missing mapper function.');
});

it('requires reducer', function (): void {
    expect(fn (): array => MapReduce::create()
        ->input([1, 2])
        ->map(fn (mixed $item): mixed => $item)
        ->run())->toThrow(InvalidArgumentException::class, 'Missing reducer function.');
});
