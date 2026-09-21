{{-- One row in a homepage box or a section listing. --}}
@php($scope = $post->gradeScope()->label())
<div>
    <a class="item-title" href="{{ $post->publicPath() }}">
        @if ($post->isPinnedAt($now))<span class="pin">Pinned</span>@endif
        {{ $post->title }}
    </a>
    @if ($scope)<p style="margin-top:.3rem"><span class="grade-tag">{{ $scope }}</span></p>@endif
    <p class="meta">
        <time datetime="{{ $post->sort_at?->toDateString() }}">{{ $post->sort_at?->format('j M Y') }}</time>
        @if ($post->updated_at_public && $post->first_published_at && $post->updated_at_public->gt($post->first_published_at))
            · Updated {{ $post->updated_at_public->format('j M Y') }}
        @endif
    </p>
</div>
