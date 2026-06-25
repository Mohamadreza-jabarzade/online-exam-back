<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_bank_id')->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('creator_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->longText('content');

            $table->string('type');
            //MULTIPLE_CHOICE_FOUR_OPTIONS: "چهار گزینه‌ای",
            //MULTIPLE_CHOICE: "چند گزینه‌ای",
            //TRUE_FALSE: "درست / نادرست",
            //SHORT_ANSWER: "پاسخ کوتاه",
            //LONG_ANSWER: "پاسخ تشریحی",
            //FILL_IN_THE_BLANK: "جای خالی",
            //MATCHING: "تطبیقی",
            //DESCRIPTIVE: "تشریحی",

            $table->timestamps();

            $table->index('question_bank_id');
            $table->index('creator_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
