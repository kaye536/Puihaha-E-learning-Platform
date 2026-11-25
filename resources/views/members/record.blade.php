@extends('layouts.app')
@section('title', 'Membership Record')

@section('content')
<div class="page-head no-print">
    <h1>Membership Record</h1>
    <div class="actions">
        <a href="{{ route('members.show', $member) }}" class="btn btn-light">Back</a>
        <button type="button" class="btn" onclick="window.print()">Print</button>
    </div>
</div>

<div class="document">
    <div class="doc-head">
        <x-logo :size="52" word tone="maroon" />
        <h1>{{ config('membership.organization') }}</h1>
        <div class="muted">Official Membership Record</div>
    </div>

    <dl class="details" style="margin-bottom:1.25rem">
        <dt>Membership No.</dt><dd class="mono"><strong>{{ $member->membership_number }}</strong></dd>
        <dt>Name</dt><dd>{{ $member->full_name }}</dd>
        <dt>Email</dt><dd>{{ $member->email }}</dd>
        <dt>Phone</dt><dd>{{ $member->phone ?: '—' }}</dd>
        <dt>Address</dt><dd>{{ $member->address ?: '—' }}</dd>
        <dt>Category</dt><dd>{{ $member->category->name }}</dd>
        <dt>Level</dt><dd>{{ $member->level->name }}</dd>
        <dt>Benefits</dt><dd>{{ $member->level->benefits ?: '—' }}</dd>
        <dt>Status</dt><dd><x-status-badge :status="$member->status" /></dd>
        <dt>Member since</dt><dd>{{ $member->membership_start?->format('F d, Y') ?? '—' }}</dd>
        <dt>Valid until</dt><dd>{{ $member->membership_expiry?->format('F d, Y') ?? '—' }}</dd>
    </dl>

    <h3>Membership Periods</h3>
    <table>
        <thead><tr><th>Reference</th><th>Type</th><th>Level</th><th>From</th><th>To</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($approvedTransactions as $t)
            <tr>
                <td class="mono">{{ $t->reference_number }}</td>
                <td>{{ ucfirst($t->type) }}</td>
                <td>{{ $t->level->name }}</td>
                <td>{{ $t->period_start?->format('M d, Y') }}</td>
                <td>{{ $t->period_end?->format('M d, Y') }}</td>
                <td><x-status-badge :status="$t->status" /></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No approved membership periods yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="small muted" style="margin-top:1.5rem">Printed on {{ now()->format('F d, Y h:i A') }}</p>
    <div class="signature"><div>Member signature</div><div>Authorized signature</div></div>
</div>
@endsection
