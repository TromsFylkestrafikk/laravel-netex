# Changelog

## [0.1.2] – 2022-06-30

### Added
- Vehicle blocks extracted to separate table. Vehicle schedules are
  now a pivot table between `netex_vehicle_blocks` and
  `netex_vehicle_schedules`.
- New `netex:status` artisan command.
- Stop places now have an 'active' state, based on existence in
  current route set.  Supplementary cli command
  `netex:sync-active-stops` added, and this sync is done on both route
  data and stop place uploads.  A new global global query scope is
  added, though not utilized, buta local `$stop->active()` scope is
  immediately available on stop models.

## [0.1.1] – 2022-04-06

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
