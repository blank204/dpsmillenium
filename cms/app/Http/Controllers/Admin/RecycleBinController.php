<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;

class RecycleBinController extends Controller
{
    public function index()
    {
        return view('admin.recycle.index', [
            'posts' => Post::onlyTrashed()->whereNull('purged_at')->orderByDesc('deleted_at')->get(),
            'days' => (int) config('cms.recycle_days'),
        ]);
    }
}
