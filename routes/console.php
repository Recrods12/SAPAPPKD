<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:prune-photos')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('maintenance:prune-temporary-uploads')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('attendance:finalize')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('queue:work database --queue=notifications --stop-when-empty --max-jobs=100 --tries=3 --timeout=60')->everyMinute()->withoutOverlapping(10);
