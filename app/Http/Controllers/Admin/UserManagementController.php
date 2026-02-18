<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\UpdateUserEventsRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->syncRoles([$request->string('role')->toString()]);

        return back()->with('status', 'User role updated successfully.');
    }

    public function updateEvents(UpdateUserEventsRequest $request, User $user): RedirectResponse
    {
        $eventIds = Event::query()->whereIn('id', $request->input('events', []));

        if (! $request->user()?->hasRole('admin')) {
            $eventIds->where('created_by', $request->user()?->id);
        }

        $user->events()->sync($eventIds->pluck('id'));

        return to_route('admin.settings.users')
            ->with('status', 'User events updated successfully.');
    }

    public function destroyUser(DeleteUserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->withErrors(['You cannot delete your own account.']);
        }

        $user->delete();

        return to_route('admin.settings.users')
            ->with('status', 'User deleted successfully.');
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $authorization = Gate::inspect('impersonate', $user);

        if ($authorization->denied()) {
            if (filled($authorization->message())) {
                return back()->withErrors([$authorization->message()]);
            }

            abort(403);
        }

        $request->user()?->impersonate($user);

        return to_route('dashboard')
            ->with('status', 'You are now impersonating '.$user->name.'.');
    }
}
