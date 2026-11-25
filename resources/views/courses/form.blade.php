@extends('layouts.app')
@php($isEdit = $course->exists)
@section('title', $isEdit ? 'Edit Course' : 'Add Course')

@section('content')
<div class="page-head">
    <h1>{{ $isEdit ? 'Edit Course' : 'Add Course' }}</h1>
    <a href="{{ $isEdit ? route('courses.show', $course) : route('courses.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card" style="max-width:760px">
    <form method="POST" action="{{ $isEdit ? route('courses.update', $course) : route('courses.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-group @error('title') has-error @enderror" style="margin-bottom:1rem">
            <label for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required maxlength="150">
            @error('title')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="form-group @error('summary') has-error @enderror" style="margin-bottom:1rem">
            <label for="summary">Short summary</label>
            <input type="text" id="summary" name="summary" value="{{ old('summary', $course->summary) }}" maxlength="300">
            <span class="hint">One or two sentences shown on the course card.</span>
            @error('summary')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="form-group @error('description') has-error @enderror" style="margin-bottom:1rem">
            <label for="description">What members will learn</label>
            <textarea id="description" name="description" maxlength="5000">{{ old('description', $course->description) }}</textarea>
            @error('description')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="form-grid" style="margin-bottom:1rem">
            <div class="form-group @error('membership_level_id') has-error @enderror">
                <label for="membership_level_id">Available to</label>
                <select id="membership_level_id" name="membership_level_id">
                    <option value="">All active members</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected((string) old('membership_level_id', $course->membership_level_id) === (string) $level->id)>{{ $level->name }} level and above</option>
                    @endforeach
                </select>
                @error('membership_level_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('pass_mark') has-error @enderror">
                <label for="pass_mark">Quiz pass mark (%) <span class="req">*</span></label>
                <input type="number" id="pass_mark" name="pass_mark" value="{{ old('pass_mark', $course->pass_mark) }}" min="1" max="100" required>
                @error('pass_mark')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-group" style="margin-bottom:1.25rem">
            <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published))> Published (visible to members)</label>
            <span class="hint">A course only appears to members once it is published and has at least one lesson.</span>
        </div>
        <button type="submit" class="btn">{{ $isEdit ? 'Save Changes' : 'Create Course' }}</button>
    </form>
</div>
@endsection
