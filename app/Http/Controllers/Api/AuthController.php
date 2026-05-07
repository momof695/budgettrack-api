<?php
// Fichier : app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ── Inscription ────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user  = User::create($data);
        $token = $user->createToken('budgettrack')->plainTextToken;

        // Créer des catégories par défaut pour le nouvel utilisateur
        $this->createDefaultCategories($user);

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    // ── Connexion ──────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        // Supprimer les anciens tokens et en créer un nouveau
        $user->tokens()->delete();
        $token = $user->createToken('budgettrack')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    // ── Déconnexion ────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    // ── Utilisateur connecté ───────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user'            => $user,
            'balance'         => $user->balance,
            'monthly_income'  => $user->monthly_income,
            'monthly_expense' => $user->monthly_expense,
        ]);
    }

    // ── Modifier profil ────────────────────────────────────
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:100',
            'phone'    => 'sometimes|nullable|string|max:20',
            'currency' => 'sometimes|string|max:10',
        ]);

        $request->user()->update($data);
        return response()->json(['user' => $request->user()->fresh()]);
    }

    // ── Changer mot de passe ───────────────────────────────
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => $request->password]);
        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    // ── Mot de passe oublié ────────────────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Un lien de réinitialisation a été envoyé à votre email.'
                : 'Impossible d\'envoyer le lien. Vérifiez votre email.',
        ], $status === Password::RESET_LINK_SENT ? 200 : 422);
    }

    // ── Réinitialiser le mot de passe ──────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return response()->json([
            'message' => $status === Password::PASSWORD_RESET
                ? 'Mot de passe réinitialisé avec succès.'
                : 'Lien invalide ou expiré.',
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }

    // ── Catégories par défaut à la création du compte ──────
    private function createDefaultCategories(User $user): void
    {
        $defaults = [
            ['name' => 'Nourriture',   'icon' => '🍽️', 'color' => '#ef4444', 'type' => 'expense', 'budget_limit' => 50000],
            ['name' => 'Transport',    'icon' => '🚗', 'color' => '#f97316', 'type' => 'expense', 'budget_limit' => 20000],
            ['name' => 'Santé',        'icon' => '🏥', 'color' => '#22c55e', 'type' => 'expense', 'budget_limit' => 15000],
            ['name' => 'Éducation',    'icon' => '📚', 'color' => '#3b82f6', 'type' => 'expense', 'budget_limit' => 30000],
            ['name' => 'Loisirs',      'icon' => '🎉', 'color' => '#a855f7', 'type' => 'expense', 'budget_limit' => 10000],
            ['name' => 'Salaire',      'icon' => '💰', 'color' => '#10b981', 'type' => 'income',  'budget_limit' => null],
            ['name' => 'Commerce',     'icon' => '🏪', 'color' => '#eab308', 'type' => 'income',  'budget_limit' => null],
        ];

        foreach ($defaults as $cat) {
            $user->categories()->create(array_merge($cat, ['is_default' => true]));
        }
    }
}