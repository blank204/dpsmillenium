@extends('admin.layout')
@section('title', 'Settings')
@section('content')
<div data-screen="settings">
    <h1 class="h1">Settings</h1>
    <p class="lede">Lists that editors choose from, and how many posts each homepage box shows.</p>

    @include('admin.partials.errors')

    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')

        <div class="cards-2">
            <section class="card" data-region="homepage-counts">
                <h2 class="eyebrow">Homepage counts</h2>
                <p class="meta mt">How many posts each box shows on the public homepage. 1 to 20.</p>
                <div class="grid-2 mt">
                    @foreach (\App\Content\Section::cases() as $section)
                        <label class="field" style="margin-top:0">
                            <span>{{ $section->label() }}</span>
                            <input type="number" min="1" max="20" name="homepage_count[{{ $section->value }}]" value="{{ old('homepage_count.'.$section->value, $s['homepage_count'][$section->value]) }}">
                        </label>
                    @endforeach
                </div>

                <div class="divider"></div>
                <h2 class="eyebrow" data-region="academic-year">Academic year</h2>
                <label class="field">
                    <span>The school year starts in</span>
                    <select name="academic_year_start_month">@foreach (range(1, 12) as $m)<option value="{{ $m }}" @selected((int) old('academic_year_start_month', $s['academic_year_start_month']) === $m)>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>@endforeach</select>
                    <span class="hint">Used to group the archive into school years.</span>
                </label>
            </section>

            @foreach (['grades' => ['Grades', 'label', true], 'calendar_types' => ['Calendar types', 'name', false], 'newsletter_series' => ['Newsletter series', 'name', false]] as $key => [$legend, $field, $hasPosition])
                <section class="card" data-region="{{ $key }}">
                    <h2 class="eyebrow">{{ $legend }}</h2>
                    <p class="meta mt">Rename or hide entries. Hiding keeps old posts intact; it just stops the entry appearing in new ones. Entries cannot be deleted because old posts may use them.</p>
                    <div data-list="{{ $key }}" class="mt">
                        @foreach ($s[$key] as $i => $row)
                            <div class="setting-row">
                                <input type="hidden" name="{{ $key }}[{{ $i }}][id]" value="{{ $row['id'] }}">
                                <input name="{{ $key }}[{{ $i }}][{{ $field }}]" value="{{ $row[$field] }}" required aria-label="Name">
                                @if ($hasPosition)
                                    <input type="number" name="{{ $key }}[{{ $i }}][position]" value="{{ $row['position'] }}" min="0" aria-label="Sort order">
                                @endif
                                <label class="check">
                                    <input type="hidden" name="{{ $key }}[{{ $i }}][hidden]" value="0">
                                    <input type="checkbox" name="{{ $key }}[{{ $i }}][hidden]" value="1" @checked($row['hidden'])> Hide
                                </label>
                            </div>
                        @endforeach
                        @php($n = count($s[$key]))
                        <div class="setting-row add-row" data-state="new-row">
                            <input name="{{ $key }}[{{ $n }}][{{ $field }}]" placeholder="Add a new one…" data-optional-new-row aria-label="Add">
                            @if ($hasPosition)
                                <input type="number" name="{{ $key }}[{{ $n }}][position]" value="{{ ($s[$key][$n - 1]['position'] ?? 0) + 10 }}" min="0" aria-label="Sort order">
                            @endif
                            <input type="hidden" name="{{ $key }}[{{ $n }}][hidden]" value="0">
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <section class="card mt-lg" style="max-width:26rem">
            @include('admin.partials.actor-name')
            <p class="btn-row mt"><button class="btn btn-primary">Save settings</button></p>
        </section>
    </form>
</div>
<script>
  /* Drop the empty "Add new" rows before submit so they are not validated as required. */
  document.querySelector('[data-screen="settings"] form').addEventListener('submit', function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-optional-new-row]'), function (input) {
      if (input.value.trim() === '') {
        Array.prototype.forEach.call(input.closest('[data-state="new-row"]').querySelectorAll('input'), function (i) { i.disabled = true; });
      }
    });
  });
</script>
@endsection
