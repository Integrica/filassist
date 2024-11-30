<?php

namespace Integrica\Filassist\Console\Concerns\Packages;

use Filament\Panel;
use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Stringer;

trait HugomybFilamentErrorMailer
{
    public function configureHugomybFilamentErrorMailer($template, $package, Panel $panel, string $panelPath): void
    {
        $emails = $package->emails ?? [ $template->filament->user->email ];

        $this->call('vendor:publish', [ '--tag' => 'error-mailer-config' ]);
        
        $stringer = Stringer::for(config_path('error-mailer.php'));
        $stringer
            ->replace("'recipient' =>", "'recipient' => [ '" . implode("', '", $emails) . "' ],")
            ->save();
    }
}