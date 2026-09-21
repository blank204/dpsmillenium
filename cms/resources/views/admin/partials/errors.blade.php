@if ($errors->any())
    <div data-region="form-errors" role="alert" class="notice notice-error">
        <b>{{ $errors->count() === 1 ? 'One thing needs fixing before this can be saved' : $errors->count().' things need fixing before this can be saved' }}</b>
        <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif
