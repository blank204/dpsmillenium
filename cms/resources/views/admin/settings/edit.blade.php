@extends('admin.layout')
@section('title', 'Settings')
@section('content')
<div data-screen="settings">
    <h1>Settings</h1>
    @include('admin.partials.errors')
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')

        <fieldset data-region="homepage-counts"><legend>Homepage: how many posts each box shows</legend>
            @foreach (\App\Content\Section::cases() as $section)
                <label>{{ $section->label() }} <input type="number" min="1" max="20" name="homepage_count[{{ $section->value }}]" value="{{ old('homepage_count.'.$section->value, $s['homepage_count'][$section->value]) }}"></label>
            @endforeach
        </fieldset>

        <fieldset data-region="academic-year"><legend>Academic year starts in</legend>
            <select name="academic_year_start_month">@foreach (range(1, 12) as $m)<option value="{{ $m }}" @selected((int) old('academic_year_start_month', $s['academic_year_start_month']) === $m)>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>@endforeach</select>
        </fieldset>

        @foreach (['grades' => ['Grades', 'label', true], 'calendar_types' => ['Calendar types', 'name', false], 'newsletter_series' => ['Newsletters', 'name', false]] as $key => [$legend, $field, $hasPosition])
            <fieldset data-region="{{ $key }}"><legend>{{ $legend }}</legend>
                <p>Rename or hide entries. Entries cannot be deleted because old posts may use them.</p>
                <table data-list="{{ $key }}">
                    <thead><tr><th>Name</th>@if ($hasPosition)<th>Order</th>@endif<th>Hidden</th></tr></thead>
                    <tbody>
                    @foreach ($s[$key] as $i => $row)
                        <tr>
                            <td><input type="hidden" name="{{ $key }}[{{ $i }}][id]" value="{{ $row['id'] }}"><input name="{{ $key }}[{{ $i }}][{{ $field }}]" value="{{ $row[$field] }}" required></td>
                            @if ($hasPosition)<td><input type="number" name="{{ $key }}[{{ $i }}][position]" value="{{ $row['position'] }}" min="0"></td>@endif
                            <td><input type="hidden" name="{{ $key }}[{{ $i }}][hidden]" value="0"><input type="checkbox" name="{{ $key }}[{{ $i }}][hidden]" value="1" @checked($row['hidden'])></td>
                        </tr>
                    @endforeach
                    @php($n = count($s[$key]))
                    <tr data-state="new-row">
                        <td><input name="{{ $key }}[{{ $n }}][{{ $field }}]" placeholder="Add new…" data-optional-new-row></td>
                        @if ($hasPosition)<td><input type="number" name="{{ $key }}[{{ $n }}][position]" value="{{ ($s[$key][$n - 1]['position'] ?? 0) + 10 }}" min="0"></td>@endif
                        <td><input type="hidden" name="{{ $key }}[{{ $n }}][hidden]" value="0"></td>
                    </tr>
                    </tbody>
                </table>
            </fieldset>
        @endforeach

        @include('admin.partials.actor-name')
        <button>Save settings</button>
    </form>
</div>
<script>
  /* Drop the empty "Add new" rows before submit so they are not validated as required. */
  document.querySelector('[data-screen="settings"] form').addEventListener('submit', function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-optional-new-row]'), function (input) {
      if (input.value.trim() === '') {
        Array.prototype.forEach.call(input.closest('tr').querySelectorAll('input'), function (i) { i.disabled = true; });
      }
    });
  });
</script>
@endsection
