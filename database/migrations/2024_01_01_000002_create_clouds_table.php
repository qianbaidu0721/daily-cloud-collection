<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clouds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('所属用户');
            $table->string('image_path')->comment('图片相对路径');
            $table->unsignedTinyInteger('mood')->comment('心情等级 1-5');
            $table->string('mood_label', 32)->nullable()->comment('心情标签文案');
            $table->string('location_city', 64)->nullable()->comment('所在城市');
            $table->decimal('location_lat', 10, 7)->nullable()->comment('纬度');
            $table->decimal('location_lng', 10, 7)->nullable()->comment('经度');
            $table->text('note')->nullable()->comment('备注');
            $table->string('cloud_type', 32)->nullable()->comment('云类型');
            $table->date('collect_date')->comment('收集日期');
            $table->timestamps();

            $table->unique(['user_id', 'collect_date']);
            $table->index('user_id');
            $table->index('collect_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clouds');
    }
};
