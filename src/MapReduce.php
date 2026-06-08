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
    public function setInput(iterable ...$input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param (callable(mixed): bool)|null $func
     */
    public function setPreFilter(?callable $func): self
    {
        $this->preFilter = $func;

        return $this;
    }

    /**
     * @param callable(mixed): mixed $func
     */
    public function setMapper(callable $func): self
    {
        $this->mapper = $func;

        return $this;
    }

    /**
     * @param (callable(mixed): bool)|null $func
     */
    public function setPostFilter(?callable $func): self
    {
        $this->postFilter = $func;

        return $this;
    }

    /**
     * @param int|string|(callable(mixed): array-key)|null $value
     */
    public function setGroupBy(int|string|callable|null $value): self
    {
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

        return $this;
    }

    /**
     * @param callable(mixed, mixed): mixed $func
     */
    public function setReducer(callable $func): self
    {
        $this->reducer = $func;

        return $this;
    }

    /**
     * @param callable(int, mixed, mixed): void|null $func
     */
    public function setProgress(?callable $func): self
    {
        $this->progress = $func;

        return $this;
    }

    /**
     * @param Generator<mixed, mixed, mixed, mixed> ...$output
     */
    public function setOutput(Generator|Writer ...$output): self
    {
        $this->output = $output;

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

        /** @var callable(mixed): bool|null $funcPreFilter */
        $funcPreFilter = $this->preFilter;
        /** @var callable(mixed): mixed $funcMapper */
        $funcMapper = $this->mapper;
        /** @var callable(mixed): bool|null $funcPostFilter */
        $funcPostFilter = $this->postFilter;
        /** @var callable(mixed, mixed): mixed $funcReducer */
        $funcReducer = $this->reducer;
        /** @var callable(mixed): mixed|null $funcGroupBy */
        $funcGroupBy = $this->groupBy;
        /** @var callable(int, mixed, mixed): void|null $funcProgress */
        $funcProgress = $this->progress;

        $reduced = [];
        $countProcessed = 0;

        foreach ($this->mergeInputs() as $item) {
            if ($item === null) {
                continue;
            }

            if ($funcPreFilter !== null && !$funcPreFilter($item)) {
                continue;
            }

            $mapped = $funcMapper($item);

            if ($mapped === null) {
                continue;
            }

            if ($funcPostFilter !== null && !$funcPostFilter($mapped)) {
                continue;
            }

            $key = $funcGroupBy === null ? self::NO_KEY : $funcGroupBy($mapped);
            $reduced[$key] = $funcReducer($reduced[$key] ?? null, $mapped);
            $countProcessed++;

            if ($funcProgress !== null) {
                $funcProgress($countProcessed, $item, $mapped);
            }
        }

        if (count($this->output) > 0) {
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
        }

        return count($reduced) === 1 && array_key_exists(self::NO_KEY, $reduced)
            ? array_values($reduced)
            : $reduced;
    }

    private function applyConfig(string $key, mixed $value): void
    {
        switch ($key) {
            case 'preFilter':
            case 'mapper':
            case 'postFilter':
            case 'groupBy':
            case 'reducer':
            case 'input':
                $funcName = 'set' . ucfirst($key);
                $this->{$funcName}($value);

                return;

            case 'inputMulti':
                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->setInput(...$value);

                return;

            case 'output':
                if ($value instanceof Generator) {
                    $this->setOutput($value);

                    return;
                }

                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->setOutput(...$value);

                return;

            case 'outputMulti':
                if (!is_iterable($value)) {
                    throw new InvalidArgumentException("Wrong data field '$key'.");
                }

                $this->setOutput(...$value);

                return;
        }

        throw new InvalidArgumentException("Wrong data field '$key'.");
    }
}
