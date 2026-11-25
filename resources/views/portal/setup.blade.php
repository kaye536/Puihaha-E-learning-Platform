@extends('layouts.auth')
@section('title', 'Set up your portal')
@section('heading', 'Welcome, '.\Illuminate\Support\Str::before($member->name, ' ').'!')
@section('sub', 'Choose a password to open your member portal.')

@section('form')
<p class="small muted" style="margin-top:0">Your username will be <strong>{{ $member->email }}</strong>.</p>
{{-- Post back to the same signed URL so the signature is verified again on submit. --}}
<form method="POST" action="{{ request()->fullUrl() }}">
    @csrf
    <div class="form-group" style="margin-bottom:1rem">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="new-password">
        <span class="hint">At least 8 characters.</span>
    </div>
    <div class="form-group" style="margin-bottom:1.25rem">
        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-block">Set password &amp; sign in</button>
</form>
@endsection
