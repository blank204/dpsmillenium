<?php

namespace App\Http\Controllers\Admin;

use App\Content\SettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;

class SettingsController extends Controller
{
    public function edit(SettingsService $settings)
    {
        return view('admin.settings.edit', ['s' => $settings->snapshot()]);
    }

    public function update(NameOnlyRequest $request, SettingsService $settings)
    {
        $input = $request->except(['_token', '_method', 'actor_name']);
        // Ignore the blank "Add new…" row (also covers browsers with JavaScript off).
        foreach (['grades' => 'label', 'calendar_types' => 'name', 'newsletter_series' => 'name'] as $list => $field) {
            $input[$list] = array_values(array_filter($input[$list] ?? [], fn ($row) => ! empty($row['id']) || trim((string) ($row[$field] ?? '')) !== ''));
        }
        $settings->update($input, $request->actor());

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
