<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Stringer;

trait BarryvdhLaravelDebugbar
{
    public function configureBarryvdhLaravelDebugbar($template, $package, Panel $panel, string $panelPath): void
    {
        $configPath = config_path('debugbar.php');
        if (!File::exists($configPath)
            && File::exists('./vendor/barryvdh/laravel-debugbar/config/debugbar.php')) {
            
            File::copy('./vendor/barryvdh/laravel-debugbar/config/debugbar.php', $configPath);

            $disable_views = ($package->disable_views ?? true);
            $disable_cache = ($package->disable_cache ?? true);
            $stringer = Stringer::for($configPath);
            $stringer
                ->when(
                    value: $disable_views && $stringer->contains("// Views with their data"),
                    callback: fn (Stringer $stringer) => $stringer
                        ->replace("// Views with their data", "'views'           => false,  // Views with their data"),
                )
                ->when(
                    value: !$disable_views && $stringer->contains("// Views with their data"),
                    callback: fn (Stringer $stringer) => $stringer
                        ->replace("// Views with their data", "'views'           => true,  // Views with their data"),
                )
                ->when(
                    value: $disable_cache && $stringer->contains("// Display cache events"),
                    callback: fn (Stringer $stringer) => $stringer
                        ->replace("// Display cache events", "'cache'           => false,  // Display cache events"),
                )
                ->when(
                    value: !$disable_cache && $stringer->contains("// Display cache events"),
                    callback: fn (Stringer $stringer) => $stringer
                        ->replace("// Display cache events", "'cache'           => true,  // Display cache events"),
                )
                ->save();
        }
    }
}