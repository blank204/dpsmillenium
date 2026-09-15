<?php

namespace App\Http\Controllers;

use App\Models\HtmlPage;
use Illuminate\Support\Facades\Storage;

class SandboxedPageController extends Controller
{
    public function __invoke(string $token)
    {
        $page = HtmlPage::where('token', $token)->first();
        abort_if($page === null || ! Storage::disk('pages')->exists($page->storagePath()), 404);

        return response(Storage::disk('pages')->get($page->storagePath()), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            // Opaque origin: scripts may run but cannot read site cookies, storage or the admin.
            'Content-Security-Policy' => "sandbox allow-scripts allow-popups; default-src 'self' data: https:; script-src 'unsafe-inline' https:; style-src 'unsafe-inline' https:; frame-ancestors 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
