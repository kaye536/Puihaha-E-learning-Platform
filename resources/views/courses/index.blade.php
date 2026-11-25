@extends('layouts.app')
@section('title', 'Courses')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">E-learning</span><h1>Courses</h1><p class="muted">Lessons, quizzes and certificates for your members.</p></div>
    @can('courses.manage')
    <a href="{{ route('courses.create') }}" class="btn">+ Add Course</a>
    @endcan
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Course</th><th>Available to</th><th>Lessons</th><th>Quiz</th><th>Learners</th><th>Status</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td><a href="{{ route('courses.show', $course) }}"><strong>{{ $course->title }}</strong></a>
                        @if ($course->summary)<div class="small muted">{{ \Illuminate\Support\Str::limit($course->summary, 90) }}</div>@endif</td>
                    <td>{{ $course->level ? $course->level->name.' and above' : 'All active members' }}</td>
                    <td>{{ $course->lessons_count }}</td>
                    <td>{{ $course->questions_count ? $course->questions_count.' questions' : '—' }}</td>
                    <td>{{ $course->enrollments_count }}</td>
                    <td><span class="badge badge-{{ $course->is_published ? 'active' : 'inactive' }}">{{ $course->is_published ? 'published' : 'draft' }}</span></td>
                    <td class="right nowrap">
                        <a href="{{ route('courses.show', $course) }}" class="btn btn-light btn-sm">Open</a>
                        @can('courses.manage')
                        <a href="{{ route('courses.edit', $course) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}" class="inline"
                              data-confirm="Delete the course '{{ $course->title }}' with all its lessons and questions?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No courses yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $courses->links() }}
</div>
@endsection
