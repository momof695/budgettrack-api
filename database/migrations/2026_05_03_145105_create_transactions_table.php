<?php
// Fichier : database/migrations/2024_01_01_000002_create_transactions_table.php
// COMMANDE : php artisan make:migration create_transactions_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 15, 2);                 // En FCFA (ex: 5000.00)
            $table->enum('type', ['income', 'expense']);
            $table->string('description')->nullable();
            $table->date('transaction_date');
            // Modes de paiement courants au Burkina Faso
            $table->enum('payment_method', [
                'cash',         // Espèces
                'orange_money', // Orange Money
                'moov_money',   // Moov Money
                'bank',         // Virement bancaire
                'other'
            ])->default('cash');
            $table->string('reference')->nullable();           // Réf. mobile money
            $table->softDeletes();
            $table->timestamps();

            // Index pour accélérer les requêtes de dashboard
            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};