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
        return view('admin.audit.index', [
            'entries' => $this->filtered($request)->paginate(50)->withQueryString(),
            'actions' => AuditEntry::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'roles' => AuditEntry::query()->select('account_role')->whereNotNull('account_role')->distinct()->orderBy('account_role')->pluck('account_role'),
        ]);
    }

    /**
     * The whole filtered log as a CSV download. School IT has no database access on
     * shared hosting, so this is the only way they can keep an off-site copy of the
     * log — which is the point of an append-only log in the first place.
     */
    public function export(Request $request, AuditLogger $audit)
    {
        $audit->record('audit.exported', Actor::fromRequest($request, null), null, null, [
            'filters' => array_filter($request->only(['name', 'action', 'role', 'from', 'to', 'post'])),
        ]);

        $query = $this->filtered($request);
        $name = 'change-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel on the school's Windows PCs reads accented names correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Entry', 'When (Qatar)', 'Typed name', 'Login', 'Action', 'Item', 'IP address', 'Device', 'Session', 'Hash']);

            $query->reorder('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [
                        $e->id,
                        $e->occurred_at->format('Y-m-d H:i:s'),
                        $e->actor_name ?? '',
                        $e->account_role ?? '',
                        $e->action,
                        $e->target_type ? $e->target_type.' #'.$e->target_id : '',
                        $e->ip_address ?? '',
                        $e->user_agent ?? '',
                        $e->session_id ?? '',
                        $e->hash,
                    ]);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        $query = AuditEntry::query()->orderByDesc('id');

        if ($name = trim((string) $request->query('name'))) {
            $query->where('actor_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%');
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($role = $request->query('role')) {
            $query->where('account_role', $role);
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

        return $query;
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
