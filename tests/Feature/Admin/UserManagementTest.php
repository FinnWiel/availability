<?php

use App\Models\Event;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('admin can view the user management page', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.settings.users'));

    $response->assertOk()->assertSee('Users');
});

test('non admin users cannot view the user management page', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($user)->get(route('admin.settings.users'));

    $response->assertForbidden();
});

test('admin can update a user role', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->regularUser()->create();

    $response = $this->actingAs($admin)->patch(route('admin.users.update-role', $targetUser), [
        'role' => 'admin',
    ]);

    $response->assertRedirect();

    expect($targetUser->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can create an event from manage events tab', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.events.store'), [
        'name' => 'Annual Meetup',
        'color' => '#ff9900',
    ]);

    $response->assertRedirect(route('admin.settings.events'));

    $this->assertDatabaseHas('events', [
        'name' => 'Annual Meetup',
        'color' => '#FF9900',
    ]);
});

test('admin can assign multiple events to a user', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->regularUser()->create();

    $events = Event::factory()->count(2)->create();

    $response = $this->actingAs($admin)->patch(route('admin.users.update-events', $targetUser), [
        'events' => $events->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.settings.events'));

    expect($targetUser->fresh()->events()->count())->toBe(2);
});

test('admin can update an event and assigned users', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $assignedUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['name' => 'Old Event Name']);

    $response = $this->actingAs($admin)->patch(route('admin.events.update', $event), [
        'name' => 'New Event Name',
        'color' => '#22c55e',
        'users' => [$assignedUser->id],
    ]);

    $response->assertRedirect(route('admin.settings.events'));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'name' => 'New Event Name',
        'color' => '#22C55E',
    ]);

    expect($event->fresh()->users()->whereKey($assignedUser)->exists())->toBeTrue();
});

test('admin can update an event with flux listbox user payload', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $assignedUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['name' => 'Test Event']);

    $response = $this->actingAs($admin)->patch(route('admin.events.update', $event), [
        'name' => 'Test Event',
        'color' => '#22c55e',
        'users' => [
            ['value' => (string) $assignedUser->id],
        ],
    ]);

    $response->assertRedirect(route('admin.settings.events'));

    expect($event->fresh()->users()->whereKey($assignedUser)->exists())->toBeTrue();
});

test('admin can update an event with json encoded user payload', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $assignedUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['name' => 'Test Event Json']);

    $response = $this->actingAs($admin)->patch(route('admin.events.update', $event), [
        'name' => 'Test Event Json',
        'color' => '#22c55e',
        'users' => [
            json_encode(['value' => (string) $assignedUser->id], JSON_THROW_ON_ERROR),
        ],
    ]);

    $response->assertRedirect(route('admin.settings.events'));

    expect($event->fresh()->users()->whereKey($assignedUser)->exists())->toBeTrue();
});

test('admin can delete an event', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.events.destroy', $event));

    $response->assertRedirect(route('admin.settings.events'));

    $this->assertDatabaseMissing('events', [
        'id' => $event->id,
    ]);
});

test('admin can delete another user', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->regularUser()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $targetUser));

    $response->assertRedirect(route('admin.settings.users'));

    $this->assertDatabaseMissing('users', [
        'id' => $targetUser->id,
    ]);
});
