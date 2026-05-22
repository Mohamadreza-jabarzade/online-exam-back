<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اضافه کردن فیلدهای جدید به جدول users
        Schema::table('users', function (Blueprint $table) {
            // فیلد شماره موبایل - unique و index
            $table->string('mobile', 11)->unique()->after('id');
            // فیلد نام کاربری به فارسی
            $table->string('username')->nullable()->after('name');
            // کد تایید (OTP)
            $table->string('otp_code', 6)->nullable();
            // زمان انقضای کد تایید
            $table->timestamp('otp_expires_at')->nullable();
            // وضعیت تایید شماره موبایل
            $table->boolean('mobile_verified_at')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'username',
                'otp_code',
                'otp_expires_at',
                'mobile_verified_at'
            ]);
        });
    }
};
