<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;  // ← AJOUTER CET IMPORT

class PaymentController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();  // ← Utiliser Auth::user()
        
        $payments = $user->orders()
            ->with('payments')
            ->get()
            ->pluck('payments')
            ->flatten();

        return response()->json([
            'success' => true, 
            'payments' => $payments
        ]);
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();  // ← Utiliser Auth::user()
        
        $payment = Payment::whereHas('order', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        return response()->json([
            'success' => true, 
            'payment' => $payment
        ]);
    }
}