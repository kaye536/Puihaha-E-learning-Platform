@extends('layouts.app')
@section('title', 'Transaction History')

@section('content')
<div class="page-head">
    <div>
        <h1>Transaction History</h1>
        <p class="muted"><a href="{{ route('members.show', $member) }}">{{ $member->full_name }}</a>
            · <span class="mono">{{ $member->membership_number }}</span>
            · {{ $member->category->name }} / {{ $member->level->name }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('members.show', $member) }}" class="btn btn-light">Back to Profile</a>
        @can('transactions.create')
            @unless ($member->isInactive())
                <a href="{{ route('transactions.create', ['member' => $member->id]) }}" class="btn">New Transaction</a>
            @endunless
        @endcan
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:1.25rem">
    <div class="stat"><div class="label">Transactions</div><div class="value">{{ $transactions->total() }}</div></div>
    <div class="stat green"><div class="label">Total Paid</div><div class="value">@money($totalPaid)</div></div>
    <div class="stat blue"><div class="label">Valid Until</div><div class="value" style="font-size:1.3rem">{{ $member->membership_expiry?->format('M d, Y') ?? '—' }}</div></div>
</div>

@forelse ($transactions as $t)
    <div class="card">
        <div class="page-head" style="margin-bottom:.75rem">
            <div>
                <h2 style="margin:0"><a href="{{ route('transactions.show', $t) }}" class="mono">{{ $t->reference_number }}</a>
                    <x-status-badge :status="$t->status" /></h2>
                <span class="muted small">{{ ucfirst($t->type) }} · {{ $t->level->name }} · {{ $t->transaction_date->format('M d, Y') }}
                    @if ($t->period_start) · Coverage {{ $t->period_start->format('M d, Y') }} – {{ $t->period_end->format('M d, Y') }} @endif</span>
            </div>
            <div class="right">
                <div>Amount: <strong>@money($t->amount)</strong></div>
                <div class="small muted">Paid @money($t->amount_paid) · Balance @money($t->balance)</div>
            </div>
        </div>
        @if ($t->payments->isNotEmpty())
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Note</th><th class="right">Amount</th></tr></thead>
                    <tbody>
                    @foreach ($t->payments as $p)
                        <tr>
                            <td><a href="{{ route('payments.show', $p) }}" class="mono">{{ $p->receipt_number }}</a></td>
                            <td>{{ $p->payment_date->format('M d, Y') }}</td>
                            <td>{{ $p->method_label }}</td>
                            <td>{{ $p->reference_note ?: '—' }}</td>
                            <td class="right">@money($p->amount)</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted small" style="margin:0">No payments recorded for this transaction.</p>
        @endif
    </div>
@empty
    <div class="card"><p class="muted" style="margin:0">This member has no transactions yet.</p></div>
@endforelse

{{ $transactions->links() }}
@endsection
