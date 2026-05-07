<?php
// Fichier : app/Models/Goal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description',
        'target_amount', 'current_amount', 'deadline', 'icon', 'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'  => 'float',
            'current_amount' => 'float',
            'deadline'       => 'date',
        ];
    }

    // ── Relations ──────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Accessors ──────────────────────────────────────────

    // Pourcentage atteint : ex. 65.5
    public function getProgressPercentAttribute(): float
    {
        if (!$this->target_amount || $this->target_amount == 0) return 0;
        return min(100, round(($this->current_amount / $this->target_amount) * 100, 1));
    }

    // Montant restant à épargner
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    // Objectif atteint ?
    public function getIsCompletedAttribute(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }
}