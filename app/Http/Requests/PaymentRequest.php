<?php

namespace App\Http\Requests;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Support\Format;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'membership_transaction_id' => ['required', 'integer', 'exists:membership_transactions,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'membership_transaction_id.required' => 'Please select the transaction being paid.',
            'membership_transaction_id.exists' => 'The selected transaction does not exist.',
            'amount.gt' => 'The payment amount must be greater than zero.',
        ];
    }

    /** Payments must be connected to a valid, payable transaction and never exceed its balance. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $transaction = MembershipTransaction::find($this->input('membership_transaction_id'));

                if (! $transaction->canReceivePayment()) {
                    $validator->errors()->add(
                        'membership_transaction_id',
                        'This transaction cannot receive payments (it must be pending with an unpaid balance).'
                    );

                    return;
                }

                if (round((float) $this->input('amount'), 2) > $transaction->balance) {
                    $validator->errors()->add(
                        'amount',
                        'The amount exceeds the remaining balance of '.Format::money($transaction->balance).'.'
                    );
                }
            },
        ];
    }
}
