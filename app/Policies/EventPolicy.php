<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('user');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->hasRole('admin')
            || $event->created_by === $user->id
            || $event->users()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->createdEvents()->count() < 2;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->hasRole('admin') || $event->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole('admin') || $event->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->hasRole('admin') || $event->created_by === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->hasRole('admin') || $event->created_by === $user->id;
    }
}
