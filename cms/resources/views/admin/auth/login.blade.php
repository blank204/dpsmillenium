@extends('admin.layout')
@section('title', 'Log in')
@section('content')
<h1>Log in</h1>
<form method="post" action="{{ route('admin.login.attempt') }}">
    @csrf
    <label>Username <input name="username" value="{{ old('username') }}" required maxlength="50" autocomplete="username"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    @error('username')<p class="error" role="alert">{{ $message }}</p>@enderror
    <button type="submit">Log in</button>
    <p>Forgot your password? Contact school IT.</p>
</form>
@endsection
