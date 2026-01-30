<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API "Le Cube" - Routes Complètes et Corrigées
|--------------------------------------------------------------------------
*/

// ========================================
// ROUTES PUBLIQUES (Ouvertes à tous)
// ========================================

// Authentification Clients
Route::prefix('clients')->group(function () {
    Route::post('inscription', [AuthController::class, 'register']); // POST /api/clients/inscription
    Route::post('connexion', [AuthController::class, 'login']);      // POST /api/clients/connexion
});

// Catalogue des Plats
Route::prefix('plats')->group(function () {
    Route::get('/', [ProductController::class, 'index']);            // GET /api/plats
    Route::get('/{id}', [ProductController::class, 'show']);         // GET /api/plats/{id}
    Route::get('/categories', [CategoryController::class, 'index']); // GET /api/plats/categories
});

// ========================================
// ROUTES AUTHENTIFIÉES (Client Connecté)
// ========================================

// Note : Changement de 'auth:api' en 'auth:sanctum' pour correspondre à tes migrations
Route::middleware('auth:api')->group(function () {
    
    // Profil Client
    Route::get('mon-profil', [AuthController::class, 'me']);
    Route::post('deconnexion', [AuthController::class, 'logout']);

    // Gestion du Panier (US-T3)
    Route::prefix('panier')->group(function () {
        Route::get('/', [CartController::class, 'index']);           // GET /api/panier
        Route::post('/ajouter', [CartController::class, 'add']);     // POST /api/panier/ajouter
        Route::delete('/vider', [CartController::class, 'clear']);   // DELETE /api/panier/vider
    });

    // Gestion des Commandes (US-T2)
    Route::prefix('commandes')->group(function () {
        Route::get('/', [OrderController::class, 'index']);          // GET /api/commandes
        Route::post('/valider', [OrderController::class, 'store']);  // POST /api/commandes/valider
    });

    // ========================================
    // ROUTES ADMINISTRATION (Admin uniquement)
    // ========================================
    
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('tableau-de-bord', [AdminController::class, 'dashboard']);
        Route::get('stats-ventes', [AdminController::class, 'statistics']);
        
        // Gestion des Catégories (Route que nous venons d'ajouter)
        Route::post('categories', [CategoryController::class, 'store']); // POST /api/admin/categories
        
        // Gestion des produits (Plats)
        Route::post('plats', [ProductController::class, 'store']);      // POST /api/admin/plats
        Route::put('plats/{id}', [ProductController::class, 'update']); // PUT /api/admin/plats/{id}
        
        // Gestion des statuts de commandes
        Route::put('commandes/{id}/statut', [AdminController::class, 'updateOrderStatus']);
    });
});

// Fallback pour les erreurs de frappe
Route::fallback(function () {
    return response()->json(['message' => 'Route non trouvée.'], 404);
});