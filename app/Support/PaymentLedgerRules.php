<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class PaymentLedgerRules
{
    /** @return array<string, mixed> */
    public static function rules(string $prefix = ''): array
    {
        return [
            $prefix.'amount_agreed' => ['required', 'numeric', 'min:0'],
            $prefix.'amount_paid' => ['required', 'numeric', 'min:0', 'lte:'.$prefix.'amount_agreed'],
            $prefix.'payment_status' => ['required', 'in:'.implode(',', array_keys(config('payment_status.options', [])))],
            $prefix.'receiving_account_id' => [
                'required',
                Rule::exists('receiving_accounts', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function messages(string $prefix = ''): array
    {
        return [
            $prefix.'amount_agreed.required' => 'Please enter the amount agreed.',
            $prefix.'amount_agreed.min' => 'Amount agreed cannot be negative.',
            $prefix.'amount_paid.required' => 'Please enter the amount paid.',
            $prefix.'amount_paid.min' => 'Amount paid cannot be negative.',
            $prefix.'amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
            $prefix.'receiving_account_id.required' => 'Please select a payment account.',
            $prefix.'receiving_account_id.exists' => 'The selected payment account is not valid.',
        ];
    }
}
