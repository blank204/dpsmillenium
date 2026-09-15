<?php

namespace App\Http\Controllers\Admin;

use App\Content\Blocks\BlockNormalizer;
use App\Content\PostService;
use App\Content\PostStatus;
use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\Grade;
use App\Models\NewsletterSeries;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request)
    {
        $now = CarbonImmutable::now();
        $query = Post::query()->orderByDesc('updated_at');

        if ($section = Section::tryFrom((string) $request->query('section'))) {
            $query->ofSection($section);
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%');
        }
        $status = (string) $request->query('status');
        match ($status) {
            'draft' => $query->where('status', PostStatus::Draft->value),
            'scheduled' => $query->where('status', PostStatus::Scheduled->value)->where('publish_at', '>', $now),
            'live' => $query->publicEligible($now),
            'hidden' => $query->where('status', '!=', PostStatus::Draft->value)->whereNotNull('remove_on')->where('remove_on', '<=', $now),
            default => null,
        };

        return view('admin.posts.index', ['posts' => $query->paginate(25)->withQueryString(), 'sections' => Section::cases()]);
    }

    public function choose()
    {
        return view('admin.posts.choose', ['sections' => Section::cases()]);
    }

    public function create(string $section)
    {
        $section = Section::tryFrom($section) ?? abort(404);

        return view('admin.posts.form', $this->formData(new Post(['section' => $section, 'status' => PostStatus::Draft])));
    }

    public function store(SavePostRequest $request, string $section)
    {
        $section = Section::tryFrom($section) ?? abort(404);
        $isSuper = $request->user()->isSuperAdmin();

        if ($request->validated('intent') === 'draft') {
            $post = $this->posts->saveDraft(null, $section, $request->postData(), $request->actor(), $isSuper);
            return redirect()->route('admin.posts.edit', $post)->with('status', 'Draft saved.')->with('clear_autosave', 'new-'.$section->value);
        }

        $post = new Post(['section' => $section, 'status' => PostStatus::Draft]);
        $post = $this->posts->publish($post, $request->postData(), $request->publishOptions(), $request->actor(), $isSuper);

        return redirect()->route('admin.posts.edit', $post)->with('status', $this->publishedMessage($post))->with('clear_autosave', 'new-'.$section->value);
    }

    public function edit(Post $post)
    {
        abort_if($post->purged_at !== null, 404);

        return view('admin.posts.form', $this->formData($post));
    }

    public function update(SavePostRequest $request, Post $post)
    {
        $isSuper = $request->user()->isSuperAdmin();

        if ($request->validated('intent') === 'draft') {
            $this->posts->saveDraft($post, $post->section, $request->postData(), $request->actor(), $isSuper);
            return redirect()->route('admin.posts.edit', $post)->with('status', 'Draft saved.')->with('clear_autosave', 'post-'.$post->id);
        }

        $post = $this->posts->publish($post, $request->postData(), $request->publishOptions(), $request->actor(), $isSuper);

        return redirect()->route('admin.posts.edit', $post)->with('status', $this->publishedMessage($post))->with('clear_autosave', 'post-'.$post->id);
    }

    public function destroy(NameOnlyRequest $request, Post $post)
    {
        $this->posts->delete($post, $request->actor());

        return redirect()->route('admin.posts.index')->with('status', '"'.$post->title.'" moved to the recycle bin. IT can restore it within '.config('cms.recycle_days').' days.');
    }

    public function preview(Request $request, BlockNormalizer $normalizer)
    {
        $section = Section::tryFrom((string) $request->input('section')) ?? abort(404);
        try {
            $blocks = $normalizer->normalize((array) json_decode((string) $request->input('blocks_json', '[]'), true), $request->user()->isSuperAdmin());
        } catch (ValidationException $e) {
            return response()->view('admin.posts.preview', ['errors_list' => $e->errors(), 'post' => null], 422);
        }

        $post = new Post(['section' => $section, 'title' => (string) $request->input('title'), 'summary' => $request->input('summary')]);
        $post->blocks = $blocks;

        return view('admin.posts.preview', ['post' => $post, 'errors_list' => []]);
    }

    private function publishedMessage(Post $post): string
    {
        return match ($post->adminStatus()) {
            'scheduled' => 'Scheduled for '.$post->publish_at->format('j M Y, g:i A').'.',
            default => 'Published. It is now live on the website.',
        };
    }

    private function formData(Post $post): array
    {
        return [
            'post' => $post,
            'grades' => Grade::ordered()->visible()->get(),
            'series' => NewsletterSeries::visible()->orderBy('name')->get(),
            'autosaveKey' => $post->exists ? 'post-'.$post->id : 'new-'.$post->section->value,
        ];
    }
}
