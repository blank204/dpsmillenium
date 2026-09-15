{{-- Always blank. Never add value= or old(). Spec: name starts blank on every change. --}}
<label data-region="actor-name">Your name (required, saved in the change log)
    <input type="text" name="actor_name" autocomplete="off" required maxlength="80" spellcheck="false">
</label>
@error('actor_name')<p class="error" role="alert">{{ $message }}</p>@enderror
