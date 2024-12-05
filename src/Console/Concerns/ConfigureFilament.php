<?php

namespace Integrica\Filassist\Console\Concerns;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Integrica\Scriptorium\Stringer;

trait ConfigureFilament
{
    public function configureFilament(object $template, Panel $panel, string $panelPath): void
    {
        $this->createFilamentUser($template->filament->user ?? null);

        $this->updateUserModel($template);
        
        if (($template->notifications ?? true) && !Schema::hasTable('notifications')) {
            $this->call('notifications:table');
        }

        // import / export actions
        if (($template->actions ?? true) && !Schema::hasTable('exports')) {
            $this->call('vendor:publish', [ '--tag' => 'filament-actions-migrations' ]);
        }

        $this->call('migrate');

        $this->configureFilamentProvider($template, $panel, $panelPath);
    }

    protected function createFilamentUser(object $user): void
    {
        if (!$user || User::where('email', $user->email)->exists()) {
            return;
        }

        if (blank($user->password)) {
            $user->password = Str::password(8);
            $this->info('Generated password: ' . $user->password);
        }

        $this->call('make:filament-user', [
            '--name' => $user->name,
            '--email' => $user->email,
            '--password' => $user->password,
        ]);
    }

    protected function updateUserModel(object $template): void
    {
        $content = <<<EOL

    public function canAccessPanel(Panel \$panel): bool
    {
        // TODO: needs to cache check
        // if checking for user roles
        return auth()->check();
    }

    public function getFilamentName(): string
    {
        return \$this->name;
    }
EOL;
        $stringer = Stringer::for(app_path('Models/User.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use Filament\Models\Contracts\FilamentUser;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Filament\Models\Contracts\FilamentUser;')
            )
            ->when(
                value: ! $stringer->contains('use Filament\Models\Contracts\HasName;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Filament\Models\Contracts\HasName;')
            )
            ->when(
                value: ! $stringer->contains('use Filament\Panel;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Filament\Panel;')
            )
            ->when(
                value: ! $stringer->contains('FilamentUser, HasName'),
                callback: fn (Stringer $stringer) => $stringer
                    ->replace("class User extends Authenticatable", "class User extends Authenticatable implements FilamentUser, HasName"),
            )
            ->when(
                value: ! $stringer->contains('public function canAccessPanel'),
                callback: fn (Stringer $stringer) => $stringer
                    ->indent(4)
                    ->prependBeforeLast("}", $content),
            )
            ->save();
    }

    protected function configureFilamentProvider(object $template, Panel $panel, string $panelPath): void
    {
        $stringer = Stringer::for($panelPath);
        $stringer
            ->when(
                value: !$stringer->contains("->discoverClusters"),
                callback: fn (Stringer $stringer) => $stringer
                    ->append("->discoverResources", "->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')", true)
            )
            ->when( /** @phpstan-ignore-next-line */
                value: !$stringer->contains("->plugins([\n"),
                callback: fn (Stringer $stringer) => $stringer
                    ->append("->middleware([", "->plugins([\n", true)
                    ->append("->plugins([\n", "])")
            )
            ->when(
                value: !$stringer->contains("->passwordReset()") && ($template->password_reset ?? true),
                callback: fn (Stringer $stringer) => $stringer
                    ->append("->login()", "->passwordReset()", false)
            )
            ->save();
    }
}