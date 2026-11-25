@extends('layouts.public')
@section('title', 'Application received')

@section('content')
<section class="thanks-card">
    <div class="thanks-icon" aria-hidden="true">✓</div>
    <span class="eyebrow">Application received</span>
    <h1>Thank you, {{ $application['name'] }}!</h1>

    @if ($application['reference'])
        <p class="lead">Your application is now <strong>pending review</strong>. We sent a confirmation to <strong>{{ $application['email'] }}</strong>.</p>
        <div class="thanks-numbers">
            <div><small>Membership number</small><strong class="mono">{{ $application['membership_number'] }}</strong></div>
            <div><small>Reference number</small><strong class="mono">{{ $application['reference'] }}</strong></div>
        </div>
        <p class="hint">Please keep these numbers. If the email doesn’t arrive within a few minutes, check your spam folder.</p>
    @else
        <p class="lead">Your application has been received.</p>
    @endif

    <div class="actions" style="justify-content:center;margin-top:1.4rem">
        <a href="{{ url('/') }}" class="btn btn-lg">Back to home</a>
    </div>
</section>
@endsection
