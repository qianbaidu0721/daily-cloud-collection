<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clouds', function (Blueprint $table) {
            $table->boolean('is_public')
                ->default(false)
                ->after('collect_date')
                ->comment('是否共享到广场 0=私有 1=公开');

            $table->index(['is_public', 'collect_date'], 'clouds_public_collect_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('clouds', function (Blueprint $table) {
            $table->dropIndex('clouds_public_collect_date_index');
            $table->dropColumn('is_public');
        });
    }
};
