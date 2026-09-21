{{--
    One nav for every public page. The prototypes each carried their own copy with
    relative links between .dc.html files, several of which pointed at the wrong
    folder; building it once on real routes means those cannot come back.

    Items marked "soon" have no page yet and are deliberately not links.
--}}
@php($isHome = request()->routeIs('home'))
@php($home = fn (string $fragment = '') => $isHome && $fragment !== '' ? '#'.$fragment : route('home').($fragment ? '#'.$fragment : ''))
<div class="nav" data-site-nav>
    <div class="shell nav-inner">
        <a class="nav-brand" href="{{ $home() }}">
            <img src="{{ asset('site/assets/shield.svg') }}" alt="">
            <span>
                <b>DPS Millennium</b>
                <span>International School</span>
            </span>
        </a>

        <button type="button" class="nav-burger" data-burger aria-expanded="false" aria-controls="site-menu">Menu</button>

        <ul class="nav-menu" id="site-menu" data-nav-menu>
            <li><a href="{{ $home() }}" @if ($isHome) aria-current="page" @endif>Home</a></li>

            <li data-menu="about">
                <a href="{{ route('about') }}" @if (request()->routeIs('about')) aria-current="page" @endif>About Us<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    <li><a href="{{ route('about') }}#purpose">Mission &amp; Vision</a></li>
                    <li><a href="{{ route('about') }}#objectives">Objectives</a></li>
                    <li><a href="{{ route('about') }}#leadership">Key Visionaries</a></li>
                    <li><a href="{{ route('about') }}#group">The DPS Group</a></li>
                    <li><a href="{{ route('about') }}#crest">The Crest</a></li>
                </ul>
            </li>

            <li data-menu="campus">
                <a href="{{ route('campus') }}" @if (request()->routeIs('campus')) aria-current="page" @endif>The Campus<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    <li><a href="{{ $home('render') }}">Concept Design</a></li>
                    <li><a href="{{ route('campus') }}#progress">Construction Progress</a></li>
                    <li><a href="{{ route('campus') }}#facilities">Facilities</a></li>
                    <li><a href="{{ route('campus') }}#location">Location &amp; Access</a></li>
                </ul>
            </li>

            <li data-menu="admissions">
                <a href="{{ $home('register') }}">Admissions<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    <li><a href="{{ $home('register') }}">Register Interest</a></li>
                    <li><a href="{{ route('fees') }}">Fees Structure</a></li>
                    <li><a class="is-soon" aria-disabled="true">How to Apply — soon</a></li>
                    <li><a class="is-soon" aria-disabled="true">Age &amp; Year Groups — soon</a></li>
                </ul>
            </li>

            <li data-menu="notices">
                <a href="{{ $home('notices') }}">Notice Board<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    @foreach (\App\Content\Section::cases() as $s)
                        <li><a href="{{ route('section', $s->pathSegment()) }}">{{ $s->label() }}</a></li>
                    @endforeach
                    <li><a href="{{ route('calendar') }}">School Calendar</a></li>
                    <li><a href="{{ route('archive') }}">Archive</a></li>
                </ul>
            </li>

            <li data-menu="partnerships">
                <a href="{{ route('partnerships') }}" @if (request()->routeIs('partnerships')) aria-current="page" @endif>Partnerships<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    <li><a href="{{ route('partnerships') }}#opportunities">Opportunities</a></li>
                    <li><a href="{{ route('partnerships') }}#enquiry">Make an Enquiry</a></li>
                </ul>
            </li>

            <li data-menu="contact">
                <a href="mailto:{{ config('cms.contact.info') }}">Contact<i class="nav-caret"></i></a>
                <ul class="nav-sub">
                    <li><a href="mailto:{{ config('cms.contact.info') }}">Email the school</a></li>
                    <li><a href="mailto:{{ config('cms.contact.admissions') }}">Email admissions</a></li>
                    <li><a href="{{ route('campus') }}#location">Address &amp; Directions</a></li>
                </ul>
            </li>
        </ul>

        <div style="display:flex;gap:.6rem;align-items:center">
            <a class="nav-cta" href="{{ $home('register') }}">Register</a>
        </div>
    </div>
    <div class="nav-progress" data-progress aria-hidden="true"></div>
</div>
