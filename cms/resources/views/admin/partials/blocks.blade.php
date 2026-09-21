@php($media = \App\Models\MediaAsset::whereIn('id', collect($blocks)->flatMap(fn ($b) => array_filter([$b['media_id'] ?? null, ...array_column($b['items'] ?? [], 'media_id'), ...array_column($b['files'] ?? [], 'media_id')]))->all())->get()->keyBy('id'))
@foreach ($blocks as $block)
    <div data-block="{{ $block['type'] }}">
    @switch($block['type'])
        @case('text'){!! $block['html'] !!}@break
        @case('image')
            @if ($m = $media[$block['media_id']] ?? null)
                <figure><picture><source type="image/webp" srcset="{{ $m->url('400.webp') }} 400w, {{ $m->url('800.webp') }} 800w, {{ $m->url('1600.webp') }} 1600w"><img src="{{ $m->url('800.jpg') }}" srcset="{{ $m->url('400.jpg') }} 400w, {{ $m->url('800.jpg') }} 800w, {{ $m->url('1600.jpg') }} 1600w" sizes="(max-width: 800px) 100vw, 800px" alt="{{ $block['caption'] ?? '' }}" loading="lazy"></picture>@if($block['caption'])<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
            @endif
            @break
        @case('gallery')
            <ul data-region="gallery">@foreach ($block['items'] as $item)@if ($m = $media[$item['media_id']] ?? null)<li><a href="{{ $m->url('1600.jpg') }}"><img src="{{ $m->url('400.jpg') }}" alt="{{ $item['caption'] ?? '' }}" loading="lazy"></a>@if($item['caption'])<span>{{ $item['caption'] }}</span>@endif</li>@endif @endforeach</ul>
            @break
        @case('pdf')
            <ul data-region="pdfs">@foreach ($block['files'] as $file)@if ($m = $media[$file['media_id']] ?? null)<li><a href="{{ $m->url('file.pdf') }}" target="_blank" rel="noopener">{{ $file['label'] }}</a> · <a href="{{ $m->url('file.pdf') }}?download=1">Download</a></li>@endif @endforeach</ul>
            @break
        @case('link')<p><a href="{{ $block['url'] }}" rel="noopener noreferrer" target="_blank" data-region="link-button">{{ $block['label'] }}</a></p>@break
        @case('youtube')
            @if ($block['mode'] === 'player')
                <div data-region="youtube-player" data-video-id="{{ $block['video_id'] }}"><a href="{{ $block['url'] }}" target="_blank" rel="noopener"><img src="https://i.ytimg.com/vi/{{ $block['video_id'] }}/hqdefault.jpg" alt="Play video on YouTube" loading="lazy"></a></div>
            @else
                <p><a href="{{ $block['url'] }}" target="_blank" rel="noopener">Watch on YouTube</a></p>
            @endif
            @break
        @case('video')
            @if ($m = $media[$block['media_id']] ?? null)<video controls preload="metadata" src="{{ $m->url('file.mp4') }}"></video>@if($block['caption'])<p>{{ $block['caption'] }}</p>@endif @endif
            @break
        @case('table')
            <div data-region="table-scroll"><table>@foreach ($block['rows'] as $i => $row)<tr>@foreach ($row as $cell)@if ($i === 0 && $block['has_header'])<th scope="col">{{ $cell }}</th>@else<td>{!! nl2br(e($cell)) !!}</td>@endif @endforeach</tr>@endforeach</table></div>
            @break
        @case('html_page')
            @php($page = \App\Models\HtmlPage::find($block['html_page_id']))
            @if ($page && \Illuminate\Support\Facades\Route::has('pages.sandboxed'))
                @if ($block['mode'] === 'embed')<iframe src="{{ route('pages.sandboxed', $page->token) }}" sandbox="allow-scripts allow-popups" title="{{ $page->title }}" loading="lazy"></iframe>@else<p><a href="{{ route('pages.sandboxed', $page->token) }}" target="_blank" rel="noopener">{{ $page->title }}</a></p>@endif
            @endif
            @break
    @endswitch
    </div>
@endforeach
