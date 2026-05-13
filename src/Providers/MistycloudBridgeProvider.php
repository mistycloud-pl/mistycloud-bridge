<?php

namespace MistyCloud\Bridge\Providers;

use App\Events\Server\Installed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MistyCloud\Bridge\Http\Controllers\EggChangeController;
use MistyCloud\Bridge\Listeners\MistycloudServerInstalledListener;

class MistycloudBridgeProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware(['api', 'application-api', 'throttle:api.application'])
            ->prefix('/api/application')
            ->scopeBindings()
            ->group(function () {
                Route::post('/servers/{server:id}/egg', EggChangeController::class);
            });

        Event::listen(Installed::class, MistycloudServerInstalledListener::class);
    }
}
