<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            // Fetch entity settings from API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/editor/entity/settings', [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                \Log::error('Failed to fetch entity settings', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return redirect()->route('dashboard.web_curator.index')
                    ->with('error', 'Failed to load entity settings.');
            }

            $settings = $response->json()['data'] ?? [];

            // Group settings by key_group
            $groupedSettings = [];
            foreach ($settings as $setting) {
                $group = $setting['key_group'] ?? 'general';
                $groupedSettings[$group][] = $setting;
            }

            return view('web_curator::settings.index', compact('groupedSettings'));

        } catch (\Exception $e) {
            \Log::error('Error fetching entity settings', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->route('dashboard.web_curator.index')
                ->with('error', 'Failed to load entity settings.');
        }
    }

    public function store(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        $validated = $request->validate([
            'key_group' => 'required|string|max:50',
            'setting_key' => 'required|string|max:100',
            'value' => 'nullable',
            'value_type' => 'required|in:string,int,float,bool,json',
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->post(config('web-api.api_base_url') . '/editor/entity/settings/create', array_merge($validated, [
                'entity_id' => $entityId,
            ]));

            if ($response->successful()) {
                return redirect()->route('dashboard.web_curator.settings.index')
                    ->with('success', 'Setting created successfully.');
            }

            \Log::error('Failed to create entity setting', [
                'response' => $response->body(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create setting: ' . ($response->json()['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('Error creating entity setting', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the setting.');
        }
    }

    public function updateSingle(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        $validated = $request->validate([
            'setting_id' => 'required|integer',
            'key_group' => 'required|string|max:50',
            'value' => 'nullable',
            'value_type' => 'required|in:string,int,float,bool,json',
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->put(config('web-api.api_base_url') . '/editor/entity/settings/' . $validated['setting_id'], array_merge($validated, [
                'entity_id' => $entityId,
            ]));

            if ($response->successful()) {
                return redirect()->route('dashboard.web_curator.settings.index')
                    ->with('success', 'Setting updated successfully.');
            }

            \Log::error('Failed to update entity setting', [
                'response' => $response->body(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update setting: ' . ($response->json()['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('Error updating entity setting', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the setting.');
        }
    }

    public function destroy(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        $validated = $request->validate([
            'setting_id' => 'required|integer',
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->delete(config('web-api.api_base_url') . '/editor/entity/settings/' . $validated['setting_id'], [
                'entity_id' => $entityId,
            ]);

            if ($response->successful()) {
                return redirect()->route('dashboard.web_curator.settings.index')
                    ->with('success', 'Setting deleted successfully.');
            }

            \Log::error('Failed to delete entity setting', [
                'response' => $response->body(),
            ]);
            return redirect()->back()
                ->with('error', 'Failed to delete setting: ' . ($response->json()['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('Error deleting entity setting', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the setting.');
        }
    }

    // Keep the bulk update method for backward compatibility
    public function update(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        // Collect all settings from request (keys like "settings[key_group][setting_key]")
        $settings = $request->input('settings', []);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->put(config('web-api.api_base_url') . '/editor/entity/settings', [
                'entity_id' => $entityId,
                'settings' => $settings,
            ]);

            if ($response->successful()) {
                return redirect()->route('dashboard.web_curator.settings.index')
                    ->with('success', 'Entity settings updated successfully.');
            }

            \Log::error('Failed to update entity settings', [
                'response' => $response->body(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . ($response->json()['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('Error updating entity settings', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the settings.');
        }
    }
}
