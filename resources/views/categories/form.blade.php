@extends('layouts.app')
@php($isEdit = $category->exists)
@section('title', $isEdit ? 'Edit Category' : 'Add Category')

@section('content')
<div class="page-head">
    <h1>{{ $isEdit ? 'Edit Category' : 'Add Category' }}</h1>
    <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $isEdit ? route('categories.update', $category) : route('categories.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-group @error('name') has-error @enderror" style="margin-bottom:1rem">
            <label for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100">
            @error('name')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <div class="form-group @error('description') has-error @enderror" style="margin-bottom:1.25rem">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="1000">{{ old('description', $category->description) }}</textarea>
            @error('description')<span class="error-text">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn">{{ $isEdit ? 'Save Changes' : 'Create Category' }}</button>
    </form>
</div>
@endsection
