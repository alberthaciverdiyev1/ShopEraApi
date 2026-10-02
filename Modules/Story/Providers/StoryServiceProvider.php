<?php

namespace Modules\Story\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class StoryServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Story';

    protected string $nameLower = 'story';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
