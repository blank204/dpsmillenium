@extends('admin.layout')
@section('title', 'Security')
@section('content')
<div data-screen="security">
    <h1>Security</h1>
    @include('admin.partials.errors')

    <form method="post" action="{{ route('admin.security.editor-password') }}" data-region="editor-password">@csrf
        <h2>Change the shared Editor password</h2>
        <p>Everyone using the Editor login will be logged out. Use this when a staff member leaves.</p>
        <label>New password (at least 12 characters) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
        <label>Repeat new password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        @include('admin.partials.actor-name')
        <button>Change Editor password</button>
    </form>

    <form method="post" action="{{ route('admin.security.own-password') }}" data-region="own-password">@csrf
        <h2>Change your Super admin password</h2>
        <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
        <label>New password (at least 12 characters) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
        <label>Repeat new password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        @include('admin.partials.actor-name')
        <button>Change my password</button>
    </form>

    <form method="post" action="{{ route('admin.security.logout-all') }}" data-region="logout-all">@csrf
        <h2>Log out every device</h2>
        <p>Logs out all Editor and Super admin sessions, including yours, without changing passwords.</p>
        @include('admin.partials.actor-name')
        <button>Log out everyone</button>
    </form>

    <h2>Active sessions</h2>
    <table data-region="sessions">
        <thead><tr><th>Login</th><th>IP</th><th>Device / browser</th><th>Last active</th></tr></thead>
        <tbody>
        @forelse ($sessions as $s)
            <tr><td>{{ $s->account }}</td><td>{{ $s->ip }}</td><td>{{ \Illuminate\Support\Str::limit($s->agent, 80) }}</td><td>{{ $s->last->diffForHumans() }}</td></tr>
        @empty
            <tr data-state="empty"><td colspan="4">No active sessions.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
