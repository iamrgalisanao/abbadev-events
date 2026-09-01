<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize a Philippine mobile number to E.164 (+639XXXXXXXXX) before
     * validation, accepting the common forms people type: 09XX XXX XXXX,
     * +639XXXXXXXXX, 639XXXXXXXXX, or 9XXXXXXXXX.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => static::normalizePhone((string) $this->input('phone'))]);
        }
    }

    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '63' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '63' . $digits;
        }

        return $digits === '' ? '' : '+' . $digits;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'regex:/^\+639\d{9}$/'],
            'organization' => ['nullable', 'string', 'max:160'],
            'event' => ['required', 'string', 'exists:events,slug'],
            'audience' => ['nullable', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60'],
            'lead_source' => ['nullable', 'string', 'max:60'],
            'utm' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Philippine mobile number, e.g. 0917 123 4567.',
        ];
    }
}
