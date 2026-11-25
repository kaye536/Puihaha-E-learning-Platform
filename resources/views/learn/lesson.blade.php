@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
@php($isDone = in_array($lesson->id, $doneIds, true))
<div class="page-head">
    <div>
        <span class="eyebrow">{{ $course->title }}</span>
        <h1>{{ $lesson->title }}</h1>
        <p class="muted">Lesson {{ $lessons->search(fn ($l) => $l->id === $lesson->id) + 1 }} of {{ $lessons->count() }}</p>
    </div>
    <a href="{{ route('learn.show', $course) }}" class="btn btn-light">Course overview</a>
</div>

<div class="lesson-layout">
    <div>
        <div class="card">
            @if ($embed = $lesson->embedUrl())
                <div class="video-frame"><iframe src="{{ $embed }}" title="{{ $lesson->title }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
            @elseif ($lesson->video_url)
                <p><a href="{{ $lesson->video_url }}" class="btn btn-light" target="_blank" rel="noopener noreferrer">▶ Watch the video</a></p>
            @endif

            @if ($lesson->content)
                <div class="lesson-body">{!! $lesson->renderedContent() !!}</div>
            @endif

            <div class="lesson-nav">
                @if ($previous)
                    <a href="{{ route('learn.lesson', [$course, $previous]) }}" class="btn btn-light">← Previous</a>
                @else
                    <span></span>
                @endif

                <form method="POST" action="{{ route('learn.lesson.complete', [$course, $lesson]) }}">
                    @csrf
                    <button type="submit" class="btn">
                        @if ($next) {{ $isDone ? 'Next lesson →' : 'Mark complete & continue →' }}
                        @elseif ($hasQuiz) {{ $isDone ? 'Go to the quiz →' : 'Mark complete & take the quiz →' }}
                        @else {{ $isDone ? 'Back to the course' : 'Mark complete & finish' }}
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>

    <aside class="card lesson-side">
        <h3>Progress</h3>
        <div class="progress" title="{{ $progress['percent'] }}%"><span style="width:{{ $progress['percent'] }}%"></span></div>
        <div class="small muted" style="margin-bottom:.8rem">{{ $progress['done'] }} of {{ $progress['total'] }} done</div>
        <ol class="lesson-list">
            @foreach ($lessons as $item)
                @php($itemDone = in_array($item->id, $doneIds, true))
                <li class="{{ $itemDone ? 'done' : '' }} {{ $item->id === $lesson->id ? 'current' : '' }}">
                    <span class="tick" aria-hidden="true">{{ $itemDone ? '✓' : $loop->iteration }}</span>
                    <a href="{{ route('learn.lesson', [$course, $item]) }}">{{ $item->title }}</a>
                </li>
            @endforeach
        </ol>
    </aside>
</div>
@endsection
