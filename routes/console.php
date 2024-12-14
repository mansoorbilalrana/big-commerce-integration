<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('app:add-merlin-stock')->twiceDaily(1, 13)->after(function () {
    Artisan::call('app:update-bc-product');
});

Schedule::command('app:fetch-ftp-data')->twiceDaily(1, 13)->after(function () {
    Artisan::call('app:update-bc-import-product');
});
