<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce\Tests;

use JLSalinas\SimpleMapReduce\MapReduce;
use JLSalinas\SimpleMapReduce\Writer;

use function expect;

it('supports the fluent api', function (): void {
    $result = MapReduce::create()
        ->input([1, 2], [3, 4])
        ->filterInput(static fn (mixed $item): bool => $item > 1, static fn (mixed $item): bool => $item < 5)
        ->map(static fn (mixed $item): mixed => $item * 2, static fn (mixed $item): mixed => $item + 1)
        ->reduce(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->run();

    expect($result)->toBe([21]);
});

it('writes to a writer and closes it', function (): void {
    $written = [];
    $closed = false;

    $writer = new class ($written, $closed) implements Writer {
        /** @var array<int, mixed> */
        public array $written;
        public bool $closed;

        /**
         * @param array<int, mixed> $written
         */
        public function __construct(array &$written, bool &$closed)
        {
            $this->written = &$written;
            $this->closed = &$closed;
        }

        public function write(mixed $item): void
        {
            $this->written[] = $item;
        }

        public function close(): void
        {
            $this->closed = true;
        }
    };

    $result = MapReduce::create()
        ->input([1, 2, 3])
        ->map(static fn (mixed $item): mixed => $item)
        ->reduce(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
        ->output($writer)
        ->run();

    expect($result)->toBe([6]);
    expect($written)->toBe([6]);
    expect($closed)->toBeTrue();
});
