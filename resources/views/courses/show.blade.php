@extends('layouts.app')
@section('title', $course->title)

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">Course</span>
        <h1>{{ $course->title }}</h1>
        <p class="muted">{{ $course->summary ?: 'No summary yet.' }}</p>
    </div>
    <div class="actions">
        <span class="badge badge-{{ $course->is_published ? 'active' : 'inactive' }}">{{ $course->is_published ? 'published' : 'draft' }}</span>
        <a href="{{ route('courses.index') }}" class="btn btn-light">All courses</a>
        @can('courses.manage')
        <a href="{{ route('courses.edit', $course) }}" class="btn btn-light">Edit course</a>
        @endcan
    </div>
</div>

<div class="grid grid-3" style="margin-bottom:1.25rem">
    <div class="stat"><div class="label">Lessons</div><div class="value">{{ $course->lessons->count() }}</div></div>
    <div class="stat green"><div class="label">Quiz questions</div><div class="value">{{ $course->questions->count() }}</div><div class="small muted">Pass mark {{ $course->pass_mark }}%</div></div>
    <div class="stat amber"><div class="label">Learners</div><div class="value">{{ $enrollments->count() }}</div><div class="small muted">{{ $enrollments->whereNotNull('completed_at')->count() }} completed</div></div>
</div>

@if ($course->is_published && $course->lessons->isEmpty())
    <div class="callout callout-warn"><strong>Members cannot see this course yet.</strong> Add at least one lesson first.</div>
@endif

<div class="card">
    <div class="page-head" style="margin-bottom:.8rem">
        <h2 style="margin:0">Lessons</h2>
        @can('courses.manage')<a href="{{ route('courses.lessons.create', $course) }}" class="btn btn-sm">+ Add lesson</a>@endcan
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Title</th><th>Content</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($course->lessons as $lesson)
                <tr>
                    <td>{{ $lesson->position }}</td>
                    <td><strong>{{ $lesson->title }}</strong></td>
                    <td class="small muted">{{ $lesson->content ? 'Text' : '' }}{{ $lesson->content && $lesson->video_url ? ' + ' : '' }}{{ $lesson->video_url ? 'Video' : '' }}</td>
                    <td class="right nowrap">
                        @can('courses.manage')
                        <a href="{{ route('lessons.edit', $lesson) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('lessons.destroy', $lesson) }}" class="inline" data-confirm="Delete the lesson '{{ $lesson->title }}'?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No lessons yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:.8rem">
        <div><h2 style="margin:0">Final quiz</h2><p class="small muted" style="margin:.2rem 0 0">Optional. Members unlock it after finishing every lesson and need {{ $course->pass_mark }}% to pass.</p></div>
        @can('courses.manage')<a href="{{ route('courses.questions.create', $course) }}" class="btn btn-sm">+ Add question</a>@endcan
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Question</th><th>Choices</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($course->questions as $question)
                <tr>
                    <td>{{ $question->position }}</td>
                    <td><strong>{{ \Illuminate\Support\Str::limit($question->prompt, 90) }}</strong>
                        <div class="small muted">Correct: {{ $question->options[$question->correct_option] ?? '—' }}</div></td>
                    <td>{{ count($question->options) }}</td>
                    <td class="right nowrap">
                        @can('courses.manage')
                        <a href="{{ route('questions.edit', $question) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('questions.destroy', $question) }}" class="inline" data-confirm="Delete this question?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No quiz questions. Members will complete the course by finishing the lessons.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Learner progress</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Member</th><th>Enrolled</th><th>Progress</th><th>Best quiz score</th><th>Certificate</th></tr></thead>
            <tbody>
            @forelse ($enrollments as $enrollment)
                @php($total = $course->lessons->count())
                @php($percent = $total > 0 ? min(100, (int) round($enrollment->completions_count / $total * 100)) : 0)
                <tr>
                    <td>@can('members.view')<a href="{{ route('members.show', $enrollment->member) }}">{{ $enrollment->member->full_name }}</a>@else{{ $enrollment->member->full_name }}@endcan</td>
                    <td class="nowrap">{{ $enrollment->enrolled_at->format('M d, Y') }}</td>
                    <td style="min-width:150px"><div class="progress" title="{{ $percent }}%"><span style="width:{{ $percent }}%"></span></div><span class="small muted">{{ min($total, $enrollment->completions_count) }} of {{ $total }} lessons</span></td>
                    <td>{{ $learning->bestScore($enrollment) !== null ? $learning->bestScore($enrollment).'%' : '—' }}</td>
                    <td>@if ($enrollment->certificate_number)<span class="mono">{{ $enrollment->certificate_number }}</span>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Nobody has enrolled yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
