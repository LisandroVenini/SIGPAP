<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('alertas:evaluar')->dailyAt('06:00');
Schedule::command('feriados:sincronizar')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('riesgo:evaluar')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('predictivo:reentrenar')->weeklyOn(0, '02:00')->withoutOverlapping();
