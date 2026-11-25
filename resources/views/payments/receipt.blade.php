@extends('layouts.app')
@section('title', 'Official Receipt '.$payment->receipt_number)

@section('content')
@php($transaction = $payment->transaction)
<div class="page-head no-print">
    <h1>Official Receipt</h1>
    <div class="actions">
        <a href="{{ $backUrl ?? route('transactions.show', $transaction) }}" class="btn btn-light">{{ $backLabel ?? 'Back to Transaction' }}</a>
        <button type="button" class="btn" onclick="window.print()">Print Receipt</button>
    </div>
</div>

<div class="document">
    <div class="doc-head">
        <x-logo :size="52" word tone="maroon" />
        <h1>{{ config('membership.organization') }}</h1>
        <div class="muted">Official Receipt</div>
    </div>

    <dl class="details" style="margin-bottom:1.25rem">
        <dt>Receipt No.</dt><dd class="mono"><strong>{{ $payment->receipt_number }}</strong></dd>
        <dt>Payment date</dt><dd>{{ $payment->payment_date->format('F d, Y') }}</dd>
        <dt>Received from</dt><dd>{{ $transaction->member->full_name }}</dd>
        <dt>Membership No.</dt><dd class="mono">{{ $transaction->member->membership_number }}</dd>
        <dt>Transaction Ref.</dt><dd class="mono">{{ $transaction->reference_number }}</dd>
        <dt>Particulars</dt><dd>{{ ucfirst($transaction->type) }} — {{ $transaction->level->name }} membership ({{ $transaction->level->validity_months }} months)</dd>
        <dt>Payment method</dt><dd>{{ $payment->method_label }}</dd>
        <dt>Reference / note</dt><dd>{{ $payment->reference_note ?: '—' }}</dd>
    </dl>

    <table>
        <tbody>
            <tr><td>Transaction amount</td><td class="right">@money($transaction->amount)</td></tr>
            <tr><td>Total paid to date</td><td class="right">@money($transaction->amount_paid)</td></tr>
            <tr><td>Remaining balance</td><td class="right">@money($transaction->balance)</td></tr>
            <tr><td><strong>Amount received</strong></td><td class="right total">@money($payment->amount)</td></tr>
        </tbody>
    </table>

    <p class="small muted" style="margin-top:1.25rem">Transaction status: {{ ucfirst($transaction->status) }} · Received by {{ $payment->receiver?->name ?? '—' }} · Printed {{ now()->format('M d, Y h:i A') }}</p>
    <div class="signature"><div>Received by</div><div>Member signature</div></div>
</div>
@endsection
