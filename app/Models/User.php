<?php
// Fichier : app/Models/User.php — REMPLACE le contenu existant

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'currency', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relations ──────────────────────────────────────────
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    // ── Accessors utiles ───────────────────────────────────

    // Solde actuel = total revenus - total dépenses
    public function getBalanceAttribute(): float
    {
        $income  = $this->transactions()->where('type', 'income')->sum('amount');
        $expense = $this->transactions()->where('type', 'expense')->sum('amount');
        return $income - $expense;
    }

    // Revenus du mois en cours
    public function getMonthlyIncomeAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'income')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
    }

    // Dépenses du mois en cours
    public function getMonthlyExpenseAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
    }
}