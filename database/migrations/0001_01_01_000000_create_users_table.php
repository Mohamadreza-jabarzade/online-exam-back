<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->unique(); // شماره موبایل
            $table->string('name')->nullable(); // نام کاربری فارسی (بعداً پر می‌شه)
            $table->string('password')->nullable(); // رمز عبور (بعداً پر می‌شه)
            $table->string('otp_code', 6)->nullable(); // کد تایید
            $table->timestamp('otp_expires_at')->nullable(); // زمان انقضای کد
            $table->boolean('is_registered')->default(false); // آیا ثبت‌نام کامل شده؟
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
