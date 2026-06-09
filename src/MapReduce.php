<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce;

use Generator;
use InvalidArgumentException;

class MapReduce
{
    private const NO_KEY = '__NO__KEY__';

    /** @var (callable(mixed): bool)|null */
    protected $preFilter = null;
    /** @var callable(mixed): mixed|null */
    protected $mapper = null;
    /** @var (callable(mixed): bool)|null */
    protected $postFilter = null;
    /** @var mixed */
    protected $groupBy = null;
    /** @var callable(mixed, mixed): mixed|null */
    protected $reducer = null;
    /** @var callable(int, mixed, mixed): void|null */
    protected $progress = null;
    /** @var array<array-key, iterable<mixed>>|null */
    protected ?array $input = null;
    /** @var array<array-key, Generator<mixed, mixed, mixed, mixed>|Writer> */
    protected array $output = [];

    private bool $inputConfigured = false;
    private bool $preFilterConfigured = false;
    private bool $mapperConfigured = false;
    private bool $postFilterConfigured = false;
    private bool $groupByConfigured = false;
    private bool $reducerConfigured = false;
    private bool $progressConfigured = false;
    private bool $outputConfigured = false;

    /**
     * @param array<string, mixed>|null $data
     */
    public static function create(?array $data = null): self
    {
        $mr = new self();

        if ($data !== null) {
            foreach ($data as $key => $value) {
                $mr->applyConfig((string) $key, $value);
            }
        }

        return $mr;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<array-key, mixed>
     */
    public static function createAndRun(array $data): array
    {
        return self::create($data)->run();
    }

    /**
     * @param iterable<mixed> ...$input
     */
    public function input(iterable ...$input): self
    {
        $this->assertNotConfigured('input', $this->inputConfigured);
        $this->input = $input;
        $this->inputConfigured = true;

        return $this;
    }

    /**
     * @param callable(mixed): bool ...$func
     */
    public function filterInput(callable ...$func): self
    {
        $this->assertNotConfigured('filterInput', $this->preFilterConfigured);
        $this->preFilter = $this->composeUnaryFilters(array_values($func));
        $this->preFilterConfigured = true;

        return $this;
    }

    /**
     * @param callable(mixed): mixed ...$func
     */
    public function map(callable ...$func): self
    {
        $this->assertNotConfigured('map', $this->mapperConfigured);
        $this->mapper = $this->composeMappers(array_values($func));
        $this->mapperConfigured = true;

        return $this;
    }

    /**
     * @param callable(mixed, mixed): bool ...$func
     */
    public function filterMapped(callable ...$func): self
    {
        $this->assertNotConfigured('filterMapped', $this->postFilterConfigured);
        $this->postFilter = $this->composeBinaryFilters(array_values($func));
        $this->postFilterConfigured = true;

        return $this;
    }

    /**
     * @param int|string|(callable(mixed): array-key)|null $value
     */
    public function groupBy(int|string|callable|null $value): self
    {
        $this->assertNotConfigured('groupBy', $this->groupByConfigured);

        $func = $value;

        if (is_numeric($value)) {
            $func = function (mixed $item) use ($value): mixed {
                return $item[$value];
            };
        } elseif (is_string($value)) {
            $func = function (mixed $item) use ($value): mixed {
                return is_array($item) ? $item[$value] : $item->$value;
            };
        } elseif ($value === null) {
            $key = self::NO_KEY;
            $func = function (mixed $_item) use ($key): string {
                return $key;
            };
        }

        $this->groupBy = $func;
        $this->groupByConfigured = true;

        return $this;
    }

    /**
     * @param callable(mixed, mixed): mixed $func
     */
    public function reduce(callable $func): self
    {
        $this->assertNotConfigured('reduce', $this->reducerConfigured);
        $this->reducer = $func;
        $this->reducerConfigured = true;

        return $this;
    }

    /**
     * @param callable(int, mixed, mixed): void|null $func
     */
    public function progress(?callable $func): self
    {
        $this->assertNotConfigured('progress', $this->progressConfigured);
        $this->progress = $func;
        $this->progressConfigured = true;

        return $this;
    }

    /**
     * @param Generator<mixed, mixed, mixed, mixed>|Writer ...$output
     */
    public function output(Generator|Writer ...$output): self
    {
        $this->assertNotConfigured('output', $this->outputConfigured);
        $this->output = $output;
        $this->outputConfigured = true;

        return $this;
    }

    /**
     * @return Generator<int, mixed, void, void>
     */
    protected function mergeInputs(): Generator
    {
        /** @var array<array-key, iterable<mixed>> $inputs */
        $inputs = $this->input ?? [];

        foreach ($inputs as $input) {
            foreach ($input as $item) {
                yield $item;
            }
        }
    }

    private function checkProperties(): void
    {
        if ($this->input === null || count($this->input) === 0) {
            throw new InvalidArgumentException('Missing input.');
        }

        if ($this->mapper === null) {
            throw new InvalidArgumentException('Missing mapper function.');
        }

        if ($this->reducer === null) {
            throw new InvalidArgumentException('Missing reducer function.');
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    public function run(): array
    {
        $this->checkProperties();

        /** @var array<array-key, iterable<mixed>> $input */
        $input = $this->input ?? [];
        /** @var callable(mixed): mixed $mapper */
        $mapper = $this->mapper;
        /** @var callable(mixed, mixed): mixed $reducer */
        $reducer = $this->reducer;

        return (new MapReduceJob(
            $input,
            $this->preFilter,
            $mapper,
            $this->postFilter,
            $this->groupBy,
            $reducer,
            $this->progress,
            $this->output,
        ))->execute();
    }

    private function applyConfig(string $key, mixed $value): void
    {
        switch ($key) {
            case 'preFilter':
            case 'filterInput':
            case 'map':
            case 'mapper':
            case 'filterMapped':
            case 'postFilter':
            case 'groupBy':
            case 'reduce':
            case 'reducer':
            case 'progress':
            case 'input':
                $method = match ($key) {
                    'preFilter' => 'filterInput',
                    'mapper', 'map' => 'map',
                    'postFilter', 'filterMapped' => 'filterMapped',
                    'reducer', 'reduce' => 'reduce',
                    default => $key,
                };

                $this->{$method}($value);

                return;

            case 'inputMulti':
                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->input(...$value);

                return;

            case 'output':
                if ($value instanceof Generator || $value instanceof Writer) {
                    $this->output($value);

                    return;
                }

                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->output(...$value);

                return;

            case 'outputMulti':
                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->output(...$value);

                return;
        }

        throw new InvalidArgumentException("Wrong data field '$key'.");
    }

    /**
     * @param array<int, callable(mixed): bool> $functions
     * @return callable
     */
    private function composeUnaryFilters(array $functions): callable
    {
        if ($functions === []) {
            throw new InvalidArgumentException('Missing callback.');
        }

        return static function (mixed ...$args) use ($functions): bool {
            foreach ($functions as $func) {
                if (!$func(...$args)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * @param array<int, callable(mixed, mixed): bool> $functions
     * @return callable
     */
    private function composeBinaryFilters(array $functions): callable
    {
        if ($functions === []) {
            throw new InvalidArgumentException('Missing callback.');
        }

        return static function (mixed $item, mixed $group = null) use ($functions): bool {
            foreach ($functions as $func) {
                if (!$func($item, $group)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * @param array<int, callable(mixed): mixed> $functions
     * @return callable
     */
    private function composeMappers(array $functions): callable
    {
        if ($functions === []) {
            throw new InvalidArgumentException('Missing callback.');
        }

        return static function (mixed $item) use ($functions): mixed {
            foreach ($functions as $func) {
                $item = $func($item);
            }

            return $item;
        };
    }

    private function assertNotConfigured(string $method, bool $configured): void
    {
        if ($configured) {
            throw new InvalidArgumentException("Method '$method' cannot be called more than once.");
        }
    }
}
