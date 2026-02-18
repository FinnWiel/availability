<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('admin sees users in settings navigation', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('profile.edit'));

    $response->assertOk()
        ->assertSee('Users')
        ->assertSee('Profile');
});

test('non admin does not see management links in settings navigation', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertDontSee(route('admin.settings.users'))
        ->assertDontSee('Users')
        ->assertSee('Profile');
});

test('settings route redirects to profile page', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/settings')
        ->assertRedirect('/settings/profile');
});
