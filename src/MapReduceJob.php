<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce;

use Generator;

final class MapReduceJob
{
    private const NO_KEY = '__NO__KEY__';

    /** @var array<array-key, iterable<mixed>> */
    private array $input;
    /** @var (callable(mixed): bool)|null */
    private $preFilter;
    /** @var callable(mixed): mixed */
    private $mapper;
    /** @var (callable(mixed, mixed): bool)|null */
    private $postFilter;
    /** @var mixed */
    private $groupBy;
    /** @var callable(mixed, mixed): mixed */
    private $reducer;
    /** @var callable(int, mixed, mixed): void|null */
    private $progress;
    /** @var array<array-key, Generator<mixed, mixed, mixed, mixed>|Writer> */
    private array $output;

    /**
     * @param array<array-key, iterable<mixed>> $input
     * @param (callable(mixed): bool)|null $preFilter
     * @param callable(mixed): mixed $mapper
     * @param (callable(mixed, mixed): bool)|null $postFilter
     * @param mixed $groupBy
     * @param callable(mixed, mixed): mixed $reducer
     * @param callable(int, mixed, mixed): void|null $progress
     * @param array<array-key, Generator<mixed, mixed, mixed, mixed>|Writer> $output
     */
    public function __construct(
        array $input,
        ?callable $preFilter,
        callable $mapper,
        ?callable $postFilter,
        mixed $groupBy,
        callable $reducer,
        ?callable $progress,
        array $output,
    ) {
        $this->input = $input;
        $this->preFilter = $preFilter;
        $this->mapper = $mapper;
        $this->postFilter = $postFilter;
        $this->groupBy = $groupBy;
        $this->reducer = $reducer;
        $this->progress = $progress;
        $this->output = $output;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function execute(): array
    {
        $reduced = [];
        $countProcessed = 0;

        foreach ($this->mergeInputs() as $item) {
            if ($item === null) {
                continue;
            }

            if ($this->preFilter !== null && !($this->preFilter)($item)) {
                continue;
            }

            $mapped = ($this->mapper)($item);

            if ($mapped === null) {
                continue;
            }

            $key = $this->groupBy === null ? self::NO_KEY : ($this->groupBy)($mapped);

            if ($this->postFilter !== null && !($this->postFilter)($mapped, $this->groupBy === null ? null : $key)) {
                continue;
            }

            $reduced[$key] = ($this->reducer)($reduced[$key] ?? null, $mapped);
            $countProcessed++;

            if ($this->progress !== null) {
                ($this->progress)($countProcessed, $item, $mapped);
            }
        }

        foreach ($this->output as $output) {
            foreach ($reduced as $item) {
                if ($output instanceof Generator) {
                    $output->send($item);
                    continue;
                }

                $output->write($item);
            }

            if ($output instanceof Generator) {
                $output->send(null);
                continue;
            }

            $output->close();
        }

        return count($reduced) === 1 && array_key_exists(self::NO_KEY, $reduced)
            ? array_values($reduced)
            : $reduced;
    }

    /**
     * @return Generator<int, mixed, void, void>
     */
    private function mergeInputs(): Generator
    {
        foreach ($this->input as $input) {
            foreach ($input as $item) {
                yield $item;
            }
        }
    }
}
