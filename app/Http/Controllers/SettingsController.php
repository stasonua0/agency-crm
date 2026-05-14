<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudioSettingRequest;
use App\Models\StudioSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => StudioSetting::singleton(),
        ]);
    }

    public function update(UpdateStudioSettingRequest $request): RedirectResponse
    {
        StudioSetting::singleton()->update([
            ...$request->validated(),
            'vat_enabled' => $request->boolean('vat_enabled'),
        ]);

        return redirect()->route('settings.index')->with('success', 'Реквизиты студии сохранены.');
    }
}
