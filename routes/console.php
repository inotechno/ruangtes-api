<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Expire subscriptions daily at midnight
Schedule::command('subscriptions:expire')
    ->daily()
    ->at('00:00')
    ->timezone('Asia/Jakarta')
    ->description('Expire subscriptions that have passed their expiry date');

// Schedule: Generate post-paid invoices daily at 1:00 AM
Schedule::command('invoices:generate-post-paid')
    ->daily()
    ->at('01:00')
    ->timezone('Asia/Jakarta')
    ->description('Generate invoices for post-paid subscriptions that have expired');
