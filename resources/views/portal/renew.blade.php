@extends('layouts.app')
@section('title', 'Renew membership')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Member portal</span>
        <h1>Renew membership</h1>
        <p class="muted">Valid until <strong>{{ $member->membership_expiry?->format('F d, Y') ?? '—' }}</strong>. Renewing continues from that date.</p>
    </div>
    <a href="{{ route('portal.index') }}" class="btn btn-light">Back</a>
</div>

@if (! $renewal['allowed'])
    <div class="callout callout-info">{{ $renewal['reason'] }}</div>
@else
    <div class="card form-card form-narrow">
        <form method="POST" action="{{ route('portal.renew.store') }}">
            @csrf
            <section class="form-section">
                <div class="fs-head"><span class="fs-num">1</span><div><h2>Choose your level</h2><p>Keep your current level or switch to another one.</p></div></div>
                <div class="form-group @error('membership_level_id') has-error @enderror">
                    <div class="level-grid">
                        @foreach ($levels as $l)
                            <label class="level-card">
                                <input type="radio" name="membership_level_id" value="{{ $l->id }}" @checked(old('membership_level_id', $member->membership_level_id) == $l->id) required>
                                <span class="level-body">
                                    <span class="level-name">{{ $l->name }}@if ($l->id === $member->membership_level_id) <small>(current)</small>@endif</span>
                                    <span class="level-fee">@money($l->fee)</span>
                                    <span class="level-meta">valid for {{ $l->validity_months }} month(s)</span>
                                    @if ($l->benefits)<span class="level-benefits">{{ \Illuminate\Support\Str::limit($l->benefits, 90) }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('membership_level_id')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </section>
            <div class="form-actions">
                <span class="note">Payment is made at the membership office after you submit.</span>
                <button type="submit" class="btn btn-lg">Request renewal</button>
            </div>
        </form>
    </div>
@endif
@endsection
