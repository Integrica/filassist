<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Stringer;

trait SaadeFilamentLaravelLog
{
    public function configureSaadeFilamentLaravelLog($template, $package, Panel $panel, string $panelPath): void
    {
        $stringer = Stringer::for($panelPath);
        $stringer
            ->when(
                value: ! $stringer->contains('use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;')
            )
            ->when(
                value: !$stringer->contains('FilamentLaravelLogPlugin::make()'),
                callback: fn (Stringer $stringer) => $stringer
                    ->indent(4)
                    ->append("->plugins([", "
                    FilamentLaravelLogPlugin::make()
                    // ->navigationGroup('System Tools')
                    // ->navigationLabel('Logs')
                    ->navigationIcon('heroicon-o-bug-ant')
                    ->navigationSort(110)
                    ->slug('logs')
                    ->authorize(
                        fn () => auth()->user()->can('page_ViewLog')
                    ),", false)
            )
            ->save();
        
        $locales = $package->locales ?? $template->locales ?? [ 'en' ];
        foreach ($locales as $locale) {
            if (!File::exists("./lang/vendor/log/{$locale}/filament-laravel-log.php") 
                && File::exists("./vendor/saade/filament-laravel-log/resources/lang/{$locale}/filament-laravel-log.php")) {
                File::copyDirectory("./vendor/saade/filament-laravel-log/resources/lang/{$locale}", "./lang/vendor/log/{$locale}");
            }
            if (!($package->navigation_group ?? false)) {
                $stringer = Stringer::for("./lang/vendor/log/{$locale}/filament-laravel-log.php");
                $stringer
                    ->when(
                        value: ! $stringer->contains("'group' => '', // "),
                        callback: fn (Stringer $stringer): Stringer => $stringer
                            ->replace("'group' =>", "'group' => '', // 'System',")
                    )
                    ->save();
            }
        }
    }
}