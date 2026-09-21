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

        $file = $this->checkedHtml($request->file('file'));

        $page = DB::transaction(function () use ($data, $file, $request) {
            $page = HtmlPage::create(['token' => Str::random(32), 'title' => $data['title'], 'bytes' => $file->getSize()]);
            Storage::disk('pages')->putFileAs('', $file, $page->storagePath());
            $this->audit->record('html_page.uploaded', $request->actor(), $page, null, ['title' => $page->title, 'bytes' => $page->bytes, 'sha256' => hash_file('sha256', $file->getRealPath())]);

            return $page;
        });

        return redirect()->route('admin.pages.index')->with('status', "Uploaded. Use custom page #{$page->id} in a post's Custom HTML page block.");
    }

    /**
     * Swap the file behind an existing page, keeping its id and token so every post
     * that already links to it keeps working. Deleting and re-uploading would mint a
     * new token and quietly break those links.
     */
    public function replace(NameOnlyRequest $request, HtmlPage $page)
    {
        $request->validate(['file' => ['required', 'file', 'max:2048']]);
        $file = $this->checkedHtml($request->file('file'));

        DB::transaction(function () use ($page, $file, $request) {
            $before = ['bytes' => $page->bytes];
            Storage::disk('pages')->putFileAs('', $file, $page->storagePath());
            $page->bytes = $file->getSize();
            $page->save();
            $this->audit->record('html_page.replaced', $request->actor(), $page, $before, [
                'bytes' => $page->bytes,
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ]);
        });

        return redirect()->route('admin.pages.index')->with('status', "Replaced the file behind custom page #{$page->id}. The link is unchanged.");
    }

    /** Rejects anything that is not really an HTML page, whatever the file is named. */
    private function checkedHtml(\Illuminate\Http\UploadedFile $file): \Illuminate\Http\UploadedFile
    {
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 2048);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! in_array($mime, ['text/html', 'text/plain'], true) || ! preg_match('/<(!doctype html|html|body|div|table|h1|p)\b/i', $head)) {
            throw ValidationException::withMessages(['file' => 'This is not an HTML page. Upload the .html file.']);
        }

        return $file;
    }

    public function destroy(NameOnlyRequest $request, HtmlPage $page)
    {
        DB::transaction(function () use ($page, $request) {
            $page->delete();
            Storage::disk('pages')->delete($page->storagePath());
            $this->audit->record('html_page.deleted', $request->actor(), $page, ['title' => $page->title], null);
        });

        return back()->with('status', 'Custom page removed. Posts that used it will no longer show it.');
    }
}
