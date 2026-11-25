@extends('layouts.app')
@php($isEdit = $lesson->exists)
@section('title', $isEdit ? 'Edit Lesson' : 'Add Lesson')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">{{ $course->title }}</span><h1>{{ $isEdit ? 'Edit Lesson' : 'Add Lesson' }}</h1></div>
    <a href="{{ route('courses.show', $course) }}" class="btn btn-light">Cancel</a>
</div>

<div class="card" style="max-width:820px">
    <form method="POST" action="{{ $isEdit ? route('lessons.update', $lesson) : route('courses.lessons.store', $course) }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-grid" style="margin-bottom:1rem;grid-template-columns:3fr 1fr">
            <div class="form-group @error('title') has-error @enderror">
                <label for="title">Lesson title <span class="req">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $lesson->title) }}" required maxlength="150">
                @error('title')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('position') has-error @enderror">
                <label for="position">Order</label>
                <input type="number" id="position" name="position" value="{{ old('position', $lesson->position) }}" min="1" max="9999">
                @error('position')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-group @error('video_url') has-error @enderror" style="margin-bottom:1rem">
            <label for="video_url">Video link (optional)</label>
            <input type="text" id="video_url" name="video_url" value="{{ old('video_url', $lesson->video_url) }}" maxlength="500" placeholder="https://www.youtube.com/watch?v=…">
            <span class="hint">YouTube and Vimeo links play inside the lesson; any other https link is shown as a button.</span>
            @error('video_url')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="form-group @error('content') has-error @enderror" style="margin-bottom:1.25rem">
            <label for="content">Lesson text</label>
            <textarea id="content" name="content" style="min-height:280px" maxlength="50000">{{ old('content', $lesson->content) }}</textarea>
            <span class="hint">Markdown is supported: <code>## Heading</code>, <code>**bold**</code>, <code>- list item</code>, <code>[link](https://…)</code>.</span>
            @error('content')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn">{{ $isEdit ? 'Save Changes' : 'Add Lesson' }}</button>
    </form>
</div>
@endsection
