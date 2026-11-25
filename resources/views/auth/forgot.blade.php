@extends('layouts.auth')
@section('title', 'Forgot password')
@section('heading', 'Forgot your password?')
@section('sub', 'Enter your email and we’ll send you a link to choose a new one.')

@section('form')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="form-group" style="margin-bottom:1.25rem">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    </div>
    <button type="submit" class="btn btn-block">Email me a reset link</button>
</form>
@endsection
