@extends('site.layout', ['marketing' => true])
@section('title', 'The Campus · DPS Millennium International School')
@section('meta_description', 'Construction progress for the DPS Millennium campus in Al Wukair, Qatar — verified site photography, the approved design, and planned facilities.')
@push('styles')<link rel="stylesheet" href="{{ asset('site/campus.css') }}">@endpush
@php($img = fn ($f) => asset('site/assets/'.$f))

@section('content')
<header class="chero" id="top">
    <img src="{{ $img('construction/site-07.jpeg') }}" alt="" aria-hidden="true" fetchpriority="high" decoding="async" class="chero-bg">
    <div class="chero-veil" aria-hidden="true"></div>
    <div class="shell chero-inner">
        <p class="kicker kicker-gold">Campus development · Al Wukair, Qatar</p>
        <h1 class="hero-title">The campus takes shape.</h1>
        <p class="hero-lede">A new learning environment is taking form for DPS Millennium. Follow the journey from approved design to verified construction progress.</p>
        <p class="chero-status"><span class="dot" aria-hidden="true"></span>Construction underway · Tentative opening April 2027</p>
        <div class="hero-actions">
            <a class="pill pill-gold" href="#journal">View construction progress <span aria-hidden="true">→</span></a>
            <a class="pill pill-ghost" href="#vision">Explore the campus design</a>
        </div>
        <p class="chero-note">Site record · eight verified photographs</p>
    </div>
</header>

<section id="snapshot" class="band-cream">
    <div class="shell">
        <div class="split-head">
            <div>
                <p class="kicker kicker-green"><span class="tick"></span>The project today</p>
                <h2 class="h2" data-anim="head">A clear record, from site to school.</h2>
            </div>
            <p class="lede">We separate architectural imagery from real site photography and publish construction progress in a format that families can understand at a glance.</p>
        </div>
        <div class="three-up">
            @foreach ([
                ['Current stage', 'Structure rising', 'Foundations are complete and ground-floor columns are cast across the site footprint.'],
                ['Opening target', 'April 2027', 'This is the current tentative opening period. Any formal revision will be published here.'],
                ['Reporting standard', 'Verified on site', 'Every update is based on approved design material or photography taken at the project site.'],
            ] as [$label, $head, $text])
                <article class="panel" data-anim="fade">
                    <p class="micro">{{ $label }}</p>
                    <h3>{{ $head }}</h3>
                    <p>{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="sequence" class="band-dark">
    <div class="shell">
        <div class="split-head">
            <div>
                <p class="kicker kicker-gold">Construction sequence</p>
                <h2 class="h2 on-dark" data-anim="head">A visible journey, one stage at a time.</h2>
            </div>
            <p class="lede on-dark-lede">This page records what has been completed, what is currently visible on site and what families can expect to see in future updates.</p>
        </div>
        <div class="three-up">
            @foreach ([
                ['01 / Complete', 'The groundwork', 'The foundation bases and ground-level structure establish the footprint of the future school.', 'Recorded in the site journal'],
                ['02 / Current', 'The frame emerges', 'Columns, reinforcement and formwork are now giving the campus its first recognisable form.', 'Construction underway'],
                ['03 / Next', 'The story continues', 'New photographs and approved information will be added as the project moves through its next milestones.', 'Updates will appear here'],
            ] as [$step, $head, $text, $foot])
                <article class="panel panel-dark" data-anim="fade">
                    <p class="micro micro-gold">{{ $step }}</p>
                    <h3>{{ $head }}</h3>
                    <p>{{ $text }}</p>
                    <p class="micro">{{ $foot }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="band-cream">
    <div class="shell split-media">
        <div>
            <p class="kicker kicker-green"><span class="tick"></span>A school in progress</p>
            <h2 class="h2" data-anim="head">Every detail starts with a strong foundation.</h2>
            <p class="lede">A campus is more than a building plan. It is the setting for future lessons, friendships, achievements and everyday school life.</p>
            <p class="lede">The photographs on this page are a direct, honest view of the work happening now.</p>
            <p class="tagline"><span class="dot dot-green" aria-hidden="true"></span>Real site photography</p>
            <p class="micro">Architectural images are labelled separately so families can distinguish the planned campus from the current construction site.</p>
        </div>
        <figure class="shot" data-anim="right">
            <img src="{{ $img('construction/site-05.jpeg') }}" alt="Concrete columns and reinforcement across the DPS Millennium construction site" loading="lazy" decoding="async">
        </figure>
    </div>
</section>

<section id="story" class="band-cream" style="padding-block-start:0">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>The school taking shape</p>
        <h2 class="h2" data-anim="head">Built with purpose, in the open.</h2>
        <p class="lede">The campus is being developed as a place for learning, belonging and growth. The current structure is the first visible stage of that long-term commitment.</p>
        <div class="split-media" style="margin-block-start:2.5rem">
            <figure class="shot" data-anim="left">
                <img src="{{ $img('construction/site-07.jpeg') }}" alt="Concrete columns across the DPS Millennium construction site in Al Wukair" loading="lazy" decoding="async">
                <figcaption>Current site view: the ground-floor column grid is now visible across the campus footprint.</figcaption>
            </figure>
            <div class="steps-col">
                @foreach ([
                    ['01 · Foundation', 'The site is established', 'Foundation work and ground beams create the base for the future campus.'],
                    ['02 · Structure', 'The plan becomes visible', 'Columns and formwork begin to define the layout of the school building.'],
                    ['03 · Next phase', 'Progress will be documented', 'New verified photographs will be added as each construction stage is completed.'],
                ] as [$step, $head, $text])
                    <article data-anim="row">
                        <p class="micro">{{ $step }}</p>
                        <h3>{{ $head }}</h3>
                        <p>{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section id="journal" class="band-dark">
    <div class="shell">
        <div class="split-head">
            <div>
                <p class="kicker kicker-gold">Build journal</p>
                <h2 class="h2 on-dark" data-anim="head">Construction, stage by stage.</h2>
            </div>
            <p class="lede on-dark-lede">A visual record of the campus in development. Captions describe only what can be seen in each photograph.</p>
        </div>

        @php($stages = [
            ['site-01.jpeg', 'Stage 01', 'Foundations', 'Foundation bases and ground beams set out.', 'Foundation bases and reinforcement at the DPS Millennium construction site'],
            ['site-03.jpeg', 'Stage 02', 'Reinforcement', 'Steel tied and prepared for the next pour.', 'Steel reinforcement grid prepared for concrete work'],
            ['site-08.jpeg', 'Stage 03', 'Formwork', 'Columns and shutters taking shape.', 'Concrete columns with timber formwork on the construction site'],
            ['site-02.jpeg', 'Stage 04', 'Access scaffold', 'Scaffold installed around the structural frame.', 'Scaffolding and formwork around the first structural columns'],
            ['site-05.jpeg', 'Stage 05', 'Structure rising', 'Vertical structure progressing across the footprint.', 'Concrete columns extending across the construction site'],
        ])

        {{-- Tabs when JavaScript runs; with it off every stage is simply shown in
             order, which is a perfectly good photo journal. --}}
        <div class="stage-tabs" role="tablist" aria-label="Construction stages" data-stage-tabs hidden>
            @foreach ($stages as $i => $s)
                <button type="button" role="tab" id="stage-tab-{{ $i }}" aria-controls="stage-panel-{{ $i }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}" data-stage="{{ $i }}">{{ $s[1] }}</button>
            @endforeach
        </div>

        <div class="stage-panels" data-stage-panels>
            @foreach ($stages as $i => [$file, $label, $title, $caption, $alt])
                <figure id="stage-panel-{{ $i }}" role="tabpanel" aria-labelledby="stage-tab-{{ $i }}" data-stage-panel="{{ $i }}">
                    <img src="{{ $img('construction/'.$file) }}" alt="{{ $alt }}" loading="lazy" decoding="async">
                    <figcaption>
                        <span class="micro micro-gold">{{ $label }}</span>
                        <strong>{{ $title }}</strong>
                        <span>{{ $caption }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <p class="micro" style="margin-block-start:1.2rem">Site photography · unretouched, taken at the project site in Al Wukair.</p>
    </div>
</section>

<section id="vision" class="band-plum">
    <div class="shell">
        <p class="kicker kicker-gold">Campus design</p>
        <h2 class="h2 on-dark" data-anim="head">A vision made visible before the doors open.</h2>
        <p class="lede on-dark-lede">The following images are architect’s materials, shown separately from construction photography. They illustrate the intended front elevation of the campus.</p>
        <div class="two-up">
            <article class="panel panel-dark" data-anim="fade">
                <img src="{{ $img('campus-concept.jpeg') }}" alt="Architect's impression of the proposed DPS Millennium campus front elevation" loading="lazy" decoding="async" class="panel-img">
                <h3>The campus as designed</h3>
                <p>The concept illustration shows the intended character and front elevation of the future school.</p>
                <p class="micro micro-gold">Architect’s impression · indicative only</p>
            </article>
            <article class="panel panel-dark" data-anim="fade">
                <img src="{{ $img('campus-sketch.jpeg') }}" alt="Architectural pencil study of the proposed DPS Millennium campus" loading="lazy" decoding="async" class="panel-img">
                <h3>The study behind it</h3>
                <p>An earlier architectural study of the same elevation and its planning direction.</p>
                <p class="micro micro-gold">Architectural study · indicative only</p>
            </article>
        </div>
    </div>
</section>

<section id="facilities" class="band-cream">
    <div class="shell split-media">
        <div>
            <p class="kicker kicker-green"><span class="tick"></span>Planned facilities</p>
            <h2 class="h2" data-anim="head">Designed around the school day.</h2>
            <p class="lede">The current campus scope supports academic learning, practical discovery, reading, movement and gathering. Final specifications will be published by the school when confirmed.</p>
        </div>
        <div class="fac-list">
            @foreach ([
                ['Teaching blocks', 'Classrooms arranged by phase, from Nursery through Class XII.'],
                ['Science laboratories', 'Physics, chemistry and biology laboratories for senior students.'],
                ['Library', 'A central space for reading, research and independent learning.'],
                ['Sport and assembly', 'Courts, play areas and a covered assembly space.'],
            ] as $i => [$head, $text])
                <article data-anim="row">
                    <span class="fac-n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h3>{{ $head }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="oversight" class="band-dark">
    <div class="shell">
        <p class="kicker kicker-gold">Project oversight</p>
        <h2 class="h2 on-dark" data-anim="head">Reviewed on site, at every stage.</h2>
        <p class="lede on-dark-lede">DPS Group representatives, the design team and the contractor meet through the build to review programme, quality and safety.</p>
        <div class="two-up">
            <figure class="shot" data-anim="fade">
                <img src="{{ $img('construction/site-06.jpeg') }}" alt="Project review meeting in the site office, drawings and programme documents on the table" loading="lazy" decoding="async">
                <figcaption><strong>Progress review</strong> Programme and drawings reviewed in the site office.</figcaption>
            </figure>
            <figure class="shot" data-anim="fade">
                <img src="{{ $img('construction/site-04.jpeg') }}" alt="Representatives together on the DPS Millennium construction site" loading="lazy" decoding="async">
                <figcaption><strong>Site walk</strong> Representatives on site during a structural inspection.</figcaption>
            </figure>
        </div>
    </div>
</section>

<section id="location" class="band-cream">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Location &amp; access</p>
        <h2 class="h2" data-anim="head">Al Wukair, south of Doha.</h2>
        <p class="lede">The campus is in Al Wukair, near Al Wakrah. A precise address, the catchment and bus routes will be published here closer to opening — we would rather post nothing than a pin in the wrong place.</p>
        <p class="btn-row" style="margin-block-start:1.6rem">
            <a class="pill pill-plum" href="mailto:{{ config('cms.contact.info') }}?subject={{ rawurlencode('Campus location enquiry — DPS Millennium') }}">Ask the school office</a>
        </p>
    </div>
</section>

<section id="follow" class="band-plum">
    <div class="shell register-grid">
        <div>
            <p class="kicker kicker-gold">Stay informed</p>
            <h2 class="h2 on-dark">Follow the build to opening.</h2>
            <p class="lede on-dark-lede">Register your interest to receive admissions news and future campus updates from DPS Millennium International School.</p>
        </div>
        <div class="hero-actions">
            <a class="pill pill-gold" href="{{ route('home') }}#register">Register your interest</a>
            <a class="pill pill-ghost" href="{{ route('fees') }}">See the fees</a>
        </div>
    </div>
</section>
@endsection

@push('scripts')<script src="{{ asset('site/campus.js') }}" defer></script>@endpush
