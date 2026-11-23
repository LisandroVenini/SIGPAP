<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('alertas:evaluar')->dailyAt('06:00');
Schedule::command('feriados:sincronizar')->dailyAt('03:00')->withoutOverlapping();
