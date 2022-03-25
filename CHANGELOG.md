# Changelog

## Unreleased

### Added
- Route data is now 'activated' in two tables w/models: ActiveJourneys
  and ActiveCalls. This speeds up the complexity and query time for
  typical route data lookups.

### Fixed
- Route data import time is drastically reduced by using inserts in
  bulk instead of one-and-one insertion

## [0.1.0] 2021-11-16

### Added
- DB migration for tables related to stop places.
- Models with relations for all stop place related tables.
- Artisan command 'netex:importstops' for parsing and importing stop
  places in NeTEx format.
- Artisan command 'netex:importroutedata' for parsing and importing
  route data in NeTEx format.
