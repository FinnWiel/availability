<?php

use App\Models\Event;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('authenticated user can view events management page', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($user)->get(route('events.index'));

    $response->assertOk()->assertSee('Events');
});

test('regular user can create up to two events', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();

    $this->actingAs($user)->post(route('events.store'), [
        'name' => 'Event One',
        'color' => '#2563EB',
    ])->assertRedirect(route('events.index'));

    $this->actingAs($user)->post(route('events.store'), [
        'name' => 'Event Two',
        'color' => '#16A34A',
    ])->assertRedirect(route('events.index'));

    $thirdResponse = $this->actingAs($user)->post(route('events.store'), [
        'name' => 'Event Three',
        'color' => '#DC2626',
    ]);

    $thirdResponse->assertForbidden();

    expect(Event::query()->where('created_by', $user->id)->count())->toBe(2);
});

test('admin can create more than two events', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->admin()->create();

    foreach (range(1, 3) as $index) {
        $this->actingAs($admin)->post(route('events.store'), [
            'name' => 'Admin Event '.$index,
            'color' => '#2563EB',
        ])->assertRedirect(route('events.index'));
    }

    expect(Event::query()->where('created_by', $admin->id)->count())->toBe(3);
});

test('admin sees their events and other users events in separate sections', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->regularUser()->create();

    Event::factory()->create([
        'name' => 'Admin Team Event',
        'created_by' => $admin->id,
    ]);

    Event::factory()->create([
        'name' => 'Other Team Event',
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($admin)->get(route('events.index'));

    $response->assertOk()
        ->assertSee('Your Events')
        ->assertSee("Other People's Events")
        ->assertSee('Admin Team Event')
        ->assertSee('Other Team Event')
        ->assertSee($otherUser->name);
});

test('event creator can invite users when creating and updating an event', function () {
    Role::findOrCreate('user');

    $creator = User::factory()->regularUser()->create();
    $invitee = User::factory()->regularUser()->create();
    $secondInvitee = User::factory()->regularUser()->create();

    $createResponse = $this->actingAs($creator)->post(route('events.store'), [
        'name' => 'Team Sync',
        'color' => '#2563EB',
        'users' => [$invitee->id],
    ]);

    $createResponse->assertRedirect(route('events.index'));

    $event = Event::query()->where('name', 'Team Sync')->firstOrFail();

    expect($event->users()->whereKey($creator)->exists())->toBeTrue();
    expect($event->users()->whereKey($invitee)->exists())->toBeTrue();

    $updateResponse = $this->actingAs($creator)->patch(route('events.update', $event), [
        'name' => 'Team Sync Updated',
        'color' => '#22c55e',
        'users' => [
            ['value' => (string) $secondInvitee->id],
        ],
    ]);

    $updateResponse->assertRedirect(route('events.index'));

    expect($event->fresh()->users()->whereKey($creator)->exists())->toBeTrue();
    expect($event->fresh()->users()->whereKey($secondInvitee)->exists())->toBeTrue();
});

test('regular user cannot edit or delete another users event', function () {
    Role::findOrCreate('user');

    $creator = User::factory()->regularUser()->create();
    $otherUser = User::factory()->regularUser()->create();

    $event = Event::factory()->create([
        'name' => 'Private Event',
        'created_by' => $creator->id,
    ]);

    $this->actingAs($otherUser)->patch(route('events.update', $event), [
        'name' => 'Should Not Update',
        'color' => '#2563EB',
    ])->assertForbidden();

    $this->actingAs($otherUser)->delete(route('events.destroy', $event))
        ->assertForbidden();
});
