<?php

use App\Models\Event;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public function store(): void
    {
        $currentUser = request()->user();

        abort_if($currentUser?->cannot('create', Event::class), 403);

        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'max:255', 'unique:events,name'],
            ],
            [
                'name.required' => 'Please enter an event title.',
                'name.unique' => 'An event with this name already exists.',
            ],
        );

        $event = Event::query()->create([
            'name' => $validated['name'],
            'color' => '#2563EB',
            'created_by' => $currentUser?->id,
        ]);

        if ($currentUser !== null) {
            $event->users()->sync([$currentUser->id]);
        }

        $this->reset('name');
        $this->dispatch('event-created');
        $this->dispatch('close-create-event-modal');

        Flux::toast(text: __('Event created successfully.'), variant: 'success');
    }
};
?>

<div x-data x-on:close-create-event-modal.window="$flux.modal('create-event-modal').close()">
    <flux:modal name="create-event-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Event') }}</flux:heading>
                <flux:text>{{ __('Set a name for your event.') }}</flux:text>
            </div>

            <flux:field>
                <flux:label>{{ __('Event title') }}</flux:label>
                <flux:input wire:model="name" :placeholder="__('Event name')" />
                <flux:error name="name" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="button" variant="primary" wire:click="store">{{ __('Create Event') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
