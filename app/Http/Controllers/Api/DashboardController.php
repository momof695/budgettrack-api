<?php
// ============================================================
// Fichier : app/Http/Controllers/Api/DashboardController.php
// ============================================================
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $byCategory = Transaction::forUser($user->id)
            ->expense()
            ->currentMonth()
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->get()
            ->map(fn($t) => [
                'category' => $t->category?->name ?? 'Sans catégorie',
                'icon'     => $t->category?->icon  ?? '',
                'color'    => $t->category?->color  ?? '#6366f1',
                'total'    => $t->total,
            ]);

        $monthly = Transaction::forUser($user->id)
            ->select(
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('YEAR(transaction_date) as year'),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->where('transaction_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('year', 'month', 'type')
            ->orderBy('year')->orderBy('month')
            ->get();

        $recent = Transaction::forUser($user->id)
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'balance'         => $user->balance,
            'monthly_income'  => $user->monthly_income,
            'monthly_expense' => $user->monthly_expense,
            'by_category'     => $byCategory,
            'monthly_chart'   => $monthly,
            'recent'          => $recent,
        ]);
    }
}