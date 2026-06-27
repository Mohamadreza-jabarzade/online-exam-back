<?php

namespace App\Http\Controllers\ExamProcess;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

class ExamAnswerController extends Controller
{
    /**
     * ذخیره پاسخ سوال
     *
     * Auto Save
     * Wizard Mode
     */
    public function store(Request $request, Exam $exam, Question $question) {
        abort_if(
            $question->exam_id !== $exam->id,
            404
        );


    }
}
