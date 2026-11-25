@extends('layouts.app')
@section('title', 'Certificate – '.$course->title)

@section('content')
<div class="page-head no-print">
    <h1>Certificate of Completion</h1>
    <div class="actions">
        <a href="{{ route('learn.show', $course) }}" class="btn btn-light">Back to course</a>
        <button type="button" class="btn" onclick="window.print()">Print certificate</button>
    </div>
</div>

<div class="certificate">
    <x-logo :size="60" word tone="maroon" />
    <div class="cert-kicker">{{ config('membership.organization') }}</div>
    <h2 class="cert-title">Certificate of Completion</h2>
    <p class="cert-line">This certifies that</p>
    <div class="cert-name">{{ $member->full_name }}</div>
    <p class="cert-line">has successfully completed the course</p>
    <div class="cert-course">{{ $course->title }}</div>
    @if ($bestScore !== null)
        <p class="cert-line">with a final quiz score of <strong>{{ $bestScore }}%</strong></p>
    @endif
    <div class="cert-meta">
        <div><span>Date completed</span><strong>{{ $enrollment->completed_at->format('F d, Y') }}</strong></div>
        <div><span>Certificate No.</span><strong class="mono">{{ $enrollment->certificate_number }}</strong></div>
        <div><span>Membership No.</span><strong class="mono">{{ $member->membership_number }}</strong></div>
    </div>
</div>
@endsection
