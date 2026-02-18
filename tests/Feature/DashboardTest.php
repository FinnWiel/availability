<?php

use App\Models\Event;
use App\Models\EventAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()->assertSee('Customize widgets');
});

test('dashboard widget preferences are persisted for users', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('dashboard.widgets-modal');
    $widgets = $component->get('widgets');

    $targetIndex = collect($widgets)->search(fn (array $widget): bool => $widget['id'] === 'next-available');

    expect($targetIndex)->not->toBeFalse();

    $component
        ->set('widgets.'.$targetIndex.'.enabled', false)
        ->set('widgets.'.$targetIndex.'.col_span', 2);

    $preferences = $user->refresh()->dashboard_widgets;

    $nextAvailableWidget = collect($preferences)->firstWhere('id', 'next-available');

    expect($nextAvailableWidget)->toBe([
        'id' => 'next-available',
        'enabled' => false,
        'col_span' => 2,
        'row_span' => 1,
    ]);
});

test('dashboard shows an empty state when all widgets are disabled', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('dashboard.widgets-modal');
    $widgets = $component->get('widgets');

    foreach ($widgets as $index => $widget) {
        $component->set('widgets.'.$index.'.enabled', false);
    }

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('No widgets enabled')
        ->assertDontSee('Next Available Date');
});

test('dashboard auto-discovers widgets from the widgets directory', function () {
    $widgetPath = resource_path('views/components/widgets/auto-discover-widget.blade.php');

    File::put($widgetPath, <<<'BLADE'
<div data-test="auto-discover-widget">Auto Discover Widget Content</div>
BLADE);

    try {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('dashboard.widgets-modal')
            ->assertSee('Auto Discover Widget');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Auto Discover Widget Content');
    } finally {
        File::delete($widgetPath);
    }
});

test('dashboard widget order can be changed from the modal and is reflected on the page', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('dashboard.widgets-modal');
    $widgets = $component->get('widgets');

    $nextAvailableIndex = collect($widgets)->search(fn (array $widget): bool => $widget['id'] === 'next-available');

    expect($nextAvailableIndex)->toBe(0);

    $component->call('sortWidget', 'next-available', 1);

    $orderedWidgetIds = collect($user->refresh()->dashboard_widgets)
        ->pluck('id')
        ->values()
        ->all();

    expect($orderedWidgetIds)->toBe([
        'your-events',
        'next-available',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertSeeInOrder([
        'Your events',
        'No upcoming availability yet',
    ]);
});

test('dashboard modal expands one widget at a time like an accordion', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('dashboard.widgets-modal')
        ->assertSet('expandedWidgetId', null)
        ->call('expandWidget', 'next-available')
        ->assertSet('expandedWidgetId', 'next-available')
        ->call('expandWidget', 'your-events')
        ->assertSet('expandedWidgetId', 'your-events')
        ->call('expandWidget', 'your-events')
        ->assertSet('expandedWidgetId', null);
});

test('dashboard modal column span controls respect minimum and maximum values', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('dashboard.widgets-modal');
    $widgets = $component->get('widgets');

    $nextAvailableIndex = collect($widgets)->search(fn (array $widget): bool => $widget['id'] === 'next-available');

    expect($nextAvailableIndex)->not->toBeFalse();

    $component
        ->set('widgets.'.$nextAvailableIndex.'.col_span', 1)
        ->call('decrementColSpan', $nextAvailableIndex)
        ->assertSet('widgets.'.$nextAvailableIndex.'.col_span', 1)
        ->call('incrementColSpan', $nextAvailableIndex)
        ->assertSet('widgets.'.$nextAvailableIndex.'.col_span', 2)
        ->set('widgets.'.$nextAvailableIndex.'.col_span', 4)
        ->call('incrementColSpan', $nextAvailableIndex)
        ->assertSet('widgets.'.$nextAvailableIndex.'.col_span', 4);
});

test('dashboard shows next available date for authenticated user', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['name' => 'Team Sync']);
    $user->events()->attach($event);

    $nextDateTime = Carbon::parse('tomorrow 14:00');

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => $nextDateTime,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Team Sync')
        ->assertSee($nextDateTime->format('D, M j Y'))
        ->assertSee($nextDateTime->format('g:i A'))
        ->assertSee('dashboard-next-availability-host-pin-off');
});

test('dashboard shows host pin for next availability at my place', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $user->events()->attach($event);

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => Carbon::parse('tomorrow 09:00'),
        'location' => 'my-place',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('dashboard-next-availability-host-pin');
});

test('dashboard shows next availability for an assigned event with multiple attendees', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create(['name' => 'Board Game Night']);

    $nextDateTime = Carbon::parse('tomorrow 11:00');

    $event->users()->attach([$user->id, $otherUser->id]);

    EventAvailability::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'available_at' => $nextDateTime,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Board Game Night')
        ->assertSee($nextDateTime->format('D, M j Y'))
        ->assertSee($nextDateTime->format('g:i A'));
});

test('dashboard shows next availability for every event in the carousel', function () {
    $user = User::factory()->create();
    $eventOne = Event::factory()->create(['name' => 'Morning Run']);
    $eventTwo = Event::factory()->create(['name' => 'Dinner Plan']);

    $user->events()->attach([$eventOne->id, $eventTwo->id]);

    EventAvailability::query()->create([
        'event_id' => $eventOne->id,
        'user_id' => $user->id,
        'available_at' => Carbon::parse('tomorrow 08:00'),
    ]);

    EventAvailability::query()->create([
        'event_id' => $eventTwo->id,
        'user_id' => $user->id,
        'available_at' => Carbon::parse('tomorrow 19:00'),
        'location' => 'my-place',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Morning Run')
        ->assertSee('Dinner Plan')
        ->assertSee(route('events.show', $eventOne))
        ->assertSee(route('events.show', $eventTwo))
        ->assertSee('dashboard-next-availability-host-pin')
        ->assertSee('dashboard-next-availability-host-pin-off');
});
