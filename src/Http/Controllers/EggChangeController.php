<?php

namespace MistyCloud\Bridge\Http\Controllers;

use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Models\Server;
use App\Services\Eggs\EggChangerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class EggChangeController extends ApplicationApiController
{
    public function __construct(private EggChangerService $eggChangerService)
    {
        parent::__construct();
    }

    public function __invoke(Request $request, Server $server): Response
    {
        $data = $request->validate([
            'egg_id' => ['required', 'integer', 'exists:eggs,id'],
            'keep_old_variables' => ['boolean'],
            'image' => ['nullable', 'string'],
            'startup' => ['nullable', 'string'],
            'environment' => ['nullable', 'array'],
            'environment.*' => ['nullable', 'string'],
            'start_after_reinstall' => ['boolean'],
        ]);

        $keepOldVariables = $data['keep_old_variables'] ?? false;

        $this->eggChangerService->handle($server, (int) $data['egg_id'], $keepOldVariables);

        $server->refresh();

        $updates = [];
        if (isset($data['image'])) {
            $updates['image'] = $data['image'];
        }
        if (isset($data['startup'])) {
            $updates['startup'] = $data['startup'];
        }
        if (!empty($updates)) {
            $server->forceFill($updates)->saveOrFail();
        }

        if (!empty($data['environment'])) {
            foreach ($server->serverVariables as $serverVariable) {
                $envKey = $serverVariable->variable->env_variable;
                if (array_key_exists($envKey, $data['environment'])) {
                    $serverVariable->update(['variable_value' => $data['environment'][$envKey] ?? '']);
                }
            }
        }

        if ($data['start_after_reinstall'] ?? false) {
            Cache::put("mistycloud.sar.{$server->id}", true, now()->addMinutes(10));
        }

        // No body: Pelican replaced Fractal with its own response layer in
        // 1.0.0-rc.1, and the panel does not read the server back anyway.
        return $this->returnNoContent();
    }
}
