@extends('site.layout', ['marketing' => true])
@section('title', 'Fees · DPS Millennium International School')
@section('meta_description', 'Indicative fee structure for DPS Millennium International School — one-time admission fees, annual tuition in three installments, and transport by zone.')
@push('styles')<link rel="stylesheet" href="{{ asset('site/fees.css') }}">@endpush

@php($bands = ['Nursery & Prep', 'Classes I–IV', 'Classes V–VIII', 'Classes IX & X', 'Classes XI & XII'])
@php($oneTime = [
    ['Registration fee', 'Non-refundable', [100, 100, 100, 100, 100]],
    ['Admission test fee', 'Non-refundable', [null, 108, 108, 108, 108]],
    ['Seat reservation fee', 'Credited against Installment 1', [425, 425, 425, 425, 425]],
])
@php($oneTimeTotal = [525, 633, 633, 633, 633])
@php($instalment = [2000, 2891, 3118, 3800, 4254])
@php($tuitionTotal = [6000, 8673, 9354, 11400, 12762])
@php($instalmentWhen = [
    'Payable April · on or before 15 May',
    'Payable August · on or before 15 September',
    'Payable December · on or before 15 January',
])
@php($transport = ['in' => ['label' => 'Inside city', 'terms' => [1400, 1300, 1300], 'total' => 4000],
                   'out' => ['label' => 'Outside city', 'terms' => [1500, 1500, 1500], 'total' => 4500]])

@section('content')
<header class="fhero" id="top">
    <div class="fhero-stars" aria-hidden="true"></div>
    <div class="shell fhero-grid">
        <div>
            <p class="kicker kicker-gold">Fees structure</p>
            <h1 class="hero-title">What a place is likely to cost.</h1>
            <p class="hero-lede">Pick a year group and see the first-year total: the one-time fees paid at admission, the annual tuition split across three installments, and transport if you need it.</p>
            <div class="hero-actions">
                <a class="pill pill-gold" href="#fees">See the full table</a>
                <a class="pill pill-ghost" href="#notes">What’s not settled</a>
            </div>
        </div>

        <div class="calc" data-calc>
            <p class="calc-h">Year group</p>
            <div class="calc-opts" data-calc-bands>
                @foreach ($bands as $i => $band)
                    <button type="button" data-band="{{ $i }}" aria-pressed="{{ $i === 1 ? 'true' : 'false' }}">{{ $band }}</button>
                @endforeach
            </div>
            <p class="calc-h">School transport</p>
            <div class="calc-opts" data-calc-transport>
                <button type="button" data-transport="none" aria-pressed="true">No transport</button>
                <button type="button" data-transport="in" aria-pressed="false">Inside city</button>
                <button type="button" data-transport="out" aria-pressed="false">Outside city</button>
            </div>

            <div class="calc-out">
                <p class="calc-label">First year, all in</p>
                <p class="calc-total"><span data-out="total">—</span><span class="calc-cur">QR</span></p>
                <ul class="calc-lines">
                    <li>One-time, at admission<b data-out="oneTime">—</b></li>
                    <li>Annual tuition<b data-out="tuition">—</b></li>
                    <li>Transport<b data-out="transport">—</b></li>
                    <li>Each tuition installment<b data-out="perInstalment">—</b></li>
                </ul>
                <p class="calc-note">Indicative. These are DPS Modern Indian School’s current rates, standing in until Millennium’s own structure is approved.</p>
            </div>
        </div>
    </div>

    {{-- The calculator needs the same numbers the tables below print, so they are
         published once here rather than duplicated into the script.

         Built into a variable first: Blade's @json() splits its argument on every
         literal comma, so an array literal written inline here is read as several
         arguments and fails to compile (the same trap documented in the post
         editor's blocks_json block). --}}
    @php($feeData = [
        'bands' => $bands,
        'oneTime' => $oneTimeTotal,
        'instalment' => $instalment,
        'tuition' => $tuitionTotal,
        'transport' => ['none' => 0, 'in' => $transport['in']['total'], 'out' => $transport['out']['total']],
    ])
    <script type="application/json" id="fee-data">@json($feeData)</script>
</header>

<div class="notconfirmed">
    <div class="shell">
        <b>These fees are not confirmed.</b>
        <p>DPS Millennium’s own fee structure has not yet been approved by the Ministry of Education. Every figure on this page is DPS Modern Indian School’s current rate, shown so families have an idea of the order of cost. Do not budget from it.</p>
    </div>
</div>

<section id="fees" class="band-cream">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Nature of fee</p>
        <h2 class="h2" data-anim="head">The whole structure, one table.</h2>
        <p class="lede">One-time fees are paid at admission; the seat reservation fee is deducted from the first installment. Annual tuition is split into three equal installments. All figures in Qatari Riyals.</p>

        <div class="tablewrap">
            <table class="feetable">
                <thead>
                    <tr>
                        <th scope="col">Nature of fee</th>
                        @foreach ($bands as $band)<th scope="col">{{ $band }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="grouprow"><th colspan="6" scope="colgroup">One-time, at admission</th></tr>
                    @foreach ($oneTime as $i => [$name, $note, $values])
                        <tr @class(['is-alt' => $i % 2 === 1])>
                            <th scope="row">{{ $name }}<span>{{ $note }}</span></th>
                            @foreach ($values as $v)<td>{{ $v === null ? '—' : number_format($v) }}</td>@endforeach
                        </tr>
                    @endforeach
                    <tr class="is-total">
                        <th scope="row">Total one-time fee</th>
                        @foreach ($oneTimeTotal as $v)<td>{{ number_format($v) }}</td>@endforeach
                    </tr>

                    <tr class="grouprow"><th colspan="6" scope="colgroup">Annual tuition · three installments</th></tr>
                    @foreach ($instalmentWhen as $i => $when)
                        <tr @class(['is-alt' => $i % 2 === 1])>
                            <th scope="row">Installment {{ $i + 1 }}<span>{{ $when }}</span></th>
                            @foreach ($instalment as $v)<td>{{ number_format($v) }}</td>@endforeach
                        </tr>
                    @endforeach
                    <tr class="is-total">
                        <th scope="row">Total tuition fee<span>Academic year</span></th>
                        @foreach ($tuitionTotal as $v)<td>{{ number_format($v) }}</td>@endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="micro">On a narrow screen the table scrolls sideways — the fee names stay pinned to the left edge.</p>
    </div>
</section>

<section id="installments" class="band-plum">
    <div class="shell">
        <p class="kicker kicker-gold">Payment schedule</p>
        <h2 class="h2 on-dark" data-anim="head">Three payments, one year.</h2>
        <p class="lede on-dark-lede">Pick a year group to see what each installment comes to. Tuition for a term is payable even if a child is absent for it.</p>

        <label class="bandpick">
            <span>Year group</span>
            <select data-band-select>
                @foreach ($bands as $i => $band)<option value="{{ $i }}" @selected($i === 1)>{{ $band }}</option>@endforeach
            </select>
        </label>

        <div class="steps">
            @foreach ($instalmentWhen as $i => $when)
                <div class="step-card">
                    <span class="step-n">{{ $i + 1 }}</span>
                    <b>Installment {{ $i + 1 }}</b>
                    <span class="step-when">{{ $when }}</span>
                    <span class="step-amt" data-step-amount>QAR {{ number_format($instalment[1]) }}</span>
                </div>
                @if (! $loop->last)<div class="step-arrow" aria-hidden="true">→</div>@endif
            @endforeach
        </div>

        <p class="steps-total">
            <span>Total tuition · <span data-band-name>{{ $bands[1] }}</span></span>
            <span data-band-total>QAR {{ number_format($tuitionTotal[1]) }}</span>
        </p>
    </div>
</section>

<section id="transport" class="band-cream">
    <div class="shell">
        <p class="kicker kicker-green"><span class="tick"></span>Bus service</p>
        <h2 class="h2" data-anim="head">Transport, by zone.</h2>
        <p class="lede">Charged by term. Fees are not adjusted mid-term if a family changes zone, and a term already begun is not refunded.</p>

        <div class="tablewrap">
            <table class="feetable">
                <thead>
                    <tr>
                        <th scope="col">Nature of fee</th>
                        @foreach ($transport as $zone)<th scope="col">{{ $zone['label'] }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($instalmentWhen as $i => $when)
                        <tr @class(['is-alt' => $i % 2 === 1])>
                            <th scope="row">Installment {{ $i + 1 }}<span>{{ $when }}</span></th>
                            @foreach ($transport as $zone)<td>{{ number_format($zone['terms'][$i]) }}</td>@endforeach
                        </tr>
                    @endforeach
                    <tr class="is-total">
                        <th scope="row">Total transport fee</th>
                        @foreach ($transport as $zone)<td>{{ number_format($zone['total']) }}</td>@endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="micro">Families in the Mesaieed area should contact the accounts department directly.</p>
    </div>
</section>

<section id="notes" class="band-dark">
    <div class="shell notes">
        <p class="lede on-dark-lede">Figures shown are DPS Modern Indian School’s current rates, standing in until DPS Millennium’s own structure is approved by the Ministry. Payment methods, late-fee terms and withdrawal rules will be published alongside the confirmed fees.</p>
        <p class="hero-actions">
            <a class="pill pill-gold" href="{{ route('home') }}#register">Get the confirmed fees first</a>
        </p>
    </div>
</section>
@endsection

@push('scripts')<script src="{{ asset('site/fees.js') }}" defer></script>@endpush
