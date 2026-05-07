<?php
// ============================================================
// Fichier : app/Http/Controllers/Api/CategoryController.php
// ============================================================
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cats = Category::forUser($request->user()->id)
            ->withCount('transactions')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                $cat->monthly_spent       = $cat->monthly_spent;
                $cat->budget_usage_percent = $cat->budget_usage_percent;
                return $cat;
            });
        return response()->json($cats);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'icon'         => 'nullable|string|max:10',
            'color'        => 'nullable|string|max:7',
            'budget_limit' => 'nullable|numeric|min:0',
            'type'         => 'required|in:income,expense',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['icon']    = $data['icon'] ?? null; // accepte null explicitement
        return response()->json(Category::create($data), 201);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $this->checkOwner($request, $category);
        return response()->json($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->checkOwner($request, $category);
        $data = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'icon'         => 'nullable|string|max:10',
            'color'        => 'nullable|string|max:7',
            'budget_limit' => 'nullable|numeric|min:0',
            'type'         => 'sometimes|in:income,expense',
        ]);
        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->checkOwner($request, $category);
        $category->delete();
        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    private function checkOwner(Request $request, Category $category): void
    {
        if ($category->user_id !== $request->user()->id) abort(403);
    }
}


