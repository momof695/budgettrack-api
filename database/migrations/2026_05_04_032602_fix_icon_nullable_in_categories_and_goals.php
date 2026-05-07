<?php
// Fichier : database/migrations/xxxx_fix_icon_nullable_in_categories_and_goals.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 10)->nullable()->default(null)->change();
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->string('icon', 10)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 10)->default('📦')->change();
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->string('icon', 10)->default('🎯')->change();
        });
    }
};