<?php

namespace App\Http\Controllers\Admin;

use App\Content\PostService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\Post;
use App\Models\PostVersion;

class VersionController extends Controller
{
    public function index(Post $post)
    {
        return view('admin.posts.versions', ['post' => $post, 'versions' => $post->versions()->get()]);
    }

    public function restore(NameOnlyRequest $request, Post $post, PostVersion $version, PostService $posts)
    {
        $posts->restoreVersion($post, $version, $request->actor());

        return redirect()->route('admin.posts.edit', $post)->with('status', "Version {$version->version} restored.");
    }
}
