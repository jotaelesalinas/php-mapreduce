# php-simple-mapreduce

[![Latest Version on Packagist][ico-version]][link-packagist]
[![License][ico-license]][link-license]
[![CI][ico-ci]][link-ci]

Simple in-memory map/reduce for PHP iterables.

This library is for local data processing when you want a small, readable API
and do not need distributed workers, external storage, or tuning knobs. It is
the lighter counterpart to heavier MapReduce-style systems.

## Why this exists

- Works with any `iterable`, including arrays, generators, and custom iterators.
- Keeps all work inside one PHP process.
- Exposes a small fluent API that is easy to test.
- Lets you observe progress without coupling to a logger.

## Install

```bash
composer require jotaelesalinas/php-simple-mapreduce
```

## Quickstart

```php
<?php

declare(strict_types=1);

use JLSalinas\SimpleMapReduce\MapReduce;

$result = MapReduce::create()
    ->input([1, 2, 3, 4, 5])
    ->map(static fn (mixed $item): mixed => $item * 2)
    ->reduce(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
    ->run();

var_dump($result);
```

If you prefer a reusable callable, the same pipeline works with **any callable that matches the expected signature**: built-ins, named functions, static
methods, closures, and invokable objects:

```php
$doublerFn = static fn (mixed $item): mixed => $item * 2;

final class Stats
{
    public static function max(?int $carry, int $item): int
    {
        return $carry === null
            ? $item
            : max($carry, $item);
    }
}

$result = MapReduce::create()
    ->input([1, 2, 3, 4, 5])
    ->map($doublerFn)
    ->reduce([Stats::class, 'max'])
    ->run();
```

## Semantics

- `input()` accepts one or more `iterable` sources.
- The pipeline runs in this order: input, input filter, mapper, group key, mapped filter, reducer.
- `filterInput()` receives the raw input item and decides whether it enters the mapper.
- `map()` transforms each input item before reduction.
- `groupBy()` can group by array key, object property, or callback.
- `filterMapped()` receives the mapped item and, when grouping is enabled, the computed group key.
- `reduce()` receives the previous carry value and the mapped item.
- `progress()` receives the processed count, original item, and mapped item.
- `output()` can write reduced results to one or more `Writer` instances.

## Fluent API

```php
$result = MapReduce::create()
    ->input($items)
    ->filterInput($inputFilter)
    ->map($mapper)
    ->groupBy($groupBy)
    ->filterMapped($mappedFilter)
    ->reduce($reducer)
    ->progress($progressCallback)
    ->output($writer)
    ->run();
```

## When to use this

- Use this library when you need a local, readable aggregation pipeline.
- Use a distributed engine when you need parallel workers or external storage.
- Use `php-data-streams` when you need specialized streaming readers and writers
  for formats such as CSV, JSON, XML, or xlsx.

## Examples

- [`examples/pets.php`](examples/pets.php)
- [`examples/insurance.php`](examples/insurance.php)
- [`examples/benchmark-big-dataset.php`](examples/benchmark-big-dataset.php)

To run the examples locally from the repository root:

```bash
composer install
php examples/pets.php
php examples/insurance.php
php examples/benchmark-big-dataset.php
```

## Development

```bash
composer install
composer test
composer analyse
composer format
```

## Project status

This repository is being modernized in place. The namespace, package name, and
tooling have been updated; the remaining work is to keep refining the API and
examples until the final plan is fully implemented.

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-simple-mapreduce.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ci]: https://img.shields.io/github/actions/workflow/status/jotaelesalinas/php-simple-mapreduce/ci.yml?branch=master&style=flat-square
[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-simple-mapreduce
[link-license]: https://opensource.org/licenses/MIT
[link-ci]: https://github.com/jotaelesalinas/php-simple-mapreduce/actions/workflows/ci.yml
