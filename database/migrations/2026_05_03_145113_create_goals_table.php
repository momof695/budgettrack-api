<?php
// Fichier : database/migrations/2024_01_01_000003_create_goals_table.php
// COMMANDE : php artisan make:migration create_goals_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');                           // Ex: "Achat moto", "Frais école"
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2);           // Objectif en FCFA
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('icon', 10)->default('🎯');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};