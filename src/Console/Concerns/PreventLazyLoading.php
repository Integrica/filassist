<?php

namespace Integrica\Filassist\Console\Concerns;

use Integrica\Scriptorium\Console\EnvUpdater;
use Integrica\Scriptorium\Stringer;

trait PreventLazyLoading
{
    public function preventLazyLoading(object $template): void
    {        
        EnvUpdater::for(base_path('.env'))
            ->setChanges([
                'INTEGRICA_PREVENT_LAZY_LOADING' => ($template->prevent_lazy_loading ?? true) ? 'true' : 'false',
                'INTEGRICA_PREVENT_SILENTLY_DISCARDING_ATTRIBUTES' => ($template->prevent_silently_discarding_attributes ?? true) ? 'true' : 'false',
                'INTEGRICA_PREVENT_ACCESSING_MISSING_ATTRIBUTES' => ($template->prevent_accessing_missing_attributes ?? true) ? 'true' : 'false',
            ])
            ->save();

        $content = <<<EOL

        if (!app()->isProduction()) {
            if (env('INTEGRICA_PREVENT_LAZY_LOADING', false)) {
                Model::preventLazyLoading();
            }

            if (env('INTEGRICA_PREVENT_SILENTLY_DISCARDING_ATTRIBUTES', false)) {
                Model::preventSilentlyDiscardingAttributes();
            }

            if (env('INTEGRICA_PREVENT_ACCESSING_MISSING_ATTRIBUTES', false)) {
                Model::preventAccessingMissingAttributes();
            }
        }

EOL;

        $stringer = Stringer::for(app_path('Providers/AppServiceProvider.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use Illuminate\Database\Eloquent\Model;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Illuminate\Database\Eloquent\Model;')
            )
            ->when(
                value: !$stringer->contains('INTEGRICA_PREVENT_LAZY_LOADING'),
                callback: fn (Stringer $stringer) => $stringer
                    ->appendBlock("public function boot()", $content, false),
            )
            ->save();
    }
}