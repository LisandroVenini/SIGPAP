<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('alertas:evaluar')->dailyAt('06:00');
