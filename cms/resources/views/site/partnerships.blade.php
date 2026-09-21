@extends('site.layout', ['marketing' => true])
@section('title', 'Partnerships & Sponsorships · DPS Millennium International School')
@section('meta_description', 'Partner with DPS Millennium before it opens in April 2027 — educational, technology, community and student development partnerships, and sponsorship of school life.')
@push('styles')<link rel="stylesheet" href="{{ asset('site/partnerships.css') }}">@endpush

@php($areas = [
    ['Educational partnerships', 'Workshops, expertise, mentoring, enrichment.',
     'Bring specialist knowledge into the school day — as a visiting expert, a recurring workshop, or a mentoring relationship that follows a cohort through the year.',
     'A visiting engineer runs four sessions with Classes IX–X on structural design, using the campus build itself as the case study.',
     'Curriculum-aligned scheduling, teacher coordination, safeguarding and a defined student group.',
     'A specialist who can commit to a set number of sessions across a term.'],
    ['Technology & innovation', 'Digital learning and practical projects.',
     'Help the school use technology well from the day it opens — the tools, the teaching around them, and the judgement students need to use them responsibly.',
     'A cloud provider equips a lab and trains staff; students present what they built at the first annual showcase.',
     'A new-build environment with no legacy systems, and staff willing to pilot and give structured feedback.',
     'Software, hardware or expertise, plus a realistic view of training and support.'],
    ['Community partnerships', 'Initiatives connecting the school with Qatar.',
     'Connect the school to the neighbourhood it is opening into — Al Wukair families, local organisations, and causes students can genuinely contribute to.',
     'A sustainability group co-runs a planting programme on the new campus grounds with Middle School students.',
     'Student volunteers, parent networks, campus space once open, and communication reach.',
     'A programme or cause with room for school-age participation.'],
    ['Student development', 'Competitions, placements, careers exposure.',
     'Create the experiences that sit outside a syllabus — the competition, the placement, the conversation with someone already doing the job they are considering.',
     'A firm hosts a careers morning for Classes XI–XII, followed by a small structured work-shadow programme.',
     'Student cohorts, staff supervision, and academic recognition where appropriate.',
     'Opportunities with defined outcomes, and mentors willing to give real feedback.'],
])
@php($sponsorships = [
    ['Events & competitions', 'Inter-house events, academic competitions, annual day.'],
    ['Technology & equipment', 'Laboratory, library and classroom equipment.'],
    ['Sports & student activities', 'Kit, courts, coaching and inter-school fixtures.'],
    ['Scholarships & student support', 'Named support for students who need it.'],
    ['Campus & learning initiatives', 'Reading spaces, gardens and maker areas.'],
])

@section('content')
<header class="phero" id="top">
    <div class="phero-stars" aria-hidden="true"></div>
    <div class="shell phero-grid">
        <div>
            <p class="kicker kicker-gold">Partnerships &amp; Sponsorships</p>
            <h1 class="hero-title">Build what comes next.</h1>
            <p class="hero-lede">A school opening in 2027 is a rare thing to help shape. Partner with DPS Millennium early and your contribution becomes part of how the place works — not an addition to it later.</p>
            <div class="hero-actions">
                <a class="pill pill-gold" href="#partnerships">Explore the areas <span aria-hidden="true">→</span></a>
                <a class="pill pill-ghost" href="#contact">Start a conversation</a>
            </div>
        </div>
        <div class="pseal" role="img" aria-label="DPS Millennium seal">
            <img src="{{ asset('site/assets/seal.svg') }}" alt="">
            <span class="pseal-tag">Opening April 2027</span>
        </div>
    </div>
</header>

<section id="why-partner" class="band-cream">
    <div class="shell why-grid">
        <div class="why-label">
            <span>01</span>
            <span class="tick" aria-hidden="true"></span>
            <h2>Why partner</h2>
        </div>
        <div>
            <p class="kicker kicker-green">Building something meaningful</p>
            <h2 class="h2" data-anim="head">A school is built by <span class="tint">more than bricks.</span></h2>
            <div class="why-body">
                <p>DPS Millennium International School is being established in Al Wukair with a long-term focus on <strong>learning, opportunity and community.</strong></p>
                <p>The right partnerships can extend that mission beyond the classroom — connecting students with people, organisations, expertise and experiences that make education more relevant.</p>
            </div>
            <p class="why-note">We are interested in relationships that create genuine value for <strong>students, the school and the wider community</strong> — not simply logo placement.</p>
        </div>
    </div>
</section>

<section id="partnerships" class="band-plum">
    <div class="shell">
        <h2 class="h2 on-dark" data-anim="head">Four ways to work together.</h2>
        <div class="areas">
            @foreach ($areas as [$name, $sub, $lead, $example, $brings, $looks])
                <article class="area" data-anim="fade">
                    <h3>{{ $name }}</h3>
                    <p class="area-sub">{{ $sub }}</p>
                    <p class="area-lead">{{ $lead }}</p>
                    <p class="area-eg"><span>For example</span>{{ $example }}</p>
                    <div class="area-split">
                        <div><p class="micro-gold">The school brings</p><p>{{ $brings }}</p></div>
                        <div><p class="micro-gold">We would look for</p><p>{{ $looks }}</p></div>
                    </div>
                    <button type="button" class="pickbtn" data-pick="{{ $name }}" aria-pressed="false">
                        <span class="pickbtn-mark" aria-hidden="true">+</span><span>Add to my enquiry</span>
                    </button>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="sponsorships" class="band-cream">
    <div class="shell spon-grid">
        <div>
            <p class="kicker kicker-green"><span class="tick"></span>Sponsorships</p>
            <h2 class="h2" data-anim="head">Put support where it matters.</h2>
            <p class="lede">Packages are built around an organisation’s interests and the school’s needs rather than fixed tiers. Tell us which area interests you and we will come back with what it would take.</p>
            <p class="lede">Recognition is agreed in writing before anything is announced, and a sponsor’s name is never attached to student work without permission.</p>
        </div>
        <ul class="sponlist">
            @foreach ($sponsorships as [$name, $sub])
                <li>
                    <button type="button" class="sponbtn" data-pick="{{ $name }}" aria-pressed="false">
                        <span><span class="sponbtn-name">{{ $name }}</span><span class="sponbtn-sub">{{ $sub }}</span></span>
                        <i class="pickbtn-mark" aria-hidden="true">+</i>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<section id="contact" class="band-dark">
    <div class="shell contact-grid">
        <div>
            <p class="kicker kicker-gold">Get in touch</p>
            <h2 class="h2 on-dark" data-anim="head">Start a conversation.</h2>
            <p class="lede on-dark-lede">A short note is enough — no proposal document at this stage. Anything you added while reading is listed below and travels with it.</p>

            <div class="basket">
                <p class="basket-h">Your enquiry <span data-pick-count>— nothing selected yet</span></p>
                <ul class="basket-list" data-pick-list></ul>
            </div>

            <ol class="nextsteps">
                <li><span>1</span>You send the note.</li>
                <li><span>2</span>We talk it through — scope, reach, and whether the timing works against the opening schedule.</li>
                <li><span>3</span>We put it in writing. Nothing is committed, or announced, before both sides have seen it.</li>
            </ol>
        </div>

        <div class="enquiry">
            {{-- No posted form: there is no mail or CRM endpoint from IT yet. The
                 button opens the visitor's own mail app with the areas they ticked
                 while reading already written into the message. --}}
            <p class="enquiry-h">Send your note</p>
            <p class="enquiry-copy">Tell us who you are and what you would like to explore. Anything you added above is included automatically.</p>
            <a class="pill pill-gold pill-lg" data-enquiry-link
               href="mailto:{{ config('cms.contact.info') }}?subject={{ rawurlencode('Partnership enquiry — DPS Millennium') }}">
                Email your enquiry <span aria-hidden="true">→</span>
            </a>
            <p class="enquiry-note">Opens your email app, addressed to {{ config('cms.contact.info') }}. Nothing is sent from this page and nothing is stored here.</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')<script src="{{ asset('site/partnerships.js') }}" defer></script>@endpush
