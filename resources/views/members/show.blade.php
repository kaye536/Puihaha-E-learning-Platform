@extends('layouts.app')
@section('title', $member->full_name)

@section('content')
<div class="page-head">
    <div>
        <h1>Member profile</h1>
        <p class="muted">Review details, transactions and membership validity.</p>
    </div>
    <div class="actions">
        @can('members.manage')
        <a href="{{ route('members.edit', $member) }}" class="btn btn-light">Edit</a>
        @endcan
        <a href="{{ route('members.history', $member) }}" class="btn btn-light">Transaction History</a>
        <a href="{{ route('members.record', $member) }}" class="btn btn-light">Printable Record</a>
        @can('transactions.create')
            @unless ($member->isInactive())
                <a href="{{ route('transactions.create', ['member' => $member->id]) }}" class="btn">New Transaction</a>
            @endunless
        @endcan
        @can('members.manage')
        <form method="POST" action="{{ route('members.toggle-status', $member) }}" class="inline"
              data-confirm="{{ $member->isInactive() ? 'Re-activate this member?' : 'Deactivate this member?' }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-warning">{{ $member->isInactive() ? 'Activate' : 'Deactivate' }}</button>
        </form>
        @endcan
    </div>
</div>

<section class="pass" aria-label="Membership pass">
    <div class="pass-top">
        <x-logo :size="34" word />
        <x-status-badge :status="$member->status" />
    </div>
    <div>
        <div class="pass-name">{{ $member->full_name }}</div>
        <div class="pass-no">{{ $member->membership_number }}</div>
    </div>
    <x-logo class="pass-seal" :size="96" />
    <div class="pass-meta">
        <div><span>Level</span><strong>{{ $member->level->name }}</strong></div>
        <div><span>Category</span><strong>{{ $member->category->name }}</strong></div>
        <div><span>Member since</span><strong>{{ $member->membership_start?->format('M d, Y') ?? '—' }}</strong></div>
        <div><span>Valid until</span><strong>{{ $member->membership_expiry?->format('M d, Y') ?? '—' }}</strong></div>
    </div>
</section>

<div class="grid grid-2">
    <div class="card">
        <h2>Personal Information</h2>
        <dl class="details">
            <dt>Email</dt><dd>{{ $member->email }}</dd>
            <dt>Phone</dt><dd>{{ $member->phone ?: '—' }}</dd>
            <dt>Birthdate</dt><dd>{{ $member->birthdate?->format('F d, Y') ?? '—' }}</dd>
            <dt>Address</dt><dd>{{ $member->address ?: '—' }}</dd>
            <dt>Registered</dt><dd>{{ $member->created_at->format('F d, Y') }}</dd>
        </dl>
    </div>
    <div class="card">
        <h2>Membership</h2>
        <dl class="details">
            <dt>Category</dt><dd>{{ $member->category->name }}</dd>
            <dt>Level</dt><dd>{{ $member->level->name }} — @money($member->level->fee) / {{ $member->level->validity_months }} months</dd>
            <dt>Benefits</dt><dd>{{ $member->level->benefits ?: '—' }}</dd>
            <dt>Member since</dt><dd>{{ $member->membership_start?->format('F d, Y') ?? '—' }}</dd>
            <dt>Valid until</dt><dd>{{ $member->membership_expiry?->format('F d, Y') ?? '—' }}</dd>
            <dt>Portal access</dt>
            <dd>
                @php($portalUser = $member->user)
                @if (! $portalUser)
                    Not created
                @elseif ($portalUser->portal_activated_at)
                    <span class="badge badge-active">Active</span> since {{ $portalUser->portal_activated_at->format('M d, Y') }}
                @else
                    <span class="badge badge-pending">Invited</span> — waiting for the member to set a password
                @endif
                @can('members.manage')
                    @if ($member->membership_expiry && ! $portalUser?->portal_activated_at)
                        <form method="POST" action="{{ route('members.portal-invite', $member) }}" class="inline" style="margin-left:.5rem">
                            @csrf
                            <button type="submit" class="btn btn-light btn-sm">{{ $portalUser ? 'Resend invitation' : 'Send invitation' }}</button>
                        </form>
                    @endif
                @endcan
            </dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:.5rem">
        <h2 style="margin:0">Latest Transactions</h2>
        <a href="{{ route('members.history', $member) }}" class="small">View full history →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reference</th><th>Type</th><th>Level</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td><a href="{{ route('transactions.show', $t) }}" class="mono">{{ $t->reference_number }}</a></td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->level->name }}</td>
                    <td>@money($t->amount)</td>
                    <td>@money($t->amount_paid)</td>
                    <td><x-status-badge :status="$t->status" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
