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

            // A,B,C,D
            $table->enum('type', ['A', 'B', 'C', 'D'])->index();

            // متن سوال
            $table->text('question_text');

            // عکس سوال
            $table->string('question_image')->nullable();

            // فصل
            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();

            // شماره صفحه
            $table->unsignedInteger('page_number')->nullable();

            // زمان پاسخ (ثانیه)
            $table->unsignedInteger('time_limit');

            // سطح سختی
            $table->enum('difficulty', [
                'easy',
                'medium',
                'hard'
            ])->default('medium');

            // اهمیت
            $table->enum('importance', [
                'low',
                'medium',
                'high'
            ])->default('medium');

            // گزینه صحیح
            $table->tinyInteger('correct_option_order');

            // توضیحات سوال
            $table->text('explanation');

            // وضعیت
            $table->enum('status', [
                'draft',
                'published',
                'archived'
            ])->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
