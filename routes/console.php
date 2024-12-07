<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('app:add-merlin-stock')->everyFiveSeconds()->onSuccess(function () {
    Artisan::call('app:update-bc-product');
});
