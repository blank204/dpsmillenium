<?php

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\HtmlPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HtmlPageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        return view('admin.pages.index', ['pages' => HtmlPage::orderByDesc('id')->get()]);
    }

    public function store(NameOnlyRequest $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'max:2048'],
        ]);

        $file = $request->file('file');
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 2048);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! in_array($mime, ['text/html', 'text/plain'], true) || ! preg_match('/<(!doctype html|html|body|div|table|h1|p)\b/i', $head)) {
            throw ValidationException::withMessages(['file' => 'This is not an HTML page. Upload the .html file.']);
        }

        $page = DB::transaction(function () use ($data, $file, $request) {
            $page = HtmlPage::create(['token' => Str::random(32), 'title' => $data['title'], 'bytes' => $file->getSize()]);
            Storage::disk('pages')->putFileAs('', $file, $page->storagePath());
            $this->audit->record('html_page.uploaded', $request->actor(), $page, null, ['title' => $page->title, 'bytes' => $page->bytes, 'sha256' => hash_file('sha256', $file->getRealPath())]);

            return $page;
        });

        return redirect()->route('admin.pages.index')->with('status', "Uploaded. Use custom page #{$page->id} in a post's Custom HTML page block.");
    }

    public function destroy(NameOnlyRequest $request, HtmlPage $page)
    {
        DB::transaction(function () use ($page, $request) {
            $page->delete();
            $this->audit->record('html_page.deleted', $request->actor(), $page, ['title' => $page->title], null);
        });

        return back()->with('status', 'Custom page removed. Posts that used it will no longer show it.');
    }
}
