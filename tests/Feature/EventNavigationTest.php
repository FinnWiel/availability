<?php

use App\Models\Event;
use App\Models\EventAvailability;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('user sees assigned events in sidebar navigation', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['name' => 'Community Day']);
    $user->events()->attach($event);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertSee('Community Day');
});

test('user can view assigned event page', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['color' => '#DC2626']);
    $user->events()->attach($event);

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertOk()
        ->assertSee($event->name)
        ->assertSee('Availability Calendar')
        ->assertSee('Add Availability')
        ->assertSee('goToPreviousMonth')
        ->assertSee('goToNextMonth')
        ->assertSee('goToToday');
});

test('user cannot view unassigned event page', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertForbidden();
});

test('assigned user can store event availability slot', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $user->events()->attach($event);

    $response = $this->actingAs($user)->post(route('events.availability.store', $event), [
        'date' => now()->addDay()->toDateString(),
        'time' => '09:00',
    ]);

    $response->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('event_availabilities', [
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);
});

test('event page shows next datetime when all users are available', function () {
    Role::findOrCreate('user');

    $firstUser = User::factory()->regularUser()->create();
    $secondUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create();

    $event->users()->attach([$firstUser->id, $secondUser->id]);

    $sharedDateTime = Carbon::parse('tomorrow 14:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $firstUser->id,
        'available_at' => $sharedDateTime,
        'location' => 'my-place',
    ]);

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $secondUser->id,
        'available_at' => $sharedDateTime,
    ]);

    $response = $this->actingAs($firstUser)->get(route('events.show', $event));

    $response->assertOk()
        ->assertSee('availability-avatar-'.$sharedDateTime->toDateString().'-user-'.$firstUser->id)
        ->assertSee('availability-avatar-'.$sharedDateTime->toDateString().'-user-'.$secondUser->id)
        ->assertSee('availability-host-pin-'.$sharedDateTime->toDateString());
});

test('event page shows host pin off when all users are available without location', function () {
    Role::findOrCreate('user');

    $firstUser = User::factory()->regularUser()->create();
    $secondUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create();

    $event->users()->attach([$firstUser->id, $secondUser->id]);

    $sharedDateTime = Carbon::parse('tomorrow 16:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $firstUser->id,
        'available_at' => $sharedDateTime,
    ]);

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $secondUser->id,
        'available_at' => $sharedDateTime,
    ]);

    $response = $this->actingAs($firstUser)->get(route('events.show', $event));

    $response->assertOk()
        ->assertSee('availability-host-pin-off-'.$sharedDateTime->toDateString());
});

test('event page includes attendees modal trigger', function () {
    Role::findOrCreate('user');

    $firstUser = User::factory()->regularUser()->create();
    $secondUser = User::factory()->regularUser()->create();
    $event = Event::factory()->create(['name' => 'test']);
    $event->users()->attach([$firstUser->id, $secondUser->id]);

    $this->assertDatabaseHas('event_user', [
        'event_id' => $event->id,
        'user_id' => $firstUser->id,
    ]);

    $this->assertDatabaseHas('event_user', [
        'event_id' => $event->id,
        'user_id' => $secondUser->id,
    ]);

    $response = $this->actingAs($firstUser)->get(route('events.show', $event));

    $response->assertOk()
        ->assertSee('View Attendees')
        ->assertSee($firstUser->name)
        ->assertSee($secondUser->name);
});

test('assigned user can store all day event availability slot', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $user->events()->attach($event);

    $response = $this->actingAs($user)->post(route('events.availability.store', $event), [
        'date' => now()->addDays(2)->toDateString(),
        'time' => 'all-day',
    ]);

    $response->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('event_availabilities', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'is_all_day' => 1,
    ]);
});

test('assigned user can remove own event availability slot', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $user->events()->attach($event);

    $availability = EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => Carbon::parse('tomorrow 10:00'),
    ]);

    $response = $this->actingAs($user)->delete(route('events.availability.destroy', [$event, $availability]));

    $response->assertRedirect(route('events.show', $event));

    $this->assertDatabaseMissing('event_availabilities', [
        'id' => $availability->id,
    ]);
});

test('event modal shows remove availability action when selected day is already available', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $event->users()->attach($user);

    $availableAt = Carbon::parse('tomorrow 10:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => $availableAt,
    ]);

    $this->actingAs($user);

    Livewire::test('events.show-calendar', [
        'event' => $event->fresh()->load(['users:id,name,email', 'availabilities.user:id,name,email']),
        'nextCommonDateTime' => null,
        'userAvailabilitySlots' => EventAvailability::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->orderBy('available_at')
            ->get(),
    ])
        ->call('setSelectedDay', $availableAt->toDateString(), 'Tomorrow')
        ->assertSee('Remove Availability')
        ->assertDontSee('Add Availability');
});

test('event modal shows add availability action when selected day is not available', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $event->users()->attach($user);

    $selectedDate = Carbon::parse('tomorrow')->toDateString();

    $this->actingAs($user);

    Livewire::test('events.show-calendar', [
        'event' => $event->fresh()->load(['users:id,name,email', 'availabilities.user:id,name,email']),
        'nextCommonDateTime' => null,
        'userAvailabilitySlots' => EventAvailability::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->orderBy('available_at')
            ->get(),
    ])
        ->call('setSelectedDay', $selectedDate, 'Tomorrow')
        ->assertSee('Add Availability')
        ->assertDontSee('Remove Availability');
});

test('assigned user can save hosting preference for selected day availability', function () {
    Role::findOrCreate('user');

    $user = User::factory()->regularUser()->create();
    $event = Event::factory()->create();
    $event->users()->attach($user);

    $availableAt = Carbon::parse('tomorrow 10:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => $availableAt,
    ]);

    $this->actingAs($user);

    Livewire::test('events.show-calendar', [
        'event' => $event->fresh()->load(['users:id,name,email', 'availabilities.user:id,name,email']),
        'nextCommonDateTime' => null,
        'userAvailabilitySlots' => EventAvailability::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->orderBy('available_at')
            ->get(),
    ])
        ->call('setSelectedDay', $availableAt->toDateString(), 'Tomorrow')
        ->set('selectedAtMyPlace', true)
        ->call('saveLocation');

    $this->assertDatabaseHas('event_availabilities', [
        'event_id' => $event->id,
        'user_id' => $user->id,
        'location' => 'my-place',
    ]);
});
