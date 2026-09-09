<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('endpoint-checks:dispatch-due')->everyMinute()->withoutOverlapping();

Schedule::command('endpoint-checks:prune')->daily();
