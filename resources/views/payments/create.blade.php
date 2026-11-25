@extends('layouts.app')
@section('title', 'Record Payment')

@section('content')
<div class="page-head">
    <div><h1>Record Payment</h1><p class="muted">Payments must be linked to a pending transaction and cannot exceed its balance.</p></div>
    <a href="{{ route('payments.index') }}" class="btn btn-light">Cancel</a>
</div>

@if ($transactions->isEmpty())
    <div class="card"><p class="muted" style="margin:0">There are no pending transactions with an unpaid balance.
        <a href="{{ route('transactions.create') }}">Create a transaction</a> first.</p></div>
@else
<div class="card" style="max-width:720px">
    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:1rem">
            <div class="form-group full @error('membership_transaction_id') has-error @enderror">
                <label for="membership_transaction_id">Transaction <span class="req">*</span></label>
                <select id="membership_transaction_id" name="membership_transaction_id" required>
                    <option value="">— Select transaction —</option>
                    @foreach ($transactions as $t)
                        <option value="{{ $t->id }}" data-balance="{{ $t->balance }}"
                                @selected(old('membership_transaction_id', $selected) == $t->id)>
                            {{ $t->reference_number }} — {{ $t->member->full_name }} — balance @money($t->balance)
                        </option>
                    @endforeach
                </select>
                @error('membership_transaction_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('amount') has-error @enderror">
                <label for="amount">Amount <span class="req">*</span></label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                <span class="hint" id="balance-hint">Select a transaction to see its balance.</span>
                @error('amount')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('payment_date') has-error @enderror">
                <label for="payment_date">Payment Date <span class="req">*</span></label>
                <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                @error('payment_date')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('payment_method') has-error @enderror">
                <label for="payment_method">Payment Method <span class="req">*</span></label>
                <select id="payment_method" name="payment_method" required>
                    @foreach (\App\Models\Payment::METHODS as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_method', 'cash') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('payment_method')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('reference_note') has-error @enderror">
                <label for="reference_note">Reference / Note</label>
                <input type="text" id="reference_note" name="reference_note" value="{{ old('reference_note') }}" maxlength="255" placeholder="e.g. check no., bank ref.">
                @error('reference_note')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        <button type="submit" class="btn">Record Payment</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('membership_transaction_id');
    var amount = document.getElementById('amount');
    var hint = document.getElementById('balance-hint');
    var currency = @json(config('membership.currency'));

    function update(fill) {
        var opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.balance) {
            var balance = parseFloat(opt.dataset.balance);
            amount.max = balance.toFixed(2);
            hint.textContent = 'Remaining balance: ' + currency + balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (fill || !amount.value) { amount.value = balance.toFixed(2); }
        } else {
            amount.removeAttribute('max');
            hint.textContent = 'Select a transaction to see its balance.';
        }
    }
    select.addEventListener('change', function () { update(true); });
    update(false);
});
</script>
@endif
@endsection
