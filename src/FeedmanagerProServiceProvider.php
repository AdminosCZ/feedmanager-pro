<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro;

use Illuminate\Support\ServiceProvider;

/**
 * @api
 */
final class FeedmanagerProServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'feedmanager-pro');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
