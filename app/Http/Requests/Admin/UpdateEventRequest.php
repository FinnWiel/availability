<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($event)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $users = collect($this->input('users', []))
            ->flatMap(function (mixed $value): array {
                if (is_array($value)) {
                    if (array_key_exists('value', $value)) {
                        return [$value['value']];
                    }

                    if (array_key_exists('id', $value)) {
                        return [$value['id']];
                    }

                    return $value;
                }

                if (is_string($value) && str_contains($value, ',')) {
                    return array_map('trim', explode(',', $value));
                }

                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    if (is_array($decoded)) {
                        if (array_key_exists('value', $decoded)) {
                            return [$decoded['value']];
                        }

                        if (array_key_exists('id', $decoded)) {
                            return [$decoded['id']];
                        }

                        return $decoded;
                    }
                }

                return [$value];
            })
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'users' => $users,
        ]);
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An event with this name already exists.',
            'color.regex' => 'Please provide a valid 6-character hex color (for example #2563EB).',
            'users.*.exists' => 'One of the selected users no longer exists.',
        ];
    }
}
