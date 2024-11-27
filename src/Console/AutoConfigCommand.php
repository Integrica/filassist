<?php

namespace Integrica\Filassist\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class AutoConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrica:auto-config {--template= : Project template file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Filament from command line without editing the files manually and minimal input.';
    
    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * @var array{'template': string | null}
     */
    protected array $options;

    protected string $envPath;
    
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->options = $this->options();

        return Command::SUCCESS;
    }

}
