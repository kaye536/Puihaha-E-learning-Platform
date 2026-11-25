@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
<div class="page-head">
    <div><h1>Transactions</h1><p class="muted">Membership applications and renewals.</p></div>
    @can('transactions.create')
    <a href="{{ route('transactions.create') }}" class="btn">+ New Transaction</a>
    @endcan
</div>

<div class="card">
    <form method="GET" action="{{ route('transactions.index') }}" class="filters" style="grid-template-columns:2fr 1fr 1fr auto">
        <div class="form-group">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Reference no. or member">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (\App\Models\MembershipTransaction::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                @foreach (\App\Models\MembershipTransaction::TYPES as $ty)
                    <option value="{{ $ty }}" @selected(request('type') === $ty)>{{ ucfirst($ty) }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Filter</button>
            <a href="{{ route('transactions.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Reference</th><th>Date</th><th>Member</th><th>Type</th><th>Level</th><th>Amount</th><th>Balance</th><th>Status</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td><a href="{{ route('transactions.show', $t) }}" class="mono nowrap">{{ $t->reference_number }}</a></td>
                    <td class="nowrap">{{ $t->transaction_date->format('M d, Y') }}</td>
                    <td><a href="{{ route('members.show', $t->member) }}">{{ $t->member->full_name }}</a></td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->level->name }}</td>
                    <td class="nowrap">@money($t->amount)</td>
                    <td class="nowrap">@money($t->balance)</td>
                    <td><x-status-badge :status="$t->status" /></td>
                    <td class="right nowrap">
                        <a href="{{ route('transactions.show', $t) }}" class="btn btn-light btn-sm">View</a>
                        @if ($t->canReceivePayment() && auth()->user()->can('payments.record'))
                            <a href="{{ route('payments.create', ['transaction' => $t->id]) }}" class="btn btn-sm">Pay</a>
                        @endif
                        @if ($t->canBeApproved() && auth()->user()->can('transactions.approve'))
                            <form method="POST" action="{{ route('transactions.approve', $t) }}" class="inline" data-confirm="Approve this transaction and activate the membership?">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links() }}
</div>
@endsection
