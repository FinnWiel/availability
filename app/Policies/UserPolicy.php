<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function impersonate(User $user, User $targetUser): Response
    {
        if ($user->is($targetUser)) {
            return Response::deny('You cannot impersonate your own account.');
        }

        if (! $user->hasRole('admin')) {
            return Response::deny();
        }

        return Response::allow();
    }
}
