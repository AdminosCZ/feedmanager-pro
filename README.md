# ADMINOS Feed Manager PRO

B2B partner feeds extension for `adminos/feedmanager`. Adds per-partner UUID-token-protected XML feeds with 24-hour sliding-window rate limits and a Filament admin UI for partner management.

## What it provides

- `Partner` entity (company info, UUID `access_token`, per-feed-type rate limits, `feeds_active` kill switch)
- `DownloadLog` (append-only record of every successful partner download)
- `B2bFeedExporter` — streamed `<products>/<product>` XML built from the `Product` catalogue defined in `adminos/feedmanager`
- HTTP middleware: `PartnerTokenAuth` (UUID lookup + active check) and `PartnerRateLimit` (24h sliding window, only 200s count toward the limit)
- Public route `GET /feed/{token}/{type}` (`type` = `full` | `stock`)
- Filament Resource for managing partners with quick-action buttons (regenerate token, toggle feeds) and a recent downloads relation manager

## Installation

In an ADMINOS skeleton project that already has `adminos/feedmanager`:

```bash
composer require adminos/feedmanager-pro
php artisan migrate
```

The service provider is auto-discovered. To make the partner Filament resource visible in your admin panel, register the plugin alongside the base feedmanager plugin:

```php
use Adminos\Modules\Feedmanager\Filament\FeedmanagerPlugin;
use Adminos\Modules\FeedmanagerPro\Filament\FeedmanagerProPlugin;

$panel
    ->plugins([
        FeedmanagerPlugin::make(),
        FeedmanagerProPlugin::make(),
    ]);
```

If you don't need B2B partner feeds, leave this package uninstalled — `adminos/feedmanager` alone covers product ingest from supplier feeds and B2C marketplace exports.

## Requirements

- PHP 8.3+
- Laravel 13+
- Filament 4+
- `adminos/core` ^0.1.0-alpha.2
- `adminos/feedmanager` ^0.1.0-alpha.1

## License

Proprietary. See [LICENSE](LICENSE). Copyright © Rekoj.cz.

## Issues and pull requests

This repository is a **read-only mirror** generated from the [`AdminosCZ/adminos`](https://github.com/AdminosCZ/adminos) monorepo by a subtree-split GitHub Action. Pull requests and issues opened here cannot be merged. File them against the monorepo instead:

- Issues: https://github.com/AdminosCZ/adminos/issues
- Pull requests: https://github.com/AdminosCZ/adminos/pulls
