@extends('layouts.app')
@php($isEdit = $user->exists)
@section('title', $isEdit ? 'Edit Account' : 'Add Account')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Access control</span><h1>{{ $isEdit ? 'Edit Account' : 'Add Account' }}</h1></div>
    <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card form-card form-narrow">
    <form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}" autocomplete="off">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="form-section">
            <div class="fs-head"><span class="fs-num">1</span><div><h2>Account</h2><p>Who is this and how do they sign in?</p></div></div>
            <div class="form-grid">
                <div class="form-group @error('name') has-error @enderror">
                    <label for="name">Full name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100">
                    @error('name')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('email') has-error @enderror">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('password') has-error @enderror">
                    <label for="password">Password @unless ($isEdit)<span class="req">*</span>@endunless</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" @unless ($isEdit) required @endunless>
                    <span class="hint">{{ $isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}</span>
                    @error('password')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="fs-head"><span class="fs-num">2</span><div><h2>Role &amp; access</h2><p>The role decides which screens and actions this person can use.</p></div></div>
            <div class="form-group @error('role') has-error @enderror">
                <div class="role-options">
                    @foreach (\App\Models\User::staffRoles() as $key => $role)
                        <label class="level-card">
                            <input type="radio" name="role" value="{{ $key }}" @checked(old('role', $user->role) === $key) required>
                            <span class="level-body">
                                <span class="level-name">{{ $role['label'] }}</span>
                                <span class="level-meta">{{ $role['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('role')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group" style="margin-top:1.1rem">
                <label class="check-card">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                    <span><strong>Account is active</strong><span class="hint">Deactivated accounts cannot sign in and are signed out immediately.</span></span>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <span class="note">Changes apply on the person's next request.</span>
            <button type="submit" class="btn btn-lg">{{ $isEdit ? 'Save Changes' : 'Create Account' }}</button>
        </div>
    </form>
</div>
@endsection
