# Changelog

All notable changes to `php-simple-mapreduce` will be documented in this file.

The project follows Keep a Changelog and SemVer.

## Unreleased

## v3.0.0 - 2026-06-10

### Added
- Package renamed to `jotaelesalinas/php-simple-mapreduce`.
- Namespace changed to `JLSalinas\SimpleMapReduce`.
- Pest, PHPStan, PHP-CS-Fixer, and GitHub Actions added.
- `examples/benchmark-big-dataset.php` added to compare a streaming `MapReduce` pass against an eager in-memory aggregation.
- Integration coverage added for `php-data-streams` readers and writers.

### Changed
- `MapReduce` execution is now split between pipeline setup and `MapReduceJob` execution.
- `MapReduce` now uses the fluent API in the public documentation and examples instead of the old setter-style configuration.
- `MapReduce` output now supports `JLSalinas\DataStreams\Core\Writer` implementations from `jotaelesalinas/php-data-streams` as well as generator outputs.

### Deprecated
- Historical documentation and examples still refer to the previous API in some sections.

### Removed
- Travis CI.

## v2.0.0

### Added
- `MapReduce::create()` and `MapReduce::createAndRun()` added as entry points for array-based configuration and one-shot execution.
- `MapReduce::setInput()` now accepts one or more `iterable` inputs directly.
- `MapReduce::setPreFilter()` and `MapReduce::setPostFilter()` added to filter items before and after mapping.
- `tests/MapReduceRunFilteredTest.php` added to cover pre-filter and post-filter execution.
- JSON fixture files added to test grouped, filtered, and multi-input reductions without relying on inline arrays.

### Changed
- Namespace changed from `JLSalinas\MapReduce` to `MapReduce`.
- `MapReduce::setGroupBy()` now normalizes integer and string selectors into callables, so grouped reductions work the same way for arrays and objects.
- `MapReduce::run()` was simplified around `MapReduce::setInput()`, `MapReduce::setMapper()`, `MapReduce::setReducer()`, `MapReduce::setPreFilter()`, `MapReduce::setPostFilter()`, and `MapReduce::setGroupBy()`.
- Minimum PHP version raised to `>=8.0` and the package removed the runtime dependency on `jotaelesalinas/php-rwgen`.

### Removed
- `MapReduce::inputMulti()` and `MapReduce::outputMulti()` removed from the public config array API.
- `DataAndCarry`, `ReaderAdapter`, and their dedicated tests removed from the codebase.
- Generated cache files removed.

## v2.0.0-beta.1

### Added
- Beta release of the simplified configuration API built around `MapReduce::create()`, `MapReduce::setInput()`, `MapReduce::setMapper()`, `MapReduce::setReducer()`, `MapReduce::setPreFilter()`, `MapReduce::setPostFilter()`, and `MapReduce::setGroupBy()`.
- Initial filtered and grouped test coverage for the refactored execution model.

### Changed
- Namespace changed from `JLSalinas\MapReduce` to `MapReduce` in the refactor branch that preceded `v2.0.0`.
- `MapReduce::run()` was rewritten around iterable inputs, pre-filters, post-filters, and normalized group selectors.

## v1.0.4

### Changed
- `examples/pets/pets.php` updated the reducer example to document the reducer contract more precisely.
- Grouped and ungrouped tests were updated so the expected reduction output reflects the current reducer behavior and numeric conversions.

## v1.0.2

### Fixed
- `MapReduce::setGroupBy()` now accepts any valid callable, not just `Closure`.
- `MapReduce::run()` now sends a final `null` value to every configured output generator so they flush and close correctly.
- `ReaderAdapter` now validates callback arity up front to fail early on invalid transforms.

### Changed
- Test coverage split grouped and ungrouped runs into dedicated test cases to cover the grouped reducer path and adapter-based inputs separately.

## v1.0.0

### Added
- Console writer support added and the public API stabilized.

### Changed
- Insurance sample and documentation updated.

## v0.2.1

### Fixed
- `MapReduce::setGroupBy()` now accepts any PHP callable, not just `Closure`.
- `MapReduce::run()` now terminates each output generator with `send(null)`.
- `ReaderAdapter` now rejects transform callbacks with the wrong arity.

### Added
- Coverage added for grouped and ungrouped execution paths.

## v0.2.0

### Added
- Public API for map/reduce jobs added.
- Sequential execution behavior and PSR-2 formatting added.

### Changed
- `ReducedDataCarry` renamed to `DataAndCarry`.

## v0.1.0

### Added
- Initial map/reduce implementation and documentation added.
