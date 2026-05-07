<?php
// Fichier : app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'icon', 'color', 'budget_limit', 'type', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'budget_limit' => 'float',
            'is_default'   => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    // Filtrer par type : Category::ofType('expense')->get()
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Catégories de l'utilisateur connecté
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Accessor : dépenses du mois pour cette catégorie ──
    public function getMonthlySpentAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
    }

    // Pourcentage du budget utilisé (si budget_limit défini)
    public function getBudgetUsagePercentAttribute(): float
    {
        if (!$this->budget_limit || $this->budget_limit == 0) return 0;
        return min(100, round(($this->monthly_spent / $this->budget_limit) * 100, 1));
    }
}