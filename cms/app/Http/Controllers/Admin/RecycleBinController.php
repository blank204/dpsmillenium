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

    public function restore(\App\Http\Requests\Admin\NameOnlyRequest $request, int $id, \App\Content\PostService $posts)
    {
        $post = Post::onlyTrashed()->whereNull('purged_at')->findOrFail($id);
        $posts->restore($post, $request->actor());

        return redirect()->route('admin.recycle.index')->with('status', '"'.$post->title.'" restored. It is live again if it was live before.');
    }
}
