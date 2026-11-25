@extends('layouts.app')
@section('title', 'Payments')

@section('content')
<div class="page-head">
    <div><h1>Payments</h1><p class="muted">Total collected: <strong>@money($total)</strong></p></div>
    @can('payments.record')
    <a href="{{ route('payments.create') }}" class="btn">+ Record Payment</a>
    @endcan
</div>

<div class="card">
    <form method="GET" action="{{ route('payments.index') }}" class="filters" style="grid-template-columns:2fr 1fr auto">
        <div class="form-group">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Receipt no., reference no. or member">
        </div>
        <div class="form-group">
            <label for="method">Method</label>
            <select id="method" name="method">
                <option value="">All</option>
                @foreach (\App\Models\Payment::METHODS as $key => $label)
                    <option value="{{ $key }}" @selected(request('method') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Filter</button>
            <a href="{{ route('payments.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Receipt No.</th><th>Date</th><th>Member</th><th>Transaction</th><th>Method</th><th class="right">Amount</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $p)
                <tr>
                    <td class="mono nowrap">{{ $p->receipt_number }}</td>
                    <td class="nowrap">{{ $p->payment_date->format('M d, Y') }}</td>
                    <td>{{ $p->transaction->member->full_name }}</td>
                    <td><a href="{{ route('transactions.show', $p->transaction) }}" class="mono">{{ $p->transaction->reference_number }}</a></td>
                    <td>{{ $p->method_label }}</td>
                    <td class="right nowrap">@money($p->amount)</td>
                    <td class="right"><a href="{{ route('payments.show', $p) }}" class="btn btn-light btn-sm">Receipt</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No payments found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
