<?php

namespace App\Providers;

use App\Events\FormCreated;
use App\Events\FormUpdated;
use App\Listeners\HandleFormCreated;
use App\Listeners\HandleFormUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // NOTE: this app relies on Laravel's event auto-discovery (listeners in
    // app/Listeners implementing handle(Event) are wired automatically). Do not
    // add shift listeners here or they will fire twice.
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
