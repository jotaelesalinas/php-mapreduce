# Changelog

All notable changes to `php-simple-mapreduce` will be documented in this file.

The project follows Keep a Changelog and SemVer.

## Unreleased

## v3.0.0 - 2026-06-10

### Added
- Relaunch under the `jotaelesalinas/php-simple-mapreduce` package name.
- Namespace migration to `JLSalinas\SimpleMapReduce`.
- Pest, PHPStan, PHP-CS-Fixer, and GitHub Actions.

### Changed
- The repository is being modernized in place, preserving git history.

### Deprecated
- Historical documentation and examples still describe the old API in places.

### Removed
- Travis CI.

## v2.0.0

### Added
- Fluent map/reduce aliases and progress callbacks.
- A `MapReduceJob` execution layer plus `Writer` and `NullWriter` abstractions.
- Integration coverage for the new API and a benchmark example for large datasets.

### Changed
- The README was rewritten as a landing page for the relaunch.
- The examples were moved to the new API and adapted to the job-based execution flow.

### Removed
- The older `DataAndCarry` and `ReaderAdapter` implementation path.
- Generated cache files that were no longer needed.

## v2.0.0-beta.1

### Added
- A first cut of the new fluent API.
- Aliases for the old method names to keep migrations manageable.
- Progress reporting hooks.

### Changed
- The `MapReduce` implementation was refactored around the new execution model.
- The examples and README were refreshed to match the new public API.

## v1.0.5

### Changed
- Example and test cleanup while keeping the existing API stable.
- Composer and Travis configuration updates for newer PHP support.

## v1.0.4

### Changed
- Writer formatting and KML/HTML output refinements.
- Internal generator handling cleanup.

## v.1.0.3

### Changed
- Console writer behavior was expanded and split into dedicated output formats.
- Travis and project metadata were adjusted for the current toolchain.

## v1.0.2

### Changed
- Test layout was split between grouped and ungrouped runs.
- Reader adapter and README updates clarified the existing API.

## v1.0.1

### Changed
- Composer metadata and release hygiene updates.

## v1.0.0

### Added
- Console writer support and a stabilized public API.

### Changed
- The insurance sample and documentation were updated for the 1.0 release.

## v0.2.1

### Fixed
- Reader adapter behavior for grouped and ungrouped runs.

### Added
- Coverage for grouped and ungrouped execution paths.

## v0.2.0

### Added
- A clearer public API for map/reduce jobs.
- Sequential execution behavior and PSR-2 formatting.

### Changed
- The `ReducedDataCarry` model was renamed to `DataAndCarry`.
- The README and examples were refreshed to match the new API.

## v0.1.0

### Added
- Initial map/reduce implementation and documentation.
