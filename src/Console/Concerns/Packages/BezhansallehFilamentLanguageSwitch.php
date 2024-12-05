<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Stringer;

trait BezhansallehFilamentLanguageSwitch
{
    public function configureBezhansallehFilamentLanguageSwitch($template, $package, Panel $panel, string $panelPath): void
    {
        $stringer = Stringer::for(app_path('Providers/AppServiceProvider.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;')
            )
            ->when(
                value: ! $stringer->contains('use BezhanSalleh\FilamentLanguageSwitch\Enums\Placement;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use BezhanSalleh\FilamentLanguageSwitch\Enums\Placement;')
            )
            ->when(
                value: !$stringer->contains('LanguageSwitch::configureUsing'),
                callback: fn (Stringer $stringer) => $stringer
                    ->appendBlock("public function boot()", "
        LanguageSwitch::configureUsing(function (LanguageSwitch \$switch) {
            \$switch
                ->visible(outsidePanels: true)
                ->outsidePanelPlacement(Placement::TopRight)
                ->locales(['" . implode("', '", $template->locales) . "'])
                // ->labels(['en' => 'English'])
                ; // also accepts a closure
        });
        ", false),
            )
            ->save();
    }
}