<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('admin can impersonate another user', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->regularUser()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $targetUser));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($targetUser);
    $this->assertSame($admin->id, session('impersonated_by'));
});

test('admin cannot impersonate themselves', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->from(route('admin.settings.users'))->post(route('admin.users.impersonate', $admin));

    $response->assertRedirect(route('admin.settings.users'));
    $response->assertSessionHasErrors();
    $this->assertAuthenticatedAs($admin);
});

test('non admin cannot impersonate users', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $targetUser = User::factory()->regularUser()->create();

    $response = $this->actingAs($user)->post(route('admin.users.impersonate', $targetUser));

    $response->assertForbidden();
    $this->assertAuthenticatedAs($user);
});

test('impersonating admin can leave impersonation', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->regularUser()->create();

    $this->actingAs($admin)->post(route('admin.users.impersonate', $targetUser));

    $response = $this->post(route('impersonation.leave'));

    $response->assertRedirect(route('admin.settings.users'));
    $this->assertAuthenticatedAs($admin);
    expect(session()->has('impersonated_by'))->toBeFalse();
});
