<?php

namespace Integrica\Filassist;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilassistServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filassist';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasCommands([
                Console\AutoConfigCommand::class,
            ]);
    }

    public function shortName(): string
    {
        return self::$name;
    }
}
