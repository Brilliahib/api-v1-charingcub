<?php

namespace App\Policies;

use App\Models\Nanny;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NannyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Nanny $nanny)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Nanny $nanny)
    {
        return $user->id === $nanny->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Nanny $nanny)
    {
        return $user->id === $nanny->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Nanny $nanny)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Nanny $nanny)
    {
        //
    }
}
