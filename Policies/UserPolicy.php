<?php

namespace Modules\User\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Entities\User;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     //
    // }

    public function before(User $user, $ability)
    {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can list the users.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function listAll(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can view the user.
     *
     * @param  \App\User  $user
     * @param  \App\User  $u
     * @return mixed
     */
    public function view(User $user, User $u)
    {
        return $user->is_admin || $user->id === $u->id;
    }

    /**
     * Determine whether the user can create users.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param  \App\User  $user
     * @param  \App\User  $u
     * @return mixed
     */
    public function update(User $user, User $u)
    {
        return $user->is_admin || $user->id === $u->id;
    }

    /**
     * Determine whether the user can delete the user.
     *
     * @param  \App\User  $user
     * @param  \App\User  $u
     * @return mixed
     */
    public function delete(User $user, User $u)
    {
        return $user->is_admin && $user->id !== $u->id;
    }
}
