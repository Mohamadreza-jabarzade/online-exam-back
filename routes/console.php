<?php

// routes/console.php

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Schedule;

// این کد هر دقیقه به صورت خودکار اجرا می‌شود
Schedule::call(function () {
    Exam::where('status', 'published')
        ->where('start_time', '<=', now())
        ->update([
            'status' => 'in_progress'
        ]);
    Exam::where('status', 'in_progress')
        ->where('end_time', '<=', now())
        ->update([
            'status' => 'closed'
        ]);

    ExamAttempt::where('status', 'in_progress')
        ->whereHas('exam', function ($query) {
            $query->where('end_time', '<=', now());
        })
        ->update([
            'status' => 'submitted',
            'finished_at' => now()
        ]);

})->everyMinute();
