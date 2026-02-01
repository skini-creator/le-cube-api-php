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
| API "Le Cube" - Routes Globales (Architecture JWT)
|--------------------------------------------------------------------------
*/

// ========================================
// 1. ROUTES PUBLIQUES
// ========================================

Route::prefix('clients')->group(function () {
    Route::post('inscription', [AuthController::class, 'register']);
    Route::post('connexion', [AuthController::class, 'login']);
});

Route::prefix('plats')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// ========================================
// 2. ROUTES PROTÉGÉES (JWT Guard)
// ========================================

Route::middleware('auth:api')->group(function () {
    
    // Profil utilisateur & Adresses (Pour corriger l'erreur 422)
    Route::get('mon-profil', [AuthController::class, 'me']);
    Route::post('deconnexion', [AuthController::class, 'logout']);
    
    Route::prefix('adresses')->group(function () {
        Route::post('/', [AuthController::class, 'addAddress']); // ✅ Nouvelle route ajoutée
    });

    // Gestion du Panier (US-T3)
    Route::prefix('panier')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/ajouter', [CartController::class, 'add']);
        Route::delete('/vider', [CartController::class, 'clear']);
    });

    // Gestion des Commandes (US-T2)
    Route::prefix('commandes')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/valider', [OrderController::class, 'store']);
    });

    // ========================================
    // 3. ROUTES ADMIN (Middleware Role + JWT)
    // ========================================
    
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('tableau-de-bord', [AdminController::class, 'dashboard']);
        Route::get('stats-ventes', [AdminController::class, 'statistics']);
        
        Route::post('categories', [CategoryController::class, 'store']);
        Route::post('plats', [ProductController::class, 'store']);
        Route::put('plats/{id}', [ProductController::class, 'update']);
        Route::put('commandes/{id}/statut', [AdminController::class, 'updateOrderStatus']);
    });
});

Route::fallback(function () {
    return response()->json(['message' => 'Route non trouvée.'], 404);
});