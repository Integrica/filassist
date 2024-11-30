<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Integrica\Scriptorium\Stringer;

trait BezhansallehFilamentShield
{
    public function configureBezhansallehFilamentShield($template, $package, Panel $panel, string $panelPath): void
    {
        $count = collect(['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions'])
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->count();
        if ($count !== 0) {
            // $this->warn('Filament Shield is already installed. Skipping...');
            return;
        }

        $this->call('vendor:publish', [ '--tag' => 'filament-shield-config' ]);
        $this->call('vendor:publish', [ '--tag' => 'filament-shield-translations' ]);

        $subfolders = File::directories(base_path('lang/vendor/filament-shield'));
        foreach ($subfolders as $subfolder) {
            $folderName = basename($subfolder);
            if (!in_array($folderName, $package->locales ?? $template->locales)) {
                File::deleteDirectory($subfolder);
            }
        }

        $stringer = Stringer::for(app_path('Models/User.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use Spatie\Permission\Traits\HasRoles;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Spatie\Permission\Traits\HasRoles;')
            )
            ->when(
                value: ! $stringer->contains('use HasRoles;'),
                callback: fn (Stringer $stringer) => $stringer
                    ->append("use HasFactory, Notifiable;", "use HasRoles;", false),
            )
            ->save();

        Stringer::for(config_path('filament-shield.php'))
            ->replace("'navigation_sort' => -1,", "'navigation_sort' => 100,")
            ->replace("'navigation_badge' => true,", "'navigation_badge' => false,")
            ->replace("'show_model_path' => true,", "'show_model_path' => false,")
            ->replace("'custom_permissions' => false,", "'custom_permissions' => true,")
            // ->replace("'AccountWidget', 'FilamentInfoWidget',", "// 'AccountWidget', 'FilamentInfoWidget',")
            ->replace("'discover_all_resources' => false,", "'discover_all_resources' => true,")
            ->replace("'discover_all_widgets' => false,", "'discover_all_widgets' => true,")
            ->replace("'discover_all_pages' => false,", "'discover_all_pages' => true,")
            ->pregReplace("/('panel_user'\s*=>\s*\[\s*'enabled'\s*=>\s*)true/", "$1false")
            ->save();

        $this->call('shield:setup');
        $this->call('shield:install', [ 'panel' => $panel->getId() ]);
        // $this->call('shield:setup', [ '--tenant', 'App\\Models\\Team' ]);
        // $this->call('shield:install', [ 'panel' => $panel->getId(), '--tenant', 'App\\Models\\Team', '--generate-relationships' => true ]);

        $stringer = Stringer::for($panelPath);
        $stringer
            ->when(
                value: $stringer->contains('FilamentShieldPlugin::make()') && !$stringer->contains("FilamentShieldPlugin::make()\n->gridColumns(["),
                callback: fn (Stringer $stringer) => $stringer
                    ->indent(4)
                    ->replace('FilamentShieldPlugin::make(),', "
                    FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 2,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),", false)
            )
            ->save();

        $stringer = Stringer::for(app_path('Providers/AppServiceProvider.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use BezhanSalleh\FilamentShield\Facades\FilamentShield;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use BezhanSalleh\FilamentShield\Facades\FilamentShield;')
            )
            ->when(
                value: ! $stringer->contains('use Illuminate\Support\Facades\Gate;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Illuminate\Support\Facades\Gate;')
            )
            ->when( /** @phpstan-ignore-next-line */
                value: !$stringer->contains('Gate::guessPolicyNamesUsing'),
                callback: fn (Stringer $stringer) => $stringer
                    ->appendBlock("public function boot()", "
        // policy name guessing not working for models and policies in subfolders
        // this is very simplified version of that
        Gate::guessPolicyNamesUsing(function (string \$class) {
            // if (!str_starts_with(\$class, 'App\\\\Models\\\\'))
            //     dd(\$class);
            if (\$class === 'Archilex\\\\AdvancedTables\\\\Models\\\\UserView') {
                return 'App\\\\Policies\\\\UserViewPolicy';
            }

            if (\$class === 'Guava\\\\FilamentKnowledgeBase\\\\Models\\\\FlatfileDocumentation') {
                return 'App\\\\Policies\\\\FlatfileDocumentationPolicy';
            }

            return str_replace('Models', 'Policies', \$class) . 'Policy';
        });

        FilamentShield::configurePermissionIdentifierUsing(
            fn(\$resource) => str(\$resource)
                ->afterLast('Resources\\\\')
                ->before('Resource')
                ->replace('\\\\', '-')
                // ->snake()
                // ->replace('_', '::')
        );", false)
            )
            ->save();
    }
}