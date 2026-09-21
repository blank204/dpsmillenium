{{--
    Shared "register your interest" call to action.

    Deliberately not a form: there is no mail or CRM endpoint from IT yet, and a
    form that silently goes nowhere is worse than no form at all. This opens the
    visitor's own mail app with the subject and a short template already filled in.
--}}
<section id="register" class="register">
    <div class="shell register-grid">
        <div>
            <p class="kicker kicker-green">Register interest</p>
            <h2 class="h2">Hear it first.</h2>
            <p class="lede">Construction updates, admissions dates, and the fee structure when it’s confirmed. Roughly once a month, and nothing else.</p>
        </div>
        <div>
            <a class="pill pill-plum pill-lg"
               href="mailto:{{ config('cms.contact.admissions') }}?subject={{ rawurlencode('Register my interest — DPS Millennium') }}&body={{ rawurlencode("Please add me to the list for updates about DPS Millennium International School.\n\nName:\nChild's year group at opening (if known):\nPhone:\n") }}">
                Email to register your interest
            </a>
            <p class="register-note">Opens your email app. We will reply from {{ config('cms.contact.admissions') }}.</p>
        </div>
    </div>
</section>
