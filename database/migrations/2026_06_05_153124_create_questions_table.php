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

            $table->string('type')
                ->default('mcq_single');



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
