@extends('layouts.app')
@section('title', $course->title)

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Course</span>
        <h1>{{ $course->title }}</h1>
        <p class="muted">{{ $course->summary }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('learn.index') }}" class="btn btn-light">All courses</a>
        @if ($enrollment?->isComplete())
            <a href="{{ route('learn.certificate', $course) }}" class="btn">View certificate</a>
        @endif
    </div>
</div>

@if ($problem)
    <div class="callout callout-warn"><strong>{{ $problem }}</strong></div>
@endif

<div class="grid grid-2" style="align-items:start">
    <div class="card">
        <h2>About this course</h2>
        @if ($course->description)
            <p style="white-space:pre-line">{{ $course->description }}</p>
        @endif
        <dl class="details">
            <dt>Lessons</dt><dd>{{ $course->lessons->count() }}</dd>
            <dt>Final quiz</dt><dd>{{ $course->questions->count() ? $course->questions->count().' questions · pass mark '.$course->pass_mark.'%' : 'None' }}</dd>
            <dt>Open to</dt><dd>{{ $course->level ? $course->level->name.' members and above' : 'All active members' }}</dd>
        </dl>

        @if (! $enrollment && ! $problem)
            <form method="POST" action="{{ route('learn.enroll', $course) }}" style="margin-top:1.25rem">
                @csrf
                <button type="submit" class="btn btn-lg">Enrol &amp; start learning</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h2>Lessons</h2>
        @if ($progress)
            <div class="progress" title="{{ $progress['percent'] }}%"><span style="width:{{ $progress['percent'] }}%"></span></div>
            <div class="small muted" style="margin-bottom:.8rem">{{ $progress['done'] }} of {{ $progress['total'] }} lessons done</div>
        @endif
        <ol class="lesson-list">
            @foreach ($course->lessons as $lesson)
                @php($done = in_array($lesson->id, $doneIds, true))
                <li class="{{ $done ? 'done' : '' }}">
                    <span class="tick" aria-hidden="true">{{ $done ? '✓' : $loop->iteration }}</span>
                    @if ($enrollment && ! $problem)
                        <a href="{{ route('learn.lesson', [$course, $lesson]) }}">{{ $lesson->title }}</a>
                    @else
                        <span>{{ $lesson->title }}</span>
                    @endif
                </li>
            @endforeach
            @if ($course->questions->isNotEmpty())
                <li class="{{ $enrollment?->isComplete() ? 'done' : '' }}">
                    <span class="tick" aria-hidden="true">{{ $enrollment?->isComplete() ? '✓' : '?' }}</span>
                    @if ($enrollment && ! $problem && $quizUnlocked)
                        <a href="{{ route('learn.quiz', $course) }}">Final quiz{{ $bestScore !== null ? ' (best '.$bestScore.'%)' : '' }}</a>
                    @else
                        <span class="muted">Final quiz {{ $enrollment ? '— finish all lessons to unlock' : '' }}</span>
                    @endif
                </li>
            @endif
        </ol>

        @if ($attempts->isNotEmpty())
            <h3 style="margin-top:1.2rem">Quiz attempts</h3>
            <ul class="attempts">
                @foreach ($attempts as $attempt)
                    <li><a href="{{ route('learn.quiz.result', [$course, $attempt]) }}">{{ $attempt->created_at->format('M d, Y h:i A') }}</a> — {{ $attempt->score }}% <span class="badge badge-{{ $attempt->passed ? 'approved' : 'expired' }}">{{ $attempt->passed ? 'passed' : 'not passed' }}</span></li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
