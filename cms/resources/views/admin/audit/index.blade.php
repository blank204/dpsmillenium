@extends('admin.layout')
@section('title', 'Change log')
@section('content')
<div data-screen="audit-index">
    <h1>Change log</h1>
    <p>Every change is recorded here permanently. Entries cannot be edited or deleted.</p>
    <form method="post" action="{{ route('admin.audit.verify') }}">@csrf<button>Check the log has not been altered</button></form>
    <form method="get" data-region="filters">
        <label>Name contains <input name="name" value="{{ request('name') }}"></label>
        <label>Action <select name="action"><option value="">All</option>@foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></label>
        <label>From <input type="date" name="from" value="{{ request('from') }}"></label>
        <label>To <input type="date" name="to" value="{{ request('to') }}"></label>
        <label>Post ID <input type="number" name="post" value="{{ request('post') }}"></label>
        <button>Filter</button>
    </form>
    <table data-region="audit-table">
        <thead><tr><th>#</th><th>When</th><th>Name typed</th><th>Login</th><th>Action</th><th>Item</th><th>IP</th><th></th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            <tr data-action="{{ $e->action }}">
                <td>{{ $e->id }}</td><td>{{ $e->occurred_at->format('j M Y, g:i:s A') }}</td><td>{{ $e->actor_name ?? '—' }}</td>
                <td>{{ $e->account_role ?? '—' }}</td><td>{{ $e->action }}</td>
                <td>{{ $e->target_type ? $e->target_type.' #'.$e->target_id : '—' }}</td><td>{{ $e->ip_address ?? '—' }}</td>
                <td><a href="{{ route('admin.audit.show', $e) }}">Details</a></td>
            </tr>
        @empty
            <tr data-state="empty"><td colspan="8">No entries match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $entries->links() }}
</div>
@endsection
