<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('creator_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedInteger('duration_minutes')->default(30);

            $table->boolean('show_result')->default(true);
            $table->boolean('show_correct_answers')->default(true);

            $table->boolean('random_questions')->default(false);
            $table->boolean('random_options')->default(false);

            $table->enum('status', [
                'draft',
                'published',
                'closed'
            ])->default('draft');
            $table->timestamp('published_at')
                ->nullable();
            $table->timestamps();

            $table->index('creator_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
