<?php

namespace MistyCloud\Bridge\Listeners;

use App\Events\Server\Installed;
use App\Repositories\Daemon\DaemonServerRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MistycloudServerInstalledListener
{
    public function __construct(private DaemonServerRepository $repository) {}

    public function handle(Installed $event): void
    {
        if ($event->initialInstall || !$event->successful) {
            return;
        }

        if (!Cache::pull("mistycloud.sar.{$event->server->id}")) {
            return;
        }

        try {
            $this->repository->setServer($event->server)->power('start');
        } catch (Exception $e) {
            Log::warning('MistyCloud Bridge: failed to auto-start server after reinstall', [
                'server_id' => $event->server->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
