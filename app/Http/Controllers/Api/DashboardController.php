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

        // Compatible PostgreSQL et MySQL
        $monthly = Transaction::forUser($user->id)
            ->select(
                DB::raw("EXTRACT(MONTH FROM transaction_date) as month"),
                DB::raw("EXTRACT(YEAR FROM transaction_date) as year"),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->where('transaction_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy(DB::raw("EXTRACT(YEAR FROM transaction_date)"), DB::raw("EXTRACT(MONTH FROM transaction_date)"), 'type')
            ->orderBy(DB::raw("EXTRACT(YEAR FROM transaction_date)"))
            ->orderBy(DB::raw("EXTRACT(MONTH FROM transaction_date)"))
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