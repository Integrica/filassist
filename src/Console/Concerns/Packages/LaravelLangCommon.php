<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Stringer;

trait LaravelLangCommon
{
    public function configureLaravelLangCommon($template, $package, Panel $panel, string $panelPath): void
    {
        $locales = $package->locales ?? $template->locales ?? [ ];
        
        if (!blank($locales)) {
            $this->call('lang:add', [ 'locales' => implode(' ', $locales) ]);
        }

        $stringer = Stringer::for(base_path('composer.json'));
        $stringer
            ->when(
                value: !$stringer->contains('@php artisan lang:update'),
                callback: fn (Stringer $stringer) => $stringer
                    ->when(
                        value: $stringer->contains('"post-update-cmd": ['),
                        callback: fn (Stringer $stringer) => $stringer
                            ->indent(4)
                            ->append('"post-update-cmd":', '"@php artisan lang:update",', false),
                    ),
            )
            ->save();
    }
}