<?php

use App\Notifications\PasswordResetNotification;
use App\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('test', function () {
    $user = User::find(5);
    $user->notify(
        new PasswordResetNotification('1234567890')
    );
})->describe('Display an inspiring quote');
