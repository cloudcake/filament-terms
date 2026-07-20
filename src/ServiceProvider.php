<?php

namespace Cloudcake\Terms;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $serializableClasses = config('cache.serializable_classes', []);

        if (!is_array($serializableClasses))
            $serializableClasses = [];

        config([
            'cache.serializable_classes' => [
                ...$serializableClasses,
                \Cloudcake\Terms\Models\PanelTerminology::class,
            ],
        ]);
    }

    public function boot(): void
    {
        $this->bootMigrations();
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}