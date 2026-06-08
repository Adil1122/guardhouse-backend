<?php

namespace App\Providers;

use App\Events\FormCreated;
use App\Events\FormUpdated;
use App\Listeners\HandleFormCreated;
use App\Listeners\HandleFormUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FormCreated::class => [
            HandleFormCreated::class,
        ],
        FormUpdated::class => [
            HandleFormUpdated::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
