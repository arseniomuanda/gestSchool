<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backups da base de dados (spatie/laravel-backup)
// Os ficheiros ficam em storage/app/private/gestschool/ (visível no host via bind mount).
Schedule::command('backup:clean')->daily()->at('01:30');
Schedule::command('backup:run --only-db')->daily()->at('02:00');

// Notificações automáticas: alunos com faltas excessivas → encarregados
Schedule::command('notifications:faltas-excessivas')->dailyAt('19:00');
