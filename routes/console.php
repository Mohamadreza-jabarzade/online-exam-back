<?php

// routes/console.php

use App\Models\Exam;
use Illuminate\Support\Facades\Schedule;

// این کد هر دقیقه به صورت خودکار اجرا می‌شود
Schedule::call(function () {
    Exam::where('status', 'published')
        ->where('end_time', '<=', now())
        ->update([
            'status' => 'closed'
        ]);
})->everyMinute();
