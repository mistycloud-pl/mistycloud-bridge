<?php

namespace MistyCloud\Bridge;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MistycloudBridgePlugin implements Plugin
{
    public function getId(): string
    {
        return 'mistycloud-bridge';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
