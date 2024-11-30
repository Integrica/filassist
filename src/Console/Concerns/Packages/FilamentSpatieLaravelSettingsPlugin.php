<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Integrica\Scriptorium\Console\EnvUpdater;
use Integrica\Scriptorium\Stringer;

trait FilamentSpatieLaravelSettingsPlugin
{
    public function configureFilamentSpatieLaravelSettingsPlugin($template, $package, Panel $panel, string $panelPath): void
    {
        if (!Schema::hasTable('settings')) {
            $this->call('vendor:publish', [ '--provider' => 'Spatie\LaravelSettings\LaravelSettingsServiceProvider', '--tag' => 'migrations' ]);
            $this->call('migrate');
        }

        if (($package->cluster ?? true) && !File::exists(app_path('Filament/Clusters/Settings.php'))) {
            $this->call('make:filament-cluster', [ 'name' => 'Settings', '--panel' => $panel->getId() ]);
        }

        EnvUpdater::for(base_path('.env'))
            ->setChanges([
                'SETTINGS_CACHE_ENABLED' => ($package->cache ?? true) ? 'true' : 'false',
            ])
            ->save();

        $stringer = Stringer::for(app_path('Filament/Clusters/Settings.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('heroicon-o-cog-6-tooth'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->simpleReplace('heroicon-o-squares-2x2', 'heroicon-o-cog-6-tooth')
            )
            ->when(
                value: ! $stringer->contains('$navigationSort'),
                callback: fn (Stringer $stringer) => $stringer
                    ->indent(4)
                    ->prependBeforeLast('}', '
    protected static ?int $navigationSort = 90;

    public function getTitle(): string
    {
        return __(\'settings.Settings\');
    }

    public static function getNavigationLabel(): string
    {
        return __(\'settings.Settings\');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __(\'settings.Settings\');
    }', false),
            )
            ->save();
        
        $locales = $package->locales ?? $template->locales ?? [ 'en' ];
        foreach ($locales as $locale) {
            if (!File::exists("./lang/{$locale}/settings.php") 
                && File::exists("./vendor/integrica/filassist/resources/lang/{$locale}/settings.php")) {
                File::copy("./vendor/integrica/filassist/resources/lang/{$locale}/settings.php", "./lang/{$locale}/settings.php");
            }
        }

        // TODO: add settings generation (settings, migration, page)
        // php artisan make:setting AppSettings --group="app"
        // php artisan make:settings-migration CreateAppSettings
        // php artisan make:filament-settings-page ManageAppSettings AppSettings
    }
}