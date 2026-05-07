<?php
// Fichier : database/migrations/2024_01_01_000001_create_categories_table.php
// COMMANDE : php artisan make:migration create_categories_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');                           // Ex: Nourriture, Transport
            $table->string('icon', 10)->default('📦');       // Emoji pour l'UI mobile
            $table->string('color', 7)->default('#6366f1');  // Couleur hex pour les graphiques
            $table->decimal('budget_limit', 15, 2)->nullable(); // Plafond mensuel en FCFA
            $table->enum('type', ['income', 'expense'])->default('expense');
            $table->boolean('is_default')->default(false);   // Catégorie système
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};