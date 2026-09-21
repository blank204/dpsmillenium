@php($isHome = request()->routeIs('home'))
@php($home = fn (string $fragment = '') => $isHome && $fragment !== '' ? '#'.$fragment : route('home').($fragment ? '#'.$fragment : ''))
<footer class="foot">
    <div class="shell">
        <div class="foot-top">
            <div class="foot-seal">
                <img src="{{ asset('site/assets/seal.svg') }}" alt="">
                <p class="foot-arabic" lang="ar" dir="rtl">مدرسة ميلينيوم الدولية</p>
                <div class="foot-quick">
                    <a href="{{ $home('register') }}">Register</a>
                    <a href="{{ route('fees') }}">Fees</a>
                    <a href="mailto:{{ config('cms.contact.info') }}">Contact</a>
                </div>
            </div>

            <div class="foot-cols">
                <div>
                    <h4>About</h4>
                    <ul>
                        <li><a href="{{ route('about') }}#purpose">Mission &amp; Vision</a></li>
                        <li><a href="{{ route('about') }}#crest">The crest</a></li>
                        <li><a href="{{ route('about') }}#leadership">Leadership</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Admissions</h4>
                    <ul>
                        <li><a href="{{ $home('register') }}">Register Interest</a></li>
                        <li><a href="{{ route('fees') }}">Fees Structure</a></li>
                        <li><a href="{{ route('campus') }}">The Campus</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Notices</h4>
                    <ul>
                        @foreach (\App\Content\Section::cases() as $s)
                            <li><a href="{{ route('section', $s->pathSegment()) }}">{{ $s->label() }}</a></li>
                        @endforeach
                        <li><a href="{{ route('archive') }}">Archive</a></li>
                    </ul>
                </div>
                <div>
                    <h4>DPS Group</h4>
                    <ul>
                        <li><a href="https://www.dpsmisdoha.com" target="_blank" rel="noopener">DPS Modern Indian School</a></li>
                        <li><a href="{{ route('partnerships') }}">Partnerships</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="foot-base">
            <p>DPS Millennium International School · part of the DPS Group of Institutions</p>
            <p>Tentative opening <time datetime="2027-04-04">4 April 2027</time> · Arabic site to follow</p>
        </div>
    </div>
</footer>
