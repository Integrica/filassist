<?php

namespace Integrica\Filassist\Console\Concerns;

use Illuminate\Support\Facades\DB;
use Integrica\Scriptorium\Console\EnvUpdater;

trait UpdateEnvFile
{
    public function updateEnvFile(object $template): void
    {
        $useTemplateSettings = $template->db?->database != env('DB_DATABASE');

        EnvUpdater::for(base_path('.env'))
            // ->text(
            //     key: 'APP_NAME',
            //     label: 'Application name?',
            //     default: config('app.name'),
            //     required: true)
            ->appendChanges([
                'APP_NAME' => $template->name,
                'APP_LOCALE' => $template->locale ?? 'en',
                'APP_FALLBACK_LOCALE' => $template->fallback_locale ?? 'en',
                'APP_TIMEZONE' => $template->timezone ?? 'UTC',
                'LOG_CHANNEL' => $template->log_channel ?? 'daily',
                'DB_CONNECTION' => $template->db?->connection ?? 'mysql',
                'DB_HOST' => $template->db?->host ?? '127.0.0.1',
                'DB_PORT' => $template->db?->port ?? '3306',
                // 'DB_DATABASE' => $template->db?->database ?? str_replace(' ', '', str_replace(' ', '_', strtolower($template->name))),
                'DB_DATABASE' => $template->db?->database ?? str_replace(' ', '', $template->name),
                'DB_USERNAME' => $template->db?->username ?? 'homestead',
                'DB_PASSWORD' => $template->db?->password ?? 'secret',
                
                'SESSION_DRIVER' => $template->session_driver ?? 'file',
                
                'CACHE_STORE' => $template->cache_store ?? 'file',
            ])
            // ->dumpChanges()
            ->save();
        
        if ($useTemplateSettings) {
            // TODO: I think that this is not the best solution but for now it will do
            $settings = \Illuminate\Support\Facades\Config::get('database.connections.' . env('DB_CONNECTION'));
            
            $settings['host'] = $template->db?->host ?? '127.0.0.1' ?? env('DB_HOST');
            $settings['port'] = $template->db?->port ?? '3306' ?? env('DB_PORT');
            $settings['database'] = $template->db?->database ?? str_replace(' ', '', $template->name) ?? env('DB_DATABASE');
            $settings['username'] = $template->db?->username ?? 'homestead' ?? env('DB_USERNAME');
            $settings['password'] = $template->db?->password ?? 'secret' ?? env('DB_PASSWORD');

            \Illuminate\Support\Facades\Config::set('database.connections.' . env('DB_CONNECTION'), $settings);
        
            // Reinitialize the database connection
            DB::purge();
            DB::reconnect();
        }
    }
}