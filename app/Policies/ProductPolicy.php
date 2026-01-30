<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['vendor', 'admin']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
