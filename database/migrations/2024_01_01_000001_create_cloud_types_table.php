<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->comment('云类型名称，如积云、层云');
            $table->string('code', 32)->unique()->comment('类型编码');
            $table->string('description')->nullable()->comment('类型描述');
            $table->string('icon')->nullable()->comment('图标 URL 或路径');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序权重，越小越靠前');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_types');
    }
};
