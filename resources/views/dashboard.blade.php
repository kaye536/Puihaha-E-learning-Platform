@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<section class="hero">
    <div>
        <h1>Hello, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
        <p>Here is where memberships, transactions and collections stand today.</p>
        <div class="actions">
            @can('members.manage')<a href="{{ route('members.create') }}" class="btn">+ Register member</a>@endcan
            @can('transactions.create')<a href="{{ route('transactions.create') }}" class="btn btn-light">New transaction</a>@endcan
            @can('payments.record')<a href="{{ route('payments.create') }}" class="btn btn-light">Record payment</a>@endcan
        </div>
    </div>
    <div class="hero-figure">
        <div class="label">Collected payments</div>
        <div class="value">@money($collectedPayments)</div>
        <div class="sub">@money($collectedThisMonth) this month</div>
    </div>
</section>

<div class="grid grid-3" style="margin-bottom:1.25rem">
    <div class="stat"><div class="label">Total members</div><div class="value">{{ $totalMembers }}</div></div>
    <div class="stat green"><div class="label">Active members</div><div class="value">{{ $activeMembers }}</div></div>
    <div class="stat amber">
        <div class="label">Pending transactions</div>
        <div class="value">{{ $pendingTransactions }}</div>
        <div class="small muted">{{ $awaitingApproval }} paid, awaiting approval</div>
    </div>
</div>

@can('courses.view')
<div class="grid grid-3" style="margin-bottom:1.25rem">
    <div class="stat"><div class="label">Published courses</div><div class="value">{{ $publishedCourses }}</div><div class="small muted"><a href="{{ route('courses.index') }}">Manage e-learning</a></div></div>
    <div class="stat green"><div class="label">Members learning</div><div class="value">{{ $learners }}</div></div>
    <div class="stat amber"><div class="label">Certificates issued</div><div class="value">{{ $certificates }}</div></div>
</div>
@endcan

<div class="grid grid-2">
    <div class="card">
        <h2>Recent transactions</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Reference</th><th>Member</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($recentTransactions as $t)
                    <tr>
                        <td><a href="{{ route('transactions.show', $t) }}" class="mono">{{ $t->reference_number }}</a></td>
                        <td>{{ $t->member->full_name }}</td>
                        <td>@money($t->amount)</td>
                        <td><x-status-badge :status="$t->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No transactions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Recent payments</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Receipt</th><th>Member</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody>
                @forelse ($recentPayments as $p)
                    <tr>
                        <td><a href="{{ route('payments.show', $p) }}" class="mono">{{ $p->receipt_number }}</a></td>
                        <td>{{ $p->transaction->member->full_name }}</td>
                        <td>@money($p->amount)</td>
                        <td class="nowrap">{{ $p->payment_date->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No payments recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-3">
    <div class="card">
        <h2>Members by category</h2>
        <table>
            <tbody>
            @forelse ($categories as $c)
                <tr><td>{{ $c->name }}</td><td class="right"><strong>{{ $c->members_count }}</strong></td></tr>
            @empty
                <tr><td class="empty">No categories.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Members by level</h2>
        <table>
            <tbody>
            @forelse ($levels as $l)
                <tr><td>{{ $l->name }}</td><td class="right"><strong>{{ $l->members_count }}</strong></td></tr>
            @empty
                <tr><td class="empty">No levels.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Expiring in 30 days</h2>
        <table>
            <tbody>
            @forelse ($expiringSoon as $m)
                <tr>
                    <td><a href="{{ route('members.show', $m) }}">{{ $m->full_name }}</a><div class="small muted">{{ $m->level->name }}</div></td>
                    <td class="right nowrap small">{{ $m->membership_expiry->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr><td class="empty">No memberships expiring soon.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
