{{-- Always blank. Never add value= or old(). Spec: name starts blank on every change. --}}
@php($compact = $compact ?? false)
<label class="field" data-region="actor-name">
    <span>Your name</span>
    <input type="text" name="actor_name" autocomplete="off" required maxlength="{{ config('cms.limits.actor_name') }}" spellcheck="false">
    @unless ($compact)<span class="hint">This is recorded in the change log.</span>@endunless
</label>
@error('actor_name')<p class="error" role="alert">{{ $message }}</p>@enderror
