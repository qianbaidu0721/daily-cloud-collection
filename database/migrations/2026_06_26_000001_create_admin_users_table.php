<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->comment('管理员姓名');
            $table->string('email')->unique()->comment('登录邮箱');
            $table->string('password')->comment('密码哈希');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
