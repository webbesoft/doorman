<?php

declare(strict_types=1);

namespace Webbesoft\Doorman\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Webbesoft\Doorman\AnalyticsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Webbesoft\\Doorman\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AnalyticsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->runPackageMigrations();
    }

    protected function runPackageMigrations(): void
    {
        $migrationsPath = __DIR__.'/../database/migrations';
        $migrations = glob($migrationsPath.'/*.php');

        sort($migrations);

        foreach ($migrations as $migration) {
            $migrationInstance = include $migration;
            $migrationInstance->up();
        }
    }
}
