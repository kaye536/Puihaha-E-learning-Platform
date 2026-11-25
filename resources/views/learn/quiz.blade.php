@extends('layouts.app')
@section('title', 'Final quiz – '.$course->title)

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">{{ $course->title }}</span>
        <h1>Final quiz</h1>
        <p class="muted">Answer every question. You need {{ $course->pass_mark }}% to pass{{ $bestScore !== null ? ' · your best score so far is '.$bestScore.'%' : '' }}. You can retake it as often as you like.</p>
    </div>
    <a href="{{ route('learn.show', $course) }}" class="btn btn-light">Back to course</a>
</div>

<form method="POST" action="{{ route('learn.quiz.submit', $course) }}">
    @csrf
    @foreach ($questions as $question)
        <fieldset class="quiz-q card">
            <legend><span class="muted">Question {{ $loop->iteration }} of {{ $questions->count() }}</span></legend>
            <p class="quiz-prompt">{{ $question->prompt }}</p>
            @foreach ($question->options as $index => $option)
                <label class="option">
                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $index }}" @checked((string) old('answers.'.$question->id) === (string) $index) required>
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </fieldset>
    @endforeach
    <button type="submit" class="btn btn-lg">Submit answers</button>
</form>
@endsection
