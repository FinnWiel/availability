<x-layouts::app :title="$event->name">
    <livewire:events.show-calendar :event="$event" :next-common-date-time="$nextCommonDateTime"
        :user-availability-slots="$userAvailabilitySlots" />
</x-layouts::app>
