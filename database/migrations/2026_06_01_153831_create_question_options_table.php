<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {

            $table->id();

            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();

            // متن گزینه
            $table->text('option_text')->nullable();

            // عکس گزینه
            $table->string('option_image')->nullable();

            // ترتیب 1 تا 4
            $table->tinyInteger('order');

            $table->timestamps();

            // هر سوال فقط 4 گزینه با شماره یکتا داشته باشد
            $table->unique([
                'question_id',
                'order'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
