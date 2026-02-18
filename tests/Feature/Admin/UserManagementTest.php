<?php

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
