# php-simple-mapreduce

[![Latest Version on Packagist][ico-version]][link-packagist]
[![License][ico-license]][link-license]
[![CI][ico-ci]][link-ci]

Simple in-memory map/reduce for PHP iterables.

This library is the lightweight counterpart to heavier distributed engines:
it keeps the API small, works with any `iterable`, and is meant for local
processing where clarity matters more than parallelism.

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
    ->setInput([1, 2, 3, 4, 5])
    ->setMapper(static fn (mixed $item): mixed => $item * 2)
    ->setReducer(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
    ->run();

var_dump($result);
```

## What it does

- Accepts any `iterable` as input.
- Lets you map, filter, group, and reduce in one local process.
- Keeps the implementation small and testable.

## Development

```bash
composer install
composer test
composer analyse
composer format
```

## Notes

This repository is being relaunched as `php-simple-mapreduce`.
The GitHub repository name, Packagist entry, and old links need to be updated
manually outside the codebase.

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-simple-mapreduce.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ci]: https://img.shields.io/github/actions/workflow/status/jotaelesalinas/php-simple-mapreduce/ci.yml?branch=master&style=flat-square
[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-simple-mapreduce
[link-license]: https://opensource.org/licenses/MIT
[link-ci]: https://github.com/jotaelesalinas/php-simple-mapreduce/actions/workflows/ci.yml
