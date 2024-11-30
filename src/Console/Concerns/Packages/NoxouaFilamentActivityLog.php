<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

trait NoxouaFilamentActivityLog
{
    public function configureNoxouaFilamentActivityLog($template, $package, Panel $panel, string $panelPath): void
    {
        // TODO: implement ACTIVITY_LOGGER_DB_CONNECTION to use different database
        if (!Schema::hasTable('activity_log')) {
            $this->call('vendor:publish', [ '--provider' => 'Spatie\Activitylog\ActivitylogServiceProvider', '--tag' => 'activitylog-migrations' ]);
            $this->call('migrate');
        }
        
        // TODO: add date formatting to template and use it for display
        if (!File::exists(app_path('Filament/Pages/Activity/Activities.php'))) {
            $this->call('make:filament-page', [ 'name' => 'Activity/Activities', '--resource' => '', '--panel' => $panel->getId() ]);
            File::copy('./vendor/integrica/filassist/stubs/NoxouaFilamentActivityLog/Activities.stub', app_path('Filament/Pages/Activity/Activities.php'));
        }
    }
}