<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests;

use Adminos\Core\AdminosCoreServiceProvider;
use Adminos\Modules\Feedmanager\FeedmanagerServiceProvider;
use Adminos\Modules\FeedmanagerPro\FeedmanagerProServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AdminosCoreServiceProvider::class,
            FeedmanagerServiceProvider::class,
            FeedmanagerProServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Base feedmanager migrations come first (creates feedmanager_products
        // which feedmanager-pro tests reference).
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../../feedmanager/src/Database/Migrations'));
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');
    }
}
