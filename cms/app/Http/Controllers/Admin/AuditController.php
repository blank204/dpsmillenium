<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditChain;
use App\Audit\AuditLogger;
use App\Audit\Diff;
use App\Http\Controllers\Controller;
use App\Models\AuditEntry;
use App\Support\Actor;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditEntry::query()->orderByDesc('id');

        if ($name = trim((string) $request->query('name'))) {
            $query->where('actor_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%');
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($from = $request->query('from')) {
            $query->where('occurred_at', '>=', $from.' 00:00:00');
        }
        if ($to = $request->query('to')) {
            $query->where('occurred_at', '<=', $to.' 23:59:59');
        }
        if ($postId = $request->query('post')) {
            $query->where('target_type', 'Post')->where('target_id', (int) $postId);
        }

        return view('admin.audit.index', [
            'entries' => $query->paginate(50)->withQueryString(),
            'actions' => AuditEntry::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function show(AuditEntry $entry)
    {
        return view('admin.audit.show', ['entry' => $entry, 'diff' => Diff::between($entry->beforeData(), $entry->afterData())]);
    }

    public function verify(Request $request, AuditChain $chain, AuditLogger $audit)
    {
        $broken = $chain->firstBrokenId();
        $audit->record('audit.verified', Actor::fromRequest($request, null), null, null, ['first_broken_id' => $broken]);

        return back()->with('status', $broken === null
            ? 'Change log intact: no entries have been altered.'
            : "WARNING: change log ALTERED at or before entry #{$broken}. Tell the school management and keep a backup copy.");
    }
}
