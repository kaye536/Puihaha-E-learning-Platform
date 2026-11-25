@extends('layouts.app')
@php($isEdit = $question->exists)
@section('title', $isEdit ? 'Edit Question' : 'Add Question')

@section('content')
@php
    $options = old('options', array_pad($question->options ?? [], 4, ''));
    $correct = (int) old('correct_option', $question->correct_option ?? 0);
@endphp
<div class="page-head">
    <div><span class="eyebrow">{{ $course->title }}</span><h1>{{ $isEdit ? 'Edit Question' : 'Add Question' }}</h1></div>
    <a href="{{ route('courses.show', $course) }}" class="btn btn-light">Cancel</a>
</div>

<div class="card" style="max-width:760px">
    <form method="POST" action="{{ $isEdit ? route('questions.update', $question) : route('courses.questions.store', $course) }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-group @error('prompt') has-error @enderror" style="margin-bottom:1rem">
            <label for="prompt">Question <span class="req">*</span></label>
            <textarea id="prompt" name="prompt" required maxlength="1000">{{ old('prompt', $question->prompt) }}</textarea>
            @error('prompt')<span class="error-text">{{ $message }}</span>@enderror
        </div>

        <div class="form-group @error('options') has-error @enderror @error('correct_option') has-error @enderror" style="margin-bottom:1rem">
            <label>Answer choices <span class="req">*</span></label>
            <span class="hint">Fill in two to four choices and select the correct one. Empty choices are ignored.</span>
            @for ($i = 0; $i < 4; $i++)
                <div class="option-row">
                    <input type="radio" name="correct_option" value="{{ $i }}" @checked($correct === $i) aria-label="Choice {{ $i + 1 }} is correct">
                    <input type="text" name="options[{{ $i }}]" value="{{ $options[$i] ?? '' }}" maxlength="255" placeholder="Choice {{ $i + 1 }}" aria-label="Choice {{ $i + 1 }}">
                </div>
            @endfor
            @error('options')<span class="error-text">{{ $message }}</span>@enderror
            @error('correct_option')<span class="error-text">{{ $message }}</span>@enderror
        </div>

        <div class="form-group @error('position') has-error @enderror" style="margin-bottom:1.25rem;max-width:200px">
            <label for="position">Order</label>
            <input type="number" id="position" name="position" value="{{ old('position', $question->position) }}" min="1" max="9999">
            @error('position')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn">{{ $isEdit ? 'Save Changes' : 'Add Question' }}</button>
    </form>
</div>
@endsection
