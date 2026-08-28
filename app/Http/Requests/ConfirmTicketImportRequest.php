<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTicketImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (config('ticket_import.form_fields', []) as $key => $field) {
            $rule = ['nullable', 'string', 'max:255'];

            if (! empty($field['required'])) {
                $rule[0] = 'required';
            }

            $rules[$key] = $rule;
        }

        $rules['flight_segments'] = ['nullable', 'array'];
        $rules['flight_segments.*'] = ['array'];

        foreach (array_keys(config('ticket_import.flight_segment_fields', [])) as $key) {
            $rules["flight_segments.*.{$key}"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
