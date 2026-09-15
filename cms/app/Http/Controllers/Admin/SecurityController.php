<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit()
    {
        $sessions = DB::table('sessions')->whereNotNull('user_id')->orderByDesc('last_activity')->limit(50)->get()
            ->map(fn ($s) => (object) [
                'account' => Account::find($s->user_id)?->username ?? 'unknown',
                'ip' => $s->ip_address,
                'agent' => $s->user_agent,
                'last' => \Carbon\CarbonImmutable::createFromTimestamp($s->last_activity, config('app.timezone')),
            ]);

        return view('admin.security.edit', ['sessions' => $sessions]);
    }

    public function editorPassword(NameOnlyRequest $request)
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:12', 'confirmed']]);
        $editor = Account::where('role', Account::EDITOR)->firstOrFail();
        $editor->password = $data['password'];
        $editor->password_changed_at = now();
        $editor->save();
        $editor->bumpSessionVersion();
        $this->audit->record('security.editor_password_changed', $request->actor(), $editor, null, ['changed' => true]);

        return back()->with('status', 'Editor password changed. Everyone using the Editor login has been logged out. Share the new password with staff privately.');
    }

    public function ownPassword(NameOnlyRequest $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $account = $request->user();
        if (! Hash::check($data['current_password'], $account->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }
        $account->password = $data['password'];
        $account->password_changed_at = now();
        $account->save();
        $account->bumpSessionVersion();
        $request->session()->regenerate();
        $request->session()->put('session_version', $account->fresh()->session_version);
        $this->audit->record('security.own_password_changed', $request->actor(), $account, null, ['changed' => true]);

        return back()->with('status', 'Your password has been changed. Other devices using this login were logged out.');
    }

    public function logoutAll(NameOnlyRequest $request)
    {
        $actor = $request->actor();
        Account::query()->each(fn (Account $a) => $a->bumpSessionVersion());
        DB::table('sessions')->delete();
        $this->audit->record('security.force_logout', $actor, null, null, ['accounts' => Account::count()]);
        Auth::logout();
        $request->session()->invalidate();

        return redirect()->route('admin.login');
    }
}
