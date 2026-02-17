<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteEventRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Requests\Admin\UpdateUserEventsRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function users(): View
    {
        return view('admin.settings.users', [
            'users' => User::query()->with('roles')->orderBy('name')->get(),
        ]);
    }

    public function events(): View
    {
        return view('admin.settings.events', [
            'events' => Event::query()->with('users:id')->withCount('users')->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->syncRoles([$request->string('role')->toString()]);

        return back()->with('status', 'User role updated successfully.');
    }

    public function storeEvent(StoreEventRequest $request): RedirectResponse
    {
        Event::query()->create([
            'name' => $request->string('name')->toString(),
            'color' => strtoupper($request->string('color')->toString()),
        ]);

        return to_route('admin.settings.events')
            ->with('status', 'Event created successfully.');
    }

    public function updateEvents(UpdateUserEventsRequest $request, User $user): RedirectResponse
    {
        $user->events()->sync($request->input('events', []));

        return to_route('admin.settings.events')
            ->with('status', 'User events updated successfully.');
    }

    public function updateEvent(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update([
            'name' => $request->string('name')->toString(),
            'color' => strtoupper($request->string('color')->toString()),
        ]);

        $event->users()->sync($request->input('users', []));

        return to_route('admin.settings.events')
            ->with('status', 'Event updated successfully.');
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

    public function destroyEvent(DeleteEventRequest $request, Event $event): RedirectResponse
    {
        $event->delete();

        return to_route('admin.settings.events')
            ->with('status', 'Event deleted successfully.');
    }
}
