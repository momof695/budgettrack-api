<?php
// ============================================================
// Fichier : app/Http/Controllers/Api/GoalController.php
// ============================================================
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $goals = Goal::forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($g) => array_merge($g->toArray(), [
                'progress_percent' => $g->progress_percent,
                'remaining_amount' => $g->remaining_amount,
                'is_completed'     => $g->is_completed,
            ]));
        return response()->json($goals);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'         => 'required|string|max:100',
            'description'   => 'nullable|string',
            'target_amount' => 'required|numeric|min:1',
            'deadline'      => 'nullable|date|after:today',
            'icon'          => 'nullable|string|max:10',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['icon']    = $data['icon'] ?? null; // accepte null explicitement
        $goal = Goal::create($data);
        return response()->json(array_merge($goal->toArray(), [
            'progress_percent' => $goal->progress_percent,
            'remaining_amount' => $goal->remaining_amount,
            'is_completed'     => $goal->is_completed,
        ]), 201);
    }

    public function show(Request $request, Goal $goal): JsonResponse
    {
        $this->checkOwner($request, $goal);
        return response()->json(array_merge($goal->toArray(), [
            'progress_percent' => $goal->progress_percent,
            'remaining_amount' => $goal->remaining_amount,
            'is_completed'     => $goal->is_completed,
        ]));
    }

    public function update(Request $request, Goal $goal): JsonResponse
    {
        $this->checkOwner($request, $goal);
        $data = $request->validate([
            'title'         => 'sometimes|string|max:100',
            'description'   => 'nullable|string',
            'target_amount' => 'sometimes|numeric|min:1',
            'deadline'      => 'nullable|date',
            'icon'          => 'nullable|string|max:10',
            'status'        => 'sometimes|in:active,completed,cancelled',
        ]);
        $goal->update($data);
        return response()->json(array_merge($goal->fresh()->toArray(), [
            'progress_percent' => $goal->fresh()->progress_percent,
            'remaining_amount' => $goal->fresh()->remaining_amount,
            'is_completed'     => $goal->fresh()->is_completed,
        ]));
    }

    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        $this->checkOwner($request, $goal);
        $goal->delete();
        return response()->json(['message' => 'Objectif supprimé.']);
    }

    public function deposit(Request $request, Goal $goal): JsonResponse
    {
        $this->checkOwner($request, $goal);
        $request->validate(['amount' => 'required|numeric|min:1']);

        $goal->increment('current_amount', $request->amount);
        $fresh = $goal->fresh();

        if ($fresh->current_amount >= $fresh->target_amount) {
            $fresh->update(['status' => 'completed']);
        }

        return response()->json(array_merge($fresh->toArray(), [
            'progress_percent' => $fresh->progress_percent,
            'remaining_amount' => $fresh->remaining_amount,
            'is_completed'     => $fresh->is_completed,
        ]));
    }

    private function checkOwner(Request $request, Goal $goal): void
    {
        if ($goal->user_id !== $request->user()->id) abort(403);
    }
}


