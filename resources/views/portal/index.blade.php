@extends('layouts.app')
@section('title', 'My Membership')

@section('content')
@php
    $expiry = $member->membership_expiry;
    $daysLeft = $expiry ? (int) today()->diffInDays($expiry, false) : null;
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Member portal</span>
        <h1>Hello, {{ \Illuminate\Support\Str::before($member->full_name, ' ') }}</h1>
        <p class="muted">Your membership at a glance.</p>
    </div>
    <div class="actions">
        @if ($renewal['allowed'])
            <a href="{{ route('portal.renew') }}" class="btn">Renew membership</a>
        @endif
        <a href="{{ route('portal.history') }}" class="btn btn-light">History &amp; receipts</a>
        @can('learning.access')
            <a href="{{ route('learn.index') }}" class="btn btn-light">My learning</a>
        @endcan
    </div>
</div>

@if ($member->isInactive())
    <div class="callout callout-warn"><strong>Your membership is deactivated.</strong> Please contact the membership office.</div>
@elseif ($expiry === null)
    <div class="callout callout-info"><strong>Your application is being processed.</strong> We’ll email you once it’s approved. Please pay the fee at the membership office if you haven’t yet.</div>
@elseif ($daysLeft < 0)
    <div class="callout callout-warn"><strong>Your membership expired on {{ $expiry->format('F d, Y') }}.</strong> @if ($renewal['allowed']) You can renew it now. @endif</div>
@elseif ($daysLeft <= config('membership.renewal_window_days'))
    <div class="callout callout-info"><strong>Your membership expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}</strong> ({{ $expiry->format('F d, Y') }}). @if ($renewal['allowed']) You can renew it now. @endif</div>
@endif

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
        <div><span>Valid until</span><strong>{{ $expiry?->format('M d, Y') ?? '—' }}</strong></div>
    </div>
</section>

<div class="grid grid-2">
    <div class="card">
        <h2>{{ $open ? 'Request in progress' : 'Requests' }}</h2>
        @if ($open)
            <dl class="details">
                <dt>Reference</dt><dd class="mono">{{ $open->reference_number }}</dd>
                <dt>Type</dt><dd>{{ ucfirst($open->type) }} — {{ $open->level->name }}</dd>
                <dt>Amount</dt><dd>@money($open->amount)</dd>
                <dt>Paid so far</dt><dd>@money($open->amount_paid)</dd>
                <dt>Balance</dt><dd><strong>@money($open->balance)</strong></dd>
                <dt>Status</dt><dd><x-status-badge :status="$open->status" /></dd>
            </dl>
            <p class="small muted" style="margin-bottom:0">
                @if ($open->status === 'pending' && $open->balance > 0)
                    Pay at the membership office and quote <span class="mono">{{ $open->reference_number }}</span>.
                @else
                    Paid in full. Staff will approve it shortly.
                @endif
            </p>
        @else
            <p class="muted" style="margin-top:0">You have no requests in progress.</p>
            @unless ($renewal['allowed'])<p class="small muted" style="margin-bottom:0">{{ $renewal['reason'] }}</p>@endunless
        @endif
    </div>

    <div class="card">
        <h2>My contact details</h2>
        <form method="POST" action="{{ route('portal.profile.update') }}">
            @csrf @method('PATCH')
            <div class="form-group @error('phone') has-error @enderror" style="margin-bottom:.9rem">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="30" autocomplete="tel">
                @error('phone')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('address') has-error @enderror" style="margin-bottom:1rem">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="{{ old('address', $member->address) }}" maxlength="500" autocomplete="street-address">
                @error('address')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <p class="small muted">To change your name or email, please contact the membership office.</p>
            <button type="submit" class="btn btn-light">Save details</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:.5rem">
        <h2 style="margin:0">Latest activity</h2>
        <a href="{{ route('portal.history') }}" class="small">View all →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reference</th><th>Type</th><th>Level</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td class="mono">{{ $t->reference_number }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->level->name }}</td>
                    <td>@money($t->amount)</td>
                    <td><x-status-badge :status="$t->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Nothing yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
