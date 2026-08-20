<?php

namespace UasDashboard\WebCurator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        try {
            // Fetch entity profile from API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('web-api.api_base_url') . '/entity/profile', [
                'entity_id' => $entityId,
            ]);

            if (!$response->successful()) {
                \Log::error('Failed to fetch entity profile', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return redirect()->route('dashboard.web_curator.index')
                    ->with('error', 'Failed to load entity profile.');
            }

            $payload = $response->json('data') ?? null;

            if (!$payload) {
                return redirect()->route('dashboard.web_curator.index')
                    ->with('error', 'Entity profile not found.');
            }

            $profile = $this->formatProfilePayload($payload);

            return view('web_curator::entity_profile.edit', compact('profile'));

        } catch (\Exception $e) {
            \Log::error('Error fetching entity profile', [
                'exception' => $e->getMessage(),
                'entity_id' => $entityId,
            ]);
            return redirect()->route('dashboard.web_curator.index')
                ->with('error', 'Failed to load entity profile.');
        }
    }

    public function update(Request $request)
    {
        $entityId = $request->attributes->get('current_role_scope');

        if (!$entityId) {
            return redirect()->route('dashboard.home')->with('error', 'No entity scope set for this role.');
        }

        \Log::info('Entity Profile Update Request', [
            'entity_id' => $entityId,
            'request_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'establishment_date' => 'nullable|date',
            'entity_introduction' => 'nullable|string|max:2000',
            'slug' => 'required|string|max:50|alpha_dash',
            'head_role_name' => 'nullable|string|max:240',
            'head_personnel_id' => 'nullable|string|max:26',
            'head_role_assignment_id' => 'nullable|string',
            'head_info_name' => 'nullable|string|max:240',
            'head_info_designation' => 'nullable|string|max:240',
            'head_info_photo_url' => 'nullable|url',
            'head_message' => 'nullable|string',
        ]);

        \Log::info('Validation passed', [
            'validated_data' => $validated,
        ]);

        try {
            $payload = array_merge($validated, [
                'entity_id' => $entityId,
            ]);

            \Log::info('Sending to Web API', [
                'url' => config('web-api.api_base_url') . '/entity/profile',
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->put(config('web-api.api_base_url') . '/entity/profile', $payload);

            \Log::info('Web API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return redirect()->route('dashboard.web_curator.entity_profile.edit')
                    ->with('success', 'Entity profile updated successfully.');
            }

            \Log::error('Failed to update entity profile', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile: ' . ($response->json()['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('Error updating entity profile', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'entity_id' => $entityId,
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the profile.');
        }
    }

    /**
     * Search for personnel in IMS
     */
    public function searchPersonnel(Request $request)
    {
        $searchQuery = $request->input('query');

        if (!$searchQuery) {
            return response()->json(['error' => 'Search query is required'], 400);
        }

        try {
            // Search for personnel in IMS using the current API contract
            $searchResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('ims.api_base_url') . '/personnels', [
                'search' => $searchQuery,
                'include' => 'designation',
                'per_page' => 20,
            ]);

            if (!$searchResponse->successful()) {
                \Log::error('Failed to search personnel in IMS', [
                    'status' => $searchResponse->status(),
                    'response' => $searchResponse->body(),
                    'query' => $searchQuery,
                ]);
                return response()->json(['error' => 'Failed to search personnel'], 500);
            }

            $payload = $searchResponse->json();
            $personnelList = data_get($payload, 'data', $payload);

            $personnelList = collect($personnelList)->map(function ($person) {
                return [
                    'id' => data_get($person, 'id'),
                    'full_name' => data_get($person, 'full_name')
                        ?? data_get($person, 'full_name_with_title')
                        ?? data_get($person, 'display_name'),
                    'institutional_email' => data_get($person, 'institutional_email'),
                    'primary_phone' => data_get($person, 'primary_phone'),
                    'pin' => data_get($person, 'pin'),
                    'photo_url' => data_get($person, 'photo_url'),
                    'designation' => [
                        'designation_name' => data_get($person, 'designation.designation_name')
                            ?? data_get($person, 'designation_name'),
                        'designation_name_bn' => data_get($person, 'designation.designation_name_bn')
                            ?? data_get($person, 'designation_name_bn'),
                    ],
                ];
            })->values()->all();

            return response()->json([
                'personnel' => $personnelList,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error searching personnel', [
                'exception' => $e->getMessage(),
                'query' => $searchQuery,
            ]);
            return response()->json(['error' => 'An error occurred while searching for personnel'], 500);
        }
    }

    public function getPersonnelRoles(Request $request, $personnelId)
    {
        if (!$personnelId) {
            return response()->json(['error' => 'Personnel ID is required'], 400);
        }

        try {
            // Fetch personnel's active roles from IMS
            $rolesResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $request->cookie('ims_access_token'),
            ])->get(config('ims.api_base_url') . '/personnels/' . $personnelId . '/roles');

            if (!$rolesResponse->successful()) {
                \Log::error('Failed to fetch personnel roles from IMS', [
                    'status' => $rolesResponse->status(),
                    'response' => $rolesResponse->body(),
                    'personnel_id' => $personnelId,
                ]);

                $payload = $rolesResponse->json();

                return response()->json([
                    'error' => data_get($payload, 'message')
                        ?? data_get($payload, 'error')
                        ?? 'Failed to fetch personnel roles',
                ], $rolesResponse->status() >= 400 ? $rolesResponse->status() : 500);
            }

            $rolesData = $rolesResponse->json();

            return response()->json([
                'roles' => $rolesData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching personnel roles', [
                'exception' => $e->getMessage(),
                'personnel_id' => $personnelId,
            ]);
            return response()->json(['error' => 'An error occurred while fetching personnel roles'], 500);
        }
    }

    /**
     * Normalize the profile payload returned by the Web API so the view receives
     * a flat array regardless of schema changes.
     */
    private function formatProfilePayload(array $payload): array
    {
        $entity = data_get($payload, 'entity', []);
        $profile = data_get($payload, 'profile', $payload);
        $head = data_get($profile, 'head', []);
        $headDisplay = data_get($head, 'display', []);

        $roleAssignmentId = data_get($head, 'role_assignment_id')
            ?? data_get($head, 'role_assignment.id')
            ?? data_get($profile, 'head_role_assignment_id')
            ?? data_get($profile, 'head_role_id')
            ?? data_get($payload, 'head_role_assignment_id')
            ?? data_get($payload, 'head_role_id');

        if ($roleAssignmentId !== null && $roleAssignmentId !== '') {
            $roleAssignmentId = (string) $roleAssignmentId;
        } else {
            $roleAssignmentId = null;
        }

        return [
            'entity_id' => data_get($entity, 'id', data_get($payload, 'entity_id')),
            'entity_name' => data_get($entity, 'entity_name', data_get($payload, 'entity_name')),
            'entity_short_name' => data_get($entity, 'short_name', data_get($entity, 'entity_short_name', data_get($payload, 'entity_short_name'))),
            'entity_title' => data_get($entity, 'entity_title', data_get($payload, 'entity_title')),
            'entity_type' => data_get($entity, 'entity_type.entity_type_name', data_get($entity, 'entity_type.name', data_get($payload, 'entity_type'))),
            'entity_category' => data_get($entity, 'category.category_name', data_get($entity, 'entity_category', data_get($payload, 'entity_category'))),
            'parent_entity_name' => data_get($entity, 'parent.entity_name', data_get($entity, 'parent_entity_name', data_get($payload, 'parent_entity_name'))),
            'establishment_date' => data_get($profile, 'establishment_date', data_get($payload, 'establishment_date')),
            'entity_introduction' => data_get($profile, 'entity_introduction', data_get($payload, 'entity_introduction')),
            'slug' => data_get($profile, 'slug', data_get($payload, 'slug')),
            'head_personnel_id' => data_get($head, 'personnel_id', data_get($profile, 'head_personnel_id', data_get($payload, 'head_personnel_id'))),
            'head_role_assignment_id' => $roleAssignmentId,
            'head_role_name' => data_get($head, 'role_name', data_get($head, 'role.role_name', data_get($profile, 'head_role_name', data_get($payload, 'head_role_name')))),
            'head_info_name' => data_get($headDisplay, 'name', data_get($profile, 'head_info_name', data_get($payload, 'head_info_name'))),
            'head_info_designation' => data_get($headDisplay, 'designation', data_get($profile, 'head_info_designation', data_get($payload, 'head_info_designation'))),
            'head_info_photo_url' => data_get($headDisplay, 'photo_url', data_get($profile, 'head_info_photo_url', data_get($payload, 'head_info_photo_url'))),
            'head_message' => data_get($profile, 'head_message', data_get($payload, 'head_message')),
        ];
    }
}
