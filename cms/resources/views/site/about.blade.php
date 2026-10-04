@extends('site.layout', ['marketing' => true])
@section('title', 'About · DPS Millennium International School')
@section('meta_description', 'A CBSE school being built in Al Wukair by the DPS Group of Institutions — the group’s third in Qatar. What is decided, what is not, and the values it is built on.')
@push('styles')<link rel="stylesheet" href="{{ asset('site/about.css') }}">@endpush

@section('content')
<header class="ahero" id="top">
    <div class="ahero-stars" aria-hidden="true"></div>
    <div class="shell ahero-grid">
        <div>
            <p class="kicker kicker-gold">About the school</p>
            <h1 class="hero-title">Built on twenty-five years in Doha.</h1>
            <p class="hero-lede">A CBSE school being built in Al Wukair by the DPS Group of Institutions — the group’s third in Qatar. This page holds what is decided, what is not, and what the crest on the gate is going to mean.</p>
            <div class="hero-actions">
                <a class="pill pill-gold" href="#crest">Meet the houses</a>
                <a class="pill pill-ghost" href="#leadership">Who runs it</a>
            </div>
        </div>
        <div class="settled">
            <p class="settled-h">What is settled</p>
            <div class="settled-grid">
                <div><span>Board</span><b>CBSE</b><span>The same board taught at both sister schools.</span></div>
                <div><span>Campus</span><b>Al Wukair, Qatar</b><span>Under construction now.</span></div>
                <div><span>Opening</span><b>April 2027</b><span>For the 2027–28 academic year.</span></div>
                <div><span>Group</span><b>DPS Group of Institutions</b><span>Its third school in Qatar.</span></div>
            </div>
            <p class="settled-note">Fees, admissions dates, year groups and staff are not yet confirmed.</p>
        </div>
    </div>
</header>

<section id="purpose" class="purpose">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Mission &amp; vision</p>
        <h2 class="h2">Where values meet vision.</h2>
        <p class="lede">Our motto. Everything we teach — academics, skills and character — is rooted in strong values and aimed at a future-ready vision.</p>
        <div class="two-up" data-anim="fade">
            <div class="panel">
                <h3>Mission</h3>
                <p>To provide exceptional, future-ready education that builds capable individuals who think critically, act ethically, and embrace creativity and innovation — embedding value education into everyday learning to shape confident, compassionate contributors to an evolving global society.</p>
            </div>
            <div class="panel">
                <h3>Vision</h3>
                <p>Empowering future-ready learners through excellence in education, value-driven learning, and a culture of innovation.</p>
            </div>
        </div>
    </div>
</section>

<section id="crest" class="crestband">
    <div class="shell">
        <p class="kicker kicker-gold">Houses</p>
        <h2 class="h2 on-dark">Four houses, one school.</h2>
        <p class="lede on-dark-lede">Every student belongs to one of four houses, each named for a quality worth growing into.</p>

        <div class="crest-grid">
            <div class="crest-stack" data-crest>
                @foreach ([['l1-ring', null], ['l2-body', null], ['l3-gold', 'sail'], ['l4-sail', 'sail'], ['l5-globe', 'globe'], ['l6-waves', 'waves'], ['l7-stars', 'stars'], ['l8-lettering', null]] as [$layer, $q])
                    <img src="{{ asset('site/assets/seal-'.$layer.'.svg') }}" alt="" @if ($q) data-q="{{ $q }}" @endif>
                @endforeach
            </div>
            <ul class="crest-list">
                @foreach ([
                    ['sail', 'Radiance', '#D8A800', 'Yellow — positivity and brightness.'],
                    ['globe', 'Valiance', '#D82818', 'Red — courage and bravery.'],
                    ['waves', 'Brilliance', '#4F7BE0', 'Blue — wisdom and intellect.'],
                    ['stars', 'Elegance', '#3FA463', 'Green — freshness, nature, grace and balance.'],
                ] as [$q, $name, $colour, $text])
                    <li>
                        <button type="button" data-pick="{{ $q }}">
                            <span class="crest-head"><i style="background:{{ $colour }}"></i><b>{{ $name }}</b></span>
                            <span class="crest-text">{{ $text }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

<section id="commitments" class="commit">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Core values</p>
        <h2 class="h2">Six values, lived every day.</h2>
        <ol class="commit-list">
            @foreach ([
                ['Excellence', 'Striving for our best in all we do.'],
                ['Integrity', 'Acting honestly and ethically, always.'],
                ['Innovation', 'Embracing new ideas and creativity.'],
                ['Compassion', 'Leading with empathy and kindness.'],
                ['Critical thinking', 'Questioning, reasoning and reflecting.'],
                ['Responsibility', 'Owning our actions and their impact.'],
            ] as $i => [$title, $text])
                <li data-anim="row">
                    <span class="commit-n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <b>{{ $title }}</b>
                    <span>{{ $text }}</span>
                </li>
            @endforeach
        </ol>

        <div id="objectives" class="objectives">
            <h3>Objectives</h3>
            <ol>
                @foreach ([
                    'Deliver academic excellence through innovative, learner-centred pedagogy.',
                    'Embed value education into everyday learning, not as a separate subject.',
                    'Cultivate critical thinking, creativity and problem-solving skills.',
                    'Prepare every learner to be confident and future-ready for a global world.',
                    'Nurture compassion, integrity and social responsibility in students.',
                    'Build a culture of continuous innovation in teaching and learning.',
                ] as $i => $objective)
                    <li><span>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $objective }}</span></li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

<section id="group" class="group">
    <div class="shell">
        <p class="kicker kicker-gold">The group</p>
        <h2 class="h2 on-dark">The third school, not the first.</h2>
        <p class="lede on-dark-lede">DPS Millennium inherits twenty-five years of teaching in Doha. That is the argument for trusting a school that has not opened: the people behind it have run two already.</p>
        <div class="group-grid">
            <div class="panel panel-dark" data-anim="fade">
                <h3>DPS Modern Indian School</h3>
                <p>Doha. The group’s first school in Qatar, teaching CBSE for twenty-five years.</p>
            </div>
            <div class="panel panel-dark" data-anim="fade">
                <h3>DPS Monarch International</h3>
                <p>Doha. The second school, operating today under the same governance.</p>
            </div>
            <div class="panel panel-dark is-here" data-anim="fade">
                <h3>DPS Millennium</h3>
                <p>Al Wukair. Under construction, opening April 2027. You are on its site.</p>
            </div>
        </div>
        <p class="warnrule">Founding years, enrolment numbers and results are left off until the group confirms them in writing.</p>
    </div>
</section>

<section id="leadership" class="leaders">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Leadership</p>
        <h2 class="h2">No names yet.</h2>
        <p class="lede">A principal has not been announced. Rather than fill this page with portraits and invented biographies, the space stays empty until there are people in the roles — then their names go here, with where they taught before.</p>

        <div class="roles">
            @foreach ([
                ['Not appointed', 'Principal', 'The first thing they say to this school will be printed here.', 'Runs the school day to day and answers for both the results and the way children are treated.'],
                ['Not appointed', 'Vice principal', 'Their words go here once there is someone to quote.', 'Deputises for the principal, usually split between academics and pastoral care.'],
                ['Not appointed', 'Heads of section', 'Their words go here once there is someone to quote.', 'Kindergarten, primary, middle and senior — in practice the person a parent actually deals with.'],
                ['In place', 'Group management', 'Named on confirmation, alongside the two sister schools.', 'The DPS Group of Institutions, Qatar — governance, finance and the campus itself.'],
            ] as $i => [$state, $role, $quote, $text])
                <article class="role" data-anim="fade">
                    <div class="role-portrait">Portrait to follow</div>
                    <p class="role-state">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} · {{ $state }}</p>
                    <b class="role-name">{{ $role }}</b>
                    <p class="role-quote">{{ $quote }}</p>
                    <p class="role-text">{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="faq" class="faq">
    <div class="shell">
        <p class="kicker kicker-gold">Questions</p>
        <h2 class="h2 on-dark">What families ask first.</h2>
        <div class="faq-list">
            @foreach ([
                ['When does the school open?', '4 April 2027 is the target, for the 2027–28 academic year. It depends on construction and on Ministry approvals, so treat it as tentative until the school confirms it.'],
                ['Which curriculum will it follow?', 'CBSE, plus the Arabic and Qatar History requirements set by the Ministry of Education. Subject lists and assessment detail follow once staffing is set.'],
                ['Where exactly is the campus?', 'Al Wukair, south of Doha and near Al Wakrah. A precise address, catchment and bus routes will be published closer to opening.'],
                ['What will fees be, and when can I apply?', 'Neither is set. Registering your interest below puts you on the list that gets fee structures and admissions dates first — there is nothing else to apply for yet.'],
                ['How does this relate to DPS Modern Indian School?', 'Same group, separate school. DPS Millennium is the group’s third institution in Qatar, with its own campus, staff and admissions — a place at one is not a place at another.'],
            ] as [$q, $a])
                {{-- <details> rather than a scripted accordion: it opens with JavaScript
                     off, and screen readers already know what it is. --}}
                <details class="faq-item">
                    <summary><span>{{ $q }}</span><span class="faq-mark" aria-hidden="true"></span></summary>
                    <div class="faq-body"><p>{{ $a }}</p></div>
                </details>
            @endforeach
        </div>
    </div>
</section>

@include('site.partials.register')
@endsection

@push('scripts')<script src="{{ asset('site/about.js') }}" defer></script>@endpush
