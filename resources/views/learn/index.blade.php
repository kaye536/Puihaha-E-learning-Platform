@extends('layouts.app')
@section('title', 'Learning')

@section('content')
@php($inProgress = $courses->filter(fn ($c) => $enrollments->has($c->id) && ! $enrollments[$c->id]->isComplete()))
@php($completed = $courses->filter(fn ($c) => $enrollments->has($c->id) && $enrollments[$c->id]->isComplete()))
@php($others = $courses->filter(fn ($c) => ! $enrollments->has($c->id)))

<div class="page-head">
    <div>
        <span class="eyebrow">E-learning</span>
        <h1>Learning</h1>
        <p class="muted">Short courses for members. Finish the lessons, pass the quiz and earn a certificate.</p>
    </div>
</div>

@if ($member->status !== 'active')
    <div class="callout callout-info"><strong>Courses open once your membership is active.</strong> You can still browse what is on offer.</div>
@endif

@if ($courses->isEmpty())
    <div class="card"><p class="muted" style="margin:0">No courses are available yet. Please check back soon.</p></div>
@endif

@foreach ([['Continue learning', $inProgress], ['Completed', $completed], ['All courses', $others]] as [$heading, $group])
    @if ($group->isNotEmpty())
        <h2 style="margin:1.4rem 0 .8rem">{{ $heading }}</h2>
        <div class="course-grid">
            @foreach ($group as $course)
                @php($enrollment = $enrollments->get($course->id))
                @php($pct = $enrollment ? $progress[$course->id]['percent'] : 0)
                <article class="course-card">
                    <div class="course-meta">
                        <span class="badge badge-{{ $enrollment?->isComplete() ? 'approved' : ($enrollment ? 'pending' : 'paid') }}">{{ $enrollment?->isComplete() ? 'completed' : ($enrollment ? 'in progress' : 'new') }}</span>
                        <span class="small muted">{{ $course->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons_count) }}{{ $course->questions_count ? ' · quiz' : '' }}</span>
                    </div>
                    <h3>{{ $course->title }}</h3>
                    <p class="muted">{{ $course->summary ?: 'A short course for members.' }}</p>
                    @if ($enrollment)
                        <div class="progress" title="{{ $pct }}%"><span style="width:{{ $pct }}%"></span></div>
                        <div class="small muted">{{ $progress[$course->id]['done'] }} of {{ $progress[$course->id]['total'] }} lessons done</div>
                    @elseif ($problems[$course->id])
                        <div class="small muted">🔒 {{ $problems[$course->id] }}</div>
                    @endif
                    <div class="course-foot">
                        <a href="{{ route('learn.show', $course) }}" class="btn btn-sm {{ $enrollment ? '' : 'btn-light' }}">{{ $enrollment?->isComplete() ? 'Review' : ($enrollment ? 'Continue' : 'View course') }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endforeach
@endsection
