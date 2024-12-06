<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\EliminarAtributosChatwoot;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('tabla:vaciar-ingresos')->dailyAt('00:00');
        $schedule->command('programaciones:ejecutar')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        // Register your command here
        EliminarAtributosChatwoot::class,
        \App\Console\Commands\BuscarUsuarioChatwoot::class,
    ];
}
