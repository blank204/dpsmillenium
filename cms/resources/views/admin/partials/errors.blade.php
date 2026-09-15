@if ($errors->any())
    <div data-region="form-errors" role="alert" class="error">
        <p>Please fix the following:</p>
        <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif
