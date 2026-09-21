@extends('admin.layout')
@section('title', 'Log in')
@section('content')
<div data-screen="login" class="login-shell">
    <div class="login-box">
        <div class="login-brand">
            <img src="{{ asset('img/shield.svg') }}" alt="">
            <span>
                <b>DPS Millennium</b>
                <span><span>Staff content manager</span></span>
            </span>
        </div>
        <form method="post" action="{{ route('admin.login.attempt') }}" class="login-card">
            @csrf
            <h1>Log in</h1>
            <p class="lede small">Use the shared staff login.</p>

            @if (session('status'))<p class="notice notice-good" role="status">{{ session('status') }}</p>@endif
            @error('username')
                <p data-region="form-errors" class="notice notice-error" role="alert">{{ $message }}</p>
            @enderror

            <label class="field">
                <span>Username</span>
                <input name="username" value="{{ old('username') }}" required maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false">
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <div class="btn-row mt">
                <button type="submit" class="btn btn-primary btn-lg">Log in</button>
            </div>
            <p class="hint">Forgot your password? Contact school IT.</p>
        </form>
    </div>
</div>
@endsection
