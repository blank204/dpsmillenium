@extends('site.layout', ['marketing' => true])
@section('title', 'DPS Millennium International School · Al Wukair, Qatar')
@section('meta_description', 'A CBSE school opening in Al Wukair, Qatar in April 2027. Construction updates, notices and the school calendar from the DPS Group.')
@push('styles')<link rel="stylesheet" href="{{ asset('site/home.css') }}">@endpush
@php($blurbs = [
    'info' => ['kicker' => '/Information', 'empty' => 'Nothing has been posted yet. Registration details and construction updates will appear here first.'],
    'event' => ['kicker' => '/Events', 'empty' => 'No events yet. Campus open days and admissions sessions will be listed here as soon as dates are set — register and we’ll email you first.'],
    'circular' => ['kicker' => '/Circulars', 'empty' => 'Circulars are issued to enrolled families once the school year starts in April 2027. Anything families need before then is posted under Information.'],
    'newsletter' => ['kicker' => '/Newsletter', 'empty' => 'The school newsletter begins once teaching starts in April 2027.'],
])

@section('content')

{{-- Opening curtain. Only on a first visit in this tab: on a return visit the
     markup is never rendered, so it cannot flash back over a painted page. --}}
@php($firstVisit = true)
<div data-load-curtain aria-hidden="true">
    <div class="curtain-stars"></div>
    <div class="curtain-copy">
        <div class="curtain-seal">
            <img src="{{ asset('site/assets/seal-l1-ring.svg') }}" alt="" class="seal-ring">
            <img src="{{ asset('site/assets/seal-l2-body.svg') }}" alt="" class="seal-body">
            <img src="{{ asset('site/assets/seal-l3-gold.svg') }}" alt="" class="seal-q" style="--d:620ms">
            <img src="{{ asset('site/assets/seal-l4-sail.svg') }}" alt="" class="seal-q" style="--d:680ms">
            <img src="{{ asset('site/assets/seal-l5-globe.svg') }}" alt="" class="seal-q" style="--d:740ms">
            <img src="{{ asset('site/assets/seal-l6-waves.svg') }}" alt="" class="seal-q" style="--d:800ms">
            <img src="{{ asset('site/assets/seal-l7-stars.svg') }}" alt="" class="seal-q" style="--d:860ms">
            <img src="{{ asset('site/assets/seal-l8-lettering.svg') }}" alt="" class="seal-letter">
        </div>
        <div class="curtain-line">
            <p>DPS Millennium International School</p>
            <div class="curtain-meter"><div></div></div>
        </div>
    </div>
</div>

<header class="hero" id="top">
    <div class="hero-stars" aria-hidden="true"></div>

    <div class="shell hero-inner">
        <div class="hero-grid">
            <div>
                <p class="hero-kicker">Al Wukair · Qatar · CBSE</p>
                <h1 class="hero-title">This is what April 2027 looks like.</h1>
                <p class="hero-lede">Right now it is a building site in Al Wukair. Scroll, and the architect’s study below fills in with the building it becomes.</p>
                <div class="hero-actions">
                    <a class="pill pill-gold" href="#register">Register your interest</a>
                    <a class="pill pill-ghost" href="{{ route('campus') }}">See the campus</a>
                </div>
            </div>
            <div class="hero-crest" role="img" aria-label="DPS Millennium International School crest">
                @foreach (['l1-ring', 'l2-body', 'l3-gold', 'l4-sail', 'l5-globe', 'l6-waves', 'l7-stars', 'l8-lettering'] as $layer)
                    <img src="{{ asset('site/assets/seal-'.$layer.'.svg') }}" alt="">
                @endforeach
            </div>
        </div>
    </div>

    {{-- Architect's study that fills in with the finished building as you scroll.
         The bloom is scrubbed by scroll position, so scrolling back up drains the
         colour out again. home.js draws it; without JS the concept image simply
         sits on top, which is a perfectly good static result. --}}
    <div id="render" class="render">
        <img src="{{ asset('site/assets/campus-sketch.jpeg') }}" fetchpriority="high" decoding="async"
             alt="Architect's pencil study of the DPS Millennium campus" class="render-img">
        <canvas data-bloom width="1594" height="620" aria-hidden="true" class="render-bloom"></canvas>
        <img src="{{ asset('site/assets/campus-concept.jpeg') }}" fetchpriority="high" decoding="async"
             alt="Concept design for the DPS Millennium International School campus in Al Wukair, Qatar"
             class="render-img render-concept" data-chase>
    </div>

    <div class="shell render-caption">
        <p>Architect’s study, then the concept design for the Al&nbsp;Wukair campus, both supplied by the school. The finished building may differ.</p>
        <p class="render-open">Tentative opening 4 April 2027</p>
    </div>

    <div class="shell">
        <div id="facts" class="facts">
            <div>
                <p class="fact-n"><span data-countdown-to="2027-04-04">—</span><span>Days</span></p>
                <p class="fact-c">Until the tentative opening on 4&nbsp;April&nbsp;2027.</p>
            </div>
            <div>
                <p class="fact-n"><span data-count-to="25">25</span><span>Years</span></p>
                <p class="fact-c">DPS Modern Indian School has taught in Doha for twenty-five years.</p>
            </div>
            <div>
                <p class="fact-n"><span data-count-to="2">2</span><span>Schools</span></p>
                <p class="fact-c">Already operating in Qatar under the DPS Group.</p>
            </div>
            <div>
                <p class="fact-n"><span data-count-to="1">1</span><span>Board</span></p>
                <p class="fact-c">CBSE, the same board taught at both sister schools.</p>
            </div>
        </div>
    </div>
</header>

<section id="links" class="dir">
    <div class="dir-grid">
        <a class="dir-card" href="{{ route('fees') }}">
            <h3>Fees structure</h3>
            <p>What a place costs, per year group.</p>
            <span class="dir-arrow">→</span>
        </a>
        <a class="dir-card" href="{{ route('about') }}">
            <h3>About us</h3>
            <p>Mission, vision, and who is building this.</p>
            <span class="dir-arrow">→</span>
        </a>
        <a class="dir-card" href="mailto:{{ config('cms.contact.info') }}">
            <h3>Contact us</h3>
            <p>Email the school office directly.</p>
            <span class="dir-arrow">→</span>
        </a>
        <a class="dir-card is-off" aria-disabled="true">
            <h3>Apply online</h3>
            <span class="dir-soon">Opens later</span>
            <p>Opens once admissions dates are confirmed.</p>
        </a>
    </div>
</section>

<section id="notices" class="notices">
    <div class="shell">
        <h2 class="notices-h">Notice board</h2>
        <div class="notice-grid">
            @foreach ($boxes as $box)
                @php($s = $box['section'])
                @continue($s->value === 'newsletter' && $box['posts']->isEmpty())
                <a class="notice-card" href="{{ route('section', $s->pathSegment()) }}">
                    <span class="notice-kicker">{{ $blurbs[$s->value]['kicker'] }}</span>
                    <h3>{{ $s->label() }}</h3>
                    @if ($box['posts']->isEmpty())
                        <p class="notice-empty">{{ $blurbs[$s->value]['empty'] }}</p>
                    @else
                        <ul class="notice-list">
                            @foreach ($box['posts']->take(3) as $post)
                                <li>
                                    <time datetime="{{ $post->sort_at?->toDateString() }}">{{ $post->sort_at?->format('j M Y') }}</time>
                                    <b>{{ $post->title }}</b>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <span class="notice-more"><span>All {{ strtolower($s->label()) }}</span><span>→</span></span>
                </a>
            @endforeach
        </div>
        <p class="notices-foot"><a href="{{ route('archive') }}">Looking for something older? Search the archive →</a></p>
    </div>
</section>

<section id="calendar" class="keydates">
    <div class="shell keydates-grid">
        <div>
            <p class="kicker kicker-green">Key dates</p>
            <h2 class="h2">The things that are already decided.</h2>
            <p class="lede">A CBSE curriculum, a campus in Al Wukair, and a target opening of April 2027. Everything else we add as it becomes true — not before.</p>
            <ul class="dates">
                <li><b>School opens</b><time datetime="2027-04-04">4 April 2027</time></li>
                <li><b>Admissions dates</b><span>To be confirmed</span></li>
                <li><b>Fee structure published</b><span>To be confirmed</span></li>
                <li><b>Academic year 2027–28</b><span>To be published</span></li>
                @foreach ($upcoming as $entry)
                    <li>
                        <b>{{ $entry->title }}</b>
                        <time datetime="{{ $entry->start_date->toDateString() }}">{{ $entry->start_date->format('j F Y') }}</time>
                    </li>
                @endforeach
            </ul>
        </div>

        @php($prev = $monthStart->subMonth())
        @php($next = $monthStart->addMonth())
        @php($busy = $monthEntries->flatMap(function ($e) {
            $days = [];
            for ($d = $e->start_date; $d->lte($e->end_date ?? $e->start_date); $d = $d->addDay()) { $days[] = $d->day; }
            return $days;
        })->unique()->all())
        <aside class="calcard">
            <div class="calcard-head">
                <h3>School calendar</h3>
                <div class="calcard-nav">
                    <a href="{{ route('home', ['month' => $prev->format('Y-m')]) }}#calendar" aria-label="Previous month">‹</a>
                    <a href="{{ route('home', ['month' => $next->format('Y-m')]) }}#calendar" aria-label="Next month">›</a>
                </div>
            </div>
            <p class="calcard-caption">{{ $monthStart->format('F Y') }}</p>
            <div class="calcard-grid">
                @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $d)<span class="calcard-dow">{{ $d }}</span>@endforeach
                @php($cursor = $monthStart->startOfWeek(\Carbon\CarbonInterface::SUNDAY))
                @php($gridEnd = $next->subDay()->endOfWeek(\Carbon\CarbonInterface::SATURDAY))
                @while ($cursor < $gridEnd)
                    @php($inMonth = $cursor->month === $monthStart->month)
                    <span @class([
                        'calcard-day',
                        'is-out' => ! $inMonth,
                        'is-today' => $cursor->isSameDay($now),
                        'is-busy' => $inMonth && in_array($cursor->day, $busy, true),
                    ])>{{ $cursor->day }}</span>
                    @php($cursor = $cursor->addDay())
                @endwhile
            </div>
            <a class="calcard-foot" href="{{ route('calendar') }}"><i></i> See the full school calendar</a>
        </aside>
    </div>
</section>

<section id="campus" class="progress">
    <div class="progress-waves" aria-hidden="true"></div>
    <div class="shell">
        <p class="kicker kicker-gold">The campus</p>
        <h2 class="h2 on-dark">Watch it go up before your child walks into it.</h2>
        <p class="warnrule">Placeholder schedule — these milestones and statuses are invented to show the layout. Nothing here ships until the school supplies the real construction programme.</p>
        <ol class="milestones">
            <li><time datetime="2026-01">Jan 2026</time><span>Site handover and enabling works</span><span class="ms-state is-done">Complete</span></li>
            <li><time datetime="2026-06">Jun 2026</time><span>Foundations and substructure</span><span class="ms-state is-done">Complete</span></li>
            <li><time datetime="2026-11">Nov 2026</time><span>Superstructure and envelope</span><span class="ms-state">In progress</span></li>
            <li><time datetime="2027-02">Feb 2027</time><span>Fit-out, furniture and testing</span><span class="ms-state">Scheduled</span></li>
            <li><time datetime="2027-04-04">4 Apr 2027</time><span>Doors open</span><span class="ms-state">Tentative</span></li>
        </ol>
        <div class="progress-note">
            <p>The concept design at the top of this page is the architect’s intent. Photographs of the real site go here as it is shot — we’d rather show you nothing than show you someone else’s school.</p>
            <p style="margin-block-start:1rem"><a class="pill pill-ghost" href="{{ route('campus') }}">See construction progress</a></p>
        </div>
    </div>
</section>

<section id="register" class="register">
    <div class="shell register-grid">
        <div>
            <p class="kicker kicker-green">Register interest</p>
            <h2 class="h2">Hear it first.</h2>
            <p class="lede">Construction updates, admissions dates, and the fee structure when it’s confirmed. Roughly once a month, and nothing else.</p>
        </div>
        <div>
            {{-- No form: there is no mail or CRM endpoint from IT yet, and a form that
                 silently goes nowhere is worse than none. This opens the visitor's own
                 mail app with the subject already filled in. --}}
            <a class="pill pill-plum pill-lg"
               href="mailto:{{ config('cms.contact.admissions') }}?subject={{ rawurlencode('Register my interest — DPS Millennium') }}&body={{ rawurlencode("Please add me to the list for updates about DPS Millennium International School.\n\nName:\nChild's year group at opening (if known):\nPhone:\n") }}">
                Email to register your interest
            </a>
            <p class="register-note">Opens your email app. We will reply from {{ config('cms.contact.admissions') }}.</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')<script src="{{ asset('site/home.js') }}" defer></script>@endpush
