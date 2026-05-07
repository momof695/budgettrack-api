<?php
// Fichier : app/Http/Controllers/Api/TransactionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // ── Liste des transactions ─────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::forUser($request->user()->id)
            ->with('category')
            ->orderBy('transaction_date', 'desc');

        // Filtres optionnels
        if ($request->type)        $query->where('type', $request->type);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->month)       $query->whereMonth('transaction_date', $request->month);
        if ($request->year)        $query->whereYear('transaction_date', $request->year ?? now()->year);

        $transactions = $query->paginate(20);

        return response()->json($transactions);
    }

    // ── Créer une transaction ──────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'           => 'required|numeric|min:1',
            'type'             => 'required|in:income,expense',
            'category_id'      => 'nullable|exists:categories,id',
            'description'      => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'payment_method'   => 'sometimes|in:cash,orange_money,moov_money,bank,other',
            'reference'        => 'nullable|string|max:100',
        ]);

        $data['user_id'] = $request->user()->id;
        $transaction = Transaction::create($data);
        $transaction->load('category');

        return response()->json($transaction, 201);
    }

    // ── Afficher une transaction ───────────────────────────
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeOwner($request, $transaction);
        return response()->json($transaction->load('category'));
    }

    // ── Modifier une transaction ───────────────────────────
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeOwner($request, $transaction);

        $data = $request->validate([
            'amount'           => 'sometimes|numeric|min:1',
            'type'             => 'sometimes|in:income,expense',
            'category_id'      => 'nullable|exists:categories,id',
            'description'      => 'nullable|string|max:255',
            'transaction_date' => 'sometimes|date',
            'payment_method'   => 'sometimes|in:cash,orange_money,moov_money,bank,other',
            'reference'        => 'nullable|string|max:100',
        ]);

        $transaction->update($data);
        return response()->json($transaction->load('category'));
    }

    // ── Supprimer une transaction ──────────────────────────
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeOwner($request, $transaction);
        $transaction->delete(); // Soft delete
        return response()->json(['message' => 'Transaction supprimée.']);
    }

    // ── Vérifier que la transaction appartient à l'user ───
    private function authorizeOwner(Request $request, Transaction $transaction): void
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }
    }
}