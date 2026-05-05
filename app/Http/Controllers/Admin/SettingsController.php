<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'publicAppUrl' => AppSetting::publicAppUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'public_app_url' => ['required', 'url', 'max:255'],
        ]);

        AppSetting::setPublicAppUrl($validated['public_app_url']);

        return back()->with('status', 'Settings updated.');
    }
}
