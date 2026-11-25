@extends('layouts.app')
@section('title', $transaction->reference_number)

@section('content')
<div class="page-head">
    <div>
        <h1><span class="mono">{{ $transaction->reference_number }}</span> <x-status-badge :status="$transaction->status" /></h1>
        <p class="muted">{{ ucfirst($transaction->type) }} · {{ $transaction->transaction_date->format('F d, Y') }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('transactions.index') }}" class="btn btn-light">Back</a>
        @if ($transaction->canReceivePayment() && auth()->user()->can('payments.record'))
            <a href="{{ route('payments.create', ['transaction' => $transaction->id]) }}" class="btn">Record Payment</a>
        @endif
        @if ($transaction->canBeApproved() && auth()->user()->can('transactions.approve'))
            <form method="POST" action="{{ route('transactions.approve', $transaction) }}" class="inline" data-confirm="Approve this transaction and activate the membership?">
                @csrf
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
        @endif
        @if ($transaction->canBeCancelled() && auth()->user()->can('transactions.approve'))
            <form method="POST" action="{{ route('transactions.cancel', $transaction) }}" class="inline" data-confirm="Cancel this transaction?">
                @csrf
                <button type="submit" class="btn btn-danger">Cancel Transaction</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>Transaction Details</h2>
        <dl class="details">
            <dt>Reference No.</dt><dd class="mono">{{ $transaction->reference_number }}</dd>
            <dt>Type</dt><dd>{{ ucfirst($transaction->type) }}</dd>
            <dt>Level</dt><dd>{{ $transaction->level->name }} ({{ $transaction->level->validity_months }} months)</dd>
            <dt>Amount Due</dt><dd><strong>@money($transaction->amount)</strong></dd>
            <dt>Amount Paid</dt><dd>@money($transaction->amount_paid)</dd>
            <dt>Balance</dt><dd>@money($transaction->balance)</dd>
            <dt>Coverage</dt>
            <dd>
                @if ($transaction->period_start)
                    {{ $transaction->period_start->format('M d, Y') }} – {{ $transaction->period_end->format('M d, Y') }}
                @else
                    Set upon approval
                @endif
            </dd>
            <dt>Approved at</dt><dd>{{ $transaction->approved_at?->format('M d, Y h:i A') ?? '—' }}</dd>
            <dt>Processed by</dt><dd>{{ $transaction->processor?->name ?? '—' }}</dd>
            <dt>Verification email</dt><dd>{{ $transaction->verification_sent_at ? 'Queued '.$transaction->verification_sent_at->format('M d, Y h:i A') : '—' }}</dd>
            <dt>Remarks</dt><dd>{{ $transaction->remarks ?: '—' }}</dd>
        </dl>
    </div>
    <div class="card">
        <h2>Member</h2>
        <dl class="details">
            <dt>Name</dt><dd><a href="{{ route('members.show', $transaction->member) }}">{{ $transaction->member->full_name }}</a></dd>
            <dt>Membership No.</dt><dd class="mono">{{ $transaction->member->membership_number }}</dd>
            <dt>Category</dt><dd>{{ $transaction->member->category->name }}</dd>
            <dt>Member status</dt><dd><x-status-badge :status="$transaction->member->status" /></dd>
            <dt>Valid until</dt><dd>{{ $transaction->member->membership_expiry?->format('M d, Y') ?? '—' }}</dd>
        </dl>
        <p style="margin-top:1rem"><a href="{{ route('members.history', $transaction->member) }}" class="btn btn-light btn-sm">View member history</a></p>
    </div>
</div>

@if ($transaction->hasLocation())
    <div class="card">
        <h2>Applicant Location &amp; Weather</h2>
        @include('partials.location-map', ['editable' => false, 'lat' => $transaction->latitude, 'lon' => $transaction->longitude])
    </div>
@endif

<div class="card">
    <h2>Payment Details</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Receipt No.</th><th>Date</th><th>Method</th><th>Note</th><th>Received by</th><th class="right">Amount</th><th></th></tr></thead>
            <tbody>
            @forelse ($transaction->payments as $p)
                <tr>
                    <td class="mono">{{ $p->receipt_number }}</td>
                    <td>{{ $p->payment_date->format('M d, Y') }}</td>
                    <td>{{ $p->method_label }}</td>
                    <td>{{ $p->reference_note ?: '—' }}</td>
                    <td>{{ $p->receiver?->name ?? '—' }}</td>
                    <td class="right">@money($p->amount)</td>
                    <td class="right"><a href="{{ route('payments.show', $p) }}" class="btn btn-light btn-sm">Receipt</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No payments recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
