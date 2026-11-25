@extends('layouts.app')
@section('title', 'History & Receipts')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Member portal</span>
        <h1>History &amp; Receipts</h1>
        <p class="muted"><span class="mono">{{ $member->membership_number }}</span> · {{ $member->category->name }} / {{ $member->level->name }}</p>
    </div>
    <a href="{{ route('portal.index') }}" class="btn btn-light">Back to my membership</a>
</div>

<div class="card">
    <h2>Applications &amp; renewals</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Reference</th><th>Type</th><th>Level</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Period</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td class="nowrap">{{ $t->transaction_date->format('M d, Y') }}</td>
                    <td class="mono">{{ $t->reference_number }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->level->name }}</td>
                    <td class="nowrap">@money($t->amount)</td>
                    <td class="nowrap">@money($t->amount_paid)</td>
                    <td class="nowrap">@money($t->balance)</td>
                    <td class="nowrap small">{{ $t->period_start ? $t->period_start->format('M d, Y').' – '.$t->period_end->format('M d, Y') : '—' }}</td>
                    <td><x-status-badge :status="$t->status" /></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Payments &amp; official receipts</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Receipt no.</th><th>For</th><th>Method</th><th>Amount</th><th class="right">Receipt</th></tr></thead>
            <tbody>
            @forelse ($payments as $p)
                <tr>
                    <td class="nowrap">{{ $p->payment_date->format('M d, Y') }}</td>
                    <td class="mono">{{ $p->receipt_number }}</td>
                    <td class="mono">{{ $p->transaction->reference_number }}</td>
                    <td>{{ $p->method_label }}</td>
                    <td class="nowrap">@money($p->amount)</td>
                    <td class="right"><a href="{{ route('portal.receipt', $p) }}" class="btn btn-light btn-sm">View / print</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No payments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
