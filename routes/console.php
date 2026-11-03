<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:expire-unpaid')->everyTenMinutes()->withoutOverlapping();

Schedule::command('invoices:notify-overdue')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('invoices:apply-late-fees')->dailyAt('08:10')->withoutOverlapping();
Schedule::command('invoices:send-reminders')->dailyAt('08:20')->withoutOverlapping();
Schedule::command('bookings:send-extension-reminders')->dailyAt('08:30')->withoutOverlapping();
