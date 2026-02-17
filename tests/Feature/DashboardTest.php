<?php

use App\Models\Event;
use App\Models\EventAvailability;
use App\Models\User;
use Carbon\Carbon;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows next available date for authenticated user', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $user->events()->attach($event);

    $nextDateTime = Carbon::parse('tomorrow 14:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => $nextDateTime,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Next available date')
        ->assertSee($nextDateTime->format('D, M j Y'))
        ->assertSee($nextDateTime->format('g:i A'));
});
