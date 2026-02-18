<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        if (! $this->user()) {
            return false;
        }

        if ($this->user()->hasRole('admin')) {
            return true;
        }

        return $event->users()->whereKey($this->user()->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'regex:/^(all-day|([01]\\d|2[0-3]):(00|30))$/'],
            'location' => ['nullable', 'string', 'in:my-place'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Please choose a date from the calendar.',
            'time.required' => 'Please choose a time for your availability.',
            'time.regex' => 'Please choose a valid time or all day.',
            'location.in' => 'Please choose a valid location option.',
        ];
    }
}
