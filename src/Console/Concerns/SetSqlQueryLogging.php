<?php

namespace Integrica\Filassist\Console\Concerns;

use Illuminate\Support\Facades\File;
use Integrica\Scriptorium\Console\EnvUpdater;
use Integrica\Scriptorium\Stringer;

trait SetSqlQueryLogging
{
    public function setSqlQueryLogging(object $template): void
    {
        EnvUpdater::for(base_path('.env'))
            ->setChanges([
                'INTEGRICA_SQL_QUERY_LOGGING' => ($template->sql_query_logging ?? true) ? 'true' : 'false',
            ])
            ->save();

        $path = config_path('logging.php');
    
        if (File::exists($path)) {
            $content = <<<EOL

        'integrica-sql-query-logging' => [
            'driver' => 'daily',
            'path' => storage_path('logs/integrica/sql/sql.log'),
            'level' => 'debug',
            'days' => 14,
        ],
EOL;

            $stringer = Stringer::for($path);
            $stringer
                ->when(
                    value: !$stringer->contains('integrica-sql-query-logging'),
                    callback: fn (Stringer $stringer) => $stringer
                        ->indent(2)
                        ->append("'channels' => [", $content, false),
                )
                ->save();
        } else {
            $this->error('The logging.php file was not found.');
        }

        $content = <<<EOL

        if (env('INTEGRICA_SQL_QUERY_LOGGING', false)) {
            DB::listen(function (\$query) {
                logger()->channel('integrica-sql-query-logging')->info('query', [ 
                    'sql' => \$query->sql, 
                    'bindings' => \$query->bindings, 
                    'time' => \$query->time, 
                ]);
            });
        }
EOL;

        $stringer = Stringer::for(app_path('Providers/AppServiceProvider.php'));
        $stringer
            ->when(
                value: ! $stringer->contains('use Illuminate\Support\Facades\DB;'),
                callback: fn (Stringer $stringer): Stringer => $stringer
                    ->append('use', 'use Illuminate\Support\Facades\DB;')
            )
            ->when(
                value: !$stringer->contains('integrica-sql-query-logging'),
                callback: fn (Stringer $stringer) => $stringer
                    ->appendBlock("public function boot()", $content, false),
            )
            ->save();
    }
}