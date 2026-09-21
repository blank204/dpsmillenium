@extends('admin.layout')
@section('title', 'Security')
@section('content')
<div data-screen="security">
    <h1 class="h1">Security</h1>
    <p class="lede">Two shared logins. Change a password here and everyone using it is signed out.</p>

    @include('admin.partials.errors')

    <div class="cards-2">
        <section class="card" data-region="editor-password">
            <h2 class="eyebrow">Change the shared Editor password</h2>
            <p class="meta mt">Everyone using the Editor login will be logged out. Use this when a staff member leaves.</p>
            <form method="post" action="{{ route('admin.security.editor-password') }}">
                @csrf
                <label class="field">
                    <span>New password</span>
                    <input type="password" name="password" required minlength="12" autocomplete="new-password">
                    <span class="hint">At least 12 characters.</span>
                </label>
                <label class="field">
                    <span>Repeat new password</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
                @include('admin.partials.actor-name')
                <p class="btn-row mt"><button class="btn btn-primary">Change Editor password</button></p>
            </form>
        </section>

        <section class="card" data-region="own-password">
            <h2 class="eyebrow">Change your Super admin password</h2>
            <p class="meta mt">Your other devices will be signed out. You stay signed in here.</p>
            <form method="post" action="{{ route('admin.security.own-password') }}">
                @csrf
                <label class="field">
                    <span>Current password</span>
                    <input type="password" name="current_password" required autocomplete="current-password">
                </label>
                <label class="field">
                    <span>New password</span>
                    <input type="password" name="password" required minlength="12" autocomplete="new-password">
                    <span class="hint">At least 12 characters.</span>
                </label>
                <label class="field">
                    <span>Repeat new password</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
                @include('admin.partials.actor-name')
                <p class="btn-row mt"><button class="btn btn-primary">Change my password</button></p>
            </form>
        </section>
    </div>

    <section class="card mt-lg" data-region="sessions">
        <h2 class="eyebrow">Who is signed in</h2>
        @if ($sessions->isEmpty())
            <p class="meta mt">Nobody else is signed in right now.</p>
        @else
            <div class="card-list mt">
                @foreach ($sessions as $s)
                    <div class="split">
                        <div>
                            <b class="row-title">{{ $s->account }}</b>
                            @if ($s->current)<span class="badge badge-current">This device</span>@endif
                            <p class="meta">{{ \Illuminate\Support\Str::limit($s->agent, 80) }} · {{ $s->ip }}</p>
                            <p class="meta">Last seen {{ $s->last->diffForHumans() }}</p>
                        </div>
                        @unless ($s->current)
                            <form method="post" action="{{ route('admin.security.logout-session') }}" class="grid-2" style="align-items:end;flex:0 1 20rem">
                                @csrf
                                <input type="hidden" name="session_id" value="{{ $s->id }}">
                                @include('admin.partials.actor-name', ['compact' => true])
                                <div><button class="btn btn-sm">Sign out</button></div>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif

        <div class="divider"></div>
        <form method="post" action="{{ route('admin.security.logout-all') }}" data-region="logout-all" style="max-width:24rem">
            @csrf
            <h2 class="eyebrow">Log out every device</h2>
            <p class="meta mt">Logs out all Editor and Super admin sessions, including yours, without changing passwords.</p>
            @include('admin.partials.actor-name')
            <p class="btn-row mt"><button class="btn btn-danger">Sign everyone out</button></p>
        </form>

        <p class="hint">Failed logins lock that login for {{ (int) (config('cms.login.lockout_seconds') / 60) }} minutes after {{ config('cms.login.max_attempts') }} tries. Lockouts appear in the change log.</p>
    </section>
</div>
@endsection
