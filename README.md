# NeTEx import and handling in laravel

This package is capable of importing stop places and route data in NeTEx
format. The end result is a set of relational database tables and Eloquent
models.

## Install

Add this package to your laravel (8.x) installation. This package is
not yet registered on packagist.org, so the repository will have to be
added manually to your composer.json:
```
{
    "require": {
        "tromsfylkestrafikk/laravel-netex": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/TromsFylkestrafikk/laravel-netex"
        }
    ]
}
```

Run the necessary migrations:
```
php artisan migrate
```

Publish the required config:
```
php artisan vendor:publish --tag=netex-config
```

### Usage

The following artisan commands are included for import of NeTEx data:
- `netex:importstops` – Import stop places from XML file.
- `netex:routedata-import` – Import route data from XML files.
- `netex:routedata-activate` – Activate route data for quicker db queries.
- `netex:routedata-deactivate` – Deactivate route data.
- `netex:routedata-list` – List seen/stored sets of NeTEx route data.
- `netex:routedata-remove` – Remove unused or specified route sets.
- `netex:routedata-status` – Show overall activation status.
- `netex:sync-active-stops` – Set 'active' flag on stops seen in route set.

See `php artisan <COMMAND> --help` for further usage.

### API

There is so far no API for querying data, only models with relations
are added.
