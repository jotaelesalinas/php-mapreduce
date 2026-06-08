# php-simple-mapreduce

[![Latest Version on Packagist][ico-version]][link-packagist]
[![License][ico-license]][link-license]
[![CI][ico-ci]][link-ci]

Simple in-memory map/reduce for PHP iterables.

This repository is the relaunch of the old `php-mapreduce` package under a new
name and namespace. The current codebase still contains the historical API in a
compatibility phase, but the target direction is the simpler `MapReduce`
engine described in the planning docs at the repository root.

## Status

- Package name: `jotaelesalinas/php-simple-mapreduce`
- Namespace: `JLSalinas\SimpleMapReduce`
- PHP floor: `8.1`
- Test runner: Pest
- Static analysis: PHPStan
- Style: PHP-CS-Fixer
- CI: GitHub Actions

## Install

```bash
composer require jotaelesalinas/php-simple-mapreduce
```

## Current API

The current class is still the historical `MapReduce` engine, now under the new
namespace:

```php
<?php

declare(strict_types=1);

use JLSalinas\SimpleMapReduce\MapReduce;

$result = MapReduce::create()
    ->setInput([1, 2, 3, 4, 5])
    ->setMapper(static fn (mixed $item): mixed => $item * 2)
    ->setReducer(static fn (mixed $carry, mixed $item): mixed => ($carry ?? 0) + $item)
    ->run();
```

## What changed in this relaunch

- New package name and namespace.
- PHP 8.1 minimum.
- Pest-based tests.
- PHPStan and PHP-CS-Fixer.
- GitHub Actions instead of Travis.

## Next steps

The remaining work is to finish aligning the implementation and documentation
with the final plan:

- clarify the map/reduce semantics,
- modernize the README into a landing page,
- add examples that match the new API,
- keep the bridge/compatibility story documented.

## Development

```bash
composer install
composer test
composer analyse
composer format
```

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-simple-mapreduce.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ci]: https://img.shields.io/github/actions/workflow/status/jotaelesalinas/php-simple-mapreduce/ci.yml?branch=master&style=flat-square
[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-simple-mapreduce
[link-license]: https://opensource.org/licenses/MIT
[link-ci]: https://github.com/jotaelesalinas/php-simple-mapreduce/actions/workflows/ci.yml
