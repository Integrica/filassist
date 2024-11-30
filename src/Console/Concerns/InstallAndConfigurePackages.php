<?php

namespace Integrica\Filassist\Console\Concerns;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Integrica\Filassist\Console\Concerns\Packages\BarryvdhLaravelDebugbar;
use Integrica\Filassist\Console\Concerns\Packages\BezhansallehFilamentShield;
use Integrica\Filassist\Console\Concerns\Packages\FilamentSpatieLaravelSettingsPlugin;
use Integrica\Filassist\Console\Concerns\Packages\HugomybFilamentErrorMailer;
use Integrica\Filassist\Console\Concerns\Packages\LaravelLangCommon;
use Integrica\Filassist\Console\Concerns\Packages\NoxouaFilamentActivityLog;
use Integrica\Filassist\Console\Concerns\Packages\SaadeFilamentLaravelLog;

trait InstallAndConfigurePackages
{
    use ConfigureFilament;
    use BarryvdhLaravelDebugbar;
    use LaravelLangCommon;
    use HugomybFilamentErrorMailer;
    use SaadeFilamentLaravelLog;
    use BezhansallehFilamentShield;
    use FilamentSpatieLaravelSettingsPlugin;
    use NoxouaFilamentActivityLog;

    public function installAndConfigurePackages(object $template): void
    {
        $panel = Filament::getPanel($template->filament->panel_name ?? null);
        $panelPath = app_path(
            (string) str($panel->getId())
                ->studly()
                ->append('PanelProvider')
                ->prepend('Providers/Filament/')
                ->replace('\\', '/')
                ->append('.php'),
        );
        $pluginsArray = "->plugins([\n";

        $this->configureFilament($template, $panel, $panelPath);
        
        $template->has_shield = false;
        $this->installPackages($template);

        $this->configurePackages($template, $panel, $panelPath);

        if ($template->has_shield) {
            $this->call('shield:generate', [ '--all' => true, '--ignore-existing-policies' => true, '--panel' => $panel->getId() ]);
        }
    }

    private function installPackages($template) 
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $installedPackages = array_keys((array) data_get($composer, 'require'));
        $installedDevPackages = array_keys((array) data_get($composer, 'require-dev'));

        $installablePackages = [];
        $installableDevPackages = [];

        foreach ($template->packages as $package) {
            $isDev = $package->dev ?? false;

            if (!$isDev && !in_array($package->package, $installedPackages)) {
                $installablePackages[] = $package->package;
            } else if ($isDev && !in_array($package->package, $installedDevPackages)) {
                $installableDevPackages[] = $package->package;
            }

            if ($package->package == 'bezhansalleh/filament-shield') {
                $template->has_shield = true;
            }
        }

        if (!blank($installableDevPackages)) {
            $this->composer->requirePackages(packages: $installableDevPackages, dev: true, output: $this->output);
        }
        
        if (!blank($installablePackages)) {
            $this->composer->requirePackages(packages: $installablePackages, dev: false, output: $this->output);
        }
    }

    private function configurePackages($template, Panel $panel, $panelPath)
    {
        foreach ($template->packages as $package) {
            $packageConfigureFunction = Str::of($package->package)->replace('/', '-')->studly()->prepend('configure')->toString();
            $this->{$packageConfigureFunction}($template, $package, $panel, $panelPath);
        }
    }
}