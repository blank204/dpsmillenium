@extends('admin.layout')
@section('title', 'Change log')
@section('content')
<div data-screen="audit-index">
    <div class="split">
        <div>
            <h1 class="h1">Change log</h1>
            <p class="lede">Every action, in order, with the name the person typed. Entries cannot be edited or removed.</p>
        </div>
        <form method="post" action="{{ route('admin.audit.verify') }}">@csrf<button class="btn">Check the log has not been altered</button></form>
    </div>

    <form method="get" class="filters" data-region="filters">
        <label class="field">
            <span>Name contains</span>
            <input name="name" value="{{ request('name') }}">
        </label>
        <label class="field">
            <span>Action</span>
            <select name="action"><option value="">Any action</option>@foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
        </label>
        <label class="field">
            <span>Login</span>
            <select name="role">
                <option value="">Both logins</option>
                @foreach ($roles as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>@endforeach
            </select>
        </label>
        <label class="field">
            <span>From</span>
            <input type="date" name="from" value="{{ request('from') }}">
        </label>
        <label class="field">
            <span>To</span>
            <input type="date" name="to" value="{{ request('to') }}">
        </label>
        <label class="field">
            <span>Post ID</span>
            <input type="number" name="post" value="{{ request('post') }}">
        </label>
        <button class="btn">Filter</button>
        <a class="btn" href="{{ route('admin.audit.export', request()->query()) }}">Export CSV</a>
    </form>

    @if ($entries->isEmpty())
        <div class="empty" data-state="empty">
            <p>No entries match these filters.</p>
            <p>Widen the dates or choose “Any action”.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="table" data-region="audit-table">
                <thead><tr><th>#</th><th>When</th><th>Typed name</th><th>Login</th><th>Action</th><th>Item</th><th>IP</th></tr></thead>
                <tbody>
                @foreach ($entries as $e)
                    <tr data-action="{{ $e->action }}">
                        <td class="meta">{{ $e->id }}</td>
                        <td class="nowrap"><a href="{{ route('admin.audit.show', $e) }}">{{ $e->occurred_at->format('j M Y, g:i:s A') }}</a></td>
                        <td>{{ $e->actor_name ?? '—' }}</td>
                        <td class="meta">{{ $e->account_role ?? '—' }}</td>
                        <td><code>{{ $e->action }}</code></td>
                        <td class="meta">{{ $e->target_type ? $e->target_type.' #'.$e->target_id : '—' }}</td>
                        <td class="meta">{{ $e->ip_address ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pager">
            <p class="meta">Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}</p>
            {{ $entries->links() }}
        </div>
    @endif
</div>
@endsection
