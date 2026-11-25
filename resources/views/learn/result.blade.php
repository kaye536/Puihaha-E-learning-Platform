@extends('layouts.app')
@section('title', 'Quiz result – '.$course->title)

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">{{ $course->title }}</span>
        <h1>Quiz result</h1>
        <p class="muted">Taken {{ $attempt->created_at->format('F d, Y h:i A') }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('learn.show', $course) }}" class="btn btn-light">Back to course</a>
        @if (! $attempt->passed)
            <a href="{{ route('learn.quiz', $course) }}" class="btn">Try again</a>
        @elseif ($enrollment->isComplete())
            <a href="{{ route('learn.certificate', $course) }}" class="btn">View certificate</a>
        @endif
    </div>
</div>

<section class="score-card {{ $attempt->passed ? 'passed' : 'failed' }}">
    <div class="score-value">{{ $attempt->score }}%</div>
    <div>
        <strong>{{ $attempt->passed ? 'Passed — well done!' : 'Not passed yet' }}</strong>
        <p style="margin:.2rem 0 0">
            @if ($attempt->passed)
                You reached the pass mark of {{ $course->pass_mark }}%.@if ($enrollment->isComplete()) Your certificate is ready.@endif
            @else
                You need {{ $course->pass_mark }}% to pass. Review the correct answers below and try again.
            @endif
        </p>
    </div>
</section>

@foreach ($questions as $question)
    @php($chosen = $attempt->answers[$question->id] ?? null)
    @php($right = $chosen === $question->correct_option)
    <div class="card quiz-q">
        <span class="small muted">Question {{ $loop->iteration }}</span>
        <p class="quiz-prompt">{{ $question->prompt }}</p>
        @foreach ($question->options as $index => $option)
            <div class="option review {{ $index === $question->correct_option ? 'is-correct' : '' }} {{ $index === $chosen && ! $right ? 'is-wrong' : '' }}">
                <span>{{ $option }}</span>
                @if ($index === $question->correct_option)<small>✓ Correct answer</small>@endif
                @if ($index === $chosen && ! $right)<small>Your answer</small>@endif
                @if ($index === $chosen && $right)<small>Your answer</small>@endif
            </div>
        @endforeach
    </div>
@endforeach
@endsection
