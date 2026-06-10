# Changelog

All notable changes to `php-simple-mapreduce` will be documented in this file.

The project follows Keep a Changelog and SemVer.

## Unreleased

## v3.0.0 - 2026-06-10

### Added
- Package renamed to `jotaelesalinas/php-simple-mapreduce`.
- Namespace changed to `JLSalinas\SimpleMapReduce`.
- Pest, PHPStan, PHP-CS-Fixer, and GitHub Actions added.

### Changed
- README, examples, and code structure updated for the new package name and namespace.

### Deprecated
- Historical documentation and examples still refer to the previous API in some sections.

### Removed
- Travis CI.

## v2.0.0

### Added
- Fluent map/reduce aliases and progress callbacks added.
- `MapReduceJob`, `Writer`, and `NullWriter` abstractions added.
- Integration coverage and a large-dataset benchmark example added.

### Changed
- README rewritten and examples updated for the new API.
- Execution flow moved to the job-based model.

### Removed
- `DataAndCarry` and `ReaderAdapter` removed from the implementation path.
- Generated cache files removed.

## v2.0.0-beta.1

### Added
- First release of the new fluent API.
- Aliases for the old method names added.
- Progress reporting hooks added.

### Changed
- `MapReduce` refactored around the new execution model.
- Examples and README updated for the public API.

## v1.0.5

### Changed
- Examples and tests cleaned up.
- Composer and Travis configuration updated for newer PHP support.

## v1.0.4

### Changed
- Writer formatting and KML/HTML output refined.
- Internal generator handling cleaned up.

## v.1.0.3

### Changed
- Console writer behavior expanded and split into dedicated output formats.
- Travis and project metadata updated.

## v1.0.2

### Changed
- Test layout split between grouped and ungrouped runs.
- Reader adapter and README updated.

## v1.0.1

### Changed
- Composer metadata and release files updated.

## v1.0.0

### Added
- Console writer support added and the public API stabilized.

### Changed
- Insurance sample and documentation updated.

## v0.2.1

### Fixed
- Reader adapter behavior fixed for grouped and ungrouped runs.

### Added
- Coverage added for grouped and ungrouped execution paths.

## v0.2.0

### Added
- Public API for map/reduce jobs added.
- Sequential execution behavior and PSR-2 formatting added.

### Changed
- `ReducedDataCarry` renamed to `DataAndCarry`.
- README and examples updated for the API.

## v0.1.0

### Added
- Initial map/reduce implementation and documentation added.
