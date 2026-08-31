<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:160'],
            'event' => ['required', 'string', 'exists:events,slug'],
            'audience' => ['nullable', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60'],
            'lead_source' => ['nullable', 'string', 'max:60'],
            'utm' => ['nullable', 'array'],
        ];
    }
}
