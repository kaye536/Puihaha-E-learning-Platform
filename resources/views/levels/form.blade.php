@extends('layouts.app')
@php($isEdit = $level->exists)
@section('title', $isEdit ? 'Edit Level' : 'Add Level')

@section('content')
<div class="page-head">
    <h1>{{ $isEdit ? 'Edit Level' : 'Add Level' }}</h1>
    <a href="{{ route('levels.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $isEdit ? route('levels.update', $level) : route('levels.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-grid" style="margin-bottom:1rem">
            <div class="form-group full @error('name') has-error @enderror">
                <label for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $level->name) }}" required maxlength="100">
                @error('name')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('fee') has-error @enderror">
                <label for="fee">Fee <span class="req">*</span></label>
                <input type="number" id="fee" name="fee" step="0.01" min="0" value="{{ old('fee', $level->fee) }}" required>
                @error('fee')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('validity_months') has-error @enderror">
                <label for="validity_months">Validity (months) <span class="req">*</span></label>
                <input type="number" id="validity_months" name="validity_months" min="1" max="120" value="{{ old('validity_months', $level->validity_months) }}" required>
                @error('validity_months')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group full @error('benefits') has-error @enderror">
                <label for="benefits">Benefits</label>
                <textarea id="benefits" name="benefits" maxlength="2000">{{ old('benefits', $level->benefits) }}</textarea>
                @error('benefits')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        <button type="submit" class="btn">{{ $isEdit ? 'Save Changes' : 'Create Level' }}</button>
    </form>
</div>
@endsection
