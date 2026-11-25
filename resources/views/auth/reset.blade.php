@extends('layouts.auth')
@section('title', 'Choose a new password')
@section('heading', 'Choose a new password')
@section('sub', 'Use at least 8 characters.')

@section('form')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="form-group" style="margin-bottom:1rem">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
    </div>
    <div class="form-group" style="margin-bottom:1rem">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="new-password">
    </div>
    <div class="form-group" style="margin-bottom:1.25rem">
        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-block">Save password</button>
</form>
@endsection
