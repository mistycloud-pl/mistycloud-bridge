<?php

namespace MistyCloud\Bridge\Http\Controllers;

use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StatusController extends ApplicationApiController
{
    public function __construct(private DaemonServerRepository $daemonRepository) {}

    public function single(Server $server): JsonResponse
    {
        return response()->json(['state' => $this->resolveState($server)]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([]);
        }

        $servers = Server::whereIn('id', $ids)->get();

        $result = [];
        foreach ($servers as $server) {
            $result[$server->id] = $this->resolveState($server);
        }

        return response()->json($result);
    }

    private function resolveState(Server $server): string
    {
        // Lifecycle states from Pelican DB take priority over daemon state
        if ($server->status !== null) {
            return $server->status->value;
        }

        $details = cache()->remember(
            "mistycloud.status.{$server->uuid}",
            now()->addSeconds(10),
            fn () => $this->daemonRepository->setServer($server)->getDetails()
        );

        return Arr::get($details, 'state', 'missing');
    }
}
