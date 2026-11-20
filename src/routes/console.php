<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('weather:sync')->hourly();
Schedule::command('countries:sync')->weekly();
