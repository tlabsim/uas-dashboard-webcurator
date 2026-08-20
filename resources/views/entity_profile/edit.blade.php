@extends('web_curator::layouts.default')

@section('dashboard-content')
<div class="space-y-6" x-data="profileForm">
    <div class="page-header">
        <x-dashboard.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
            ['label' => 'Entity Profile'],
        ]" />
        <h2 class="page-title">Entity Profile</h2>
        <p class="text-sm text-gray-600 mt-1">Manage your entity's profile information and head details</p>
    </div>

    <!-- @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif -->

    @if (session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif 

    <!-- Entity Information (Read-only) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Entity Information</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label-base">Entity Name</label>
                <p class="text-gray-900 font-semibold">{{ $profile['entity_name'] ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="label-base">Entity Short Name</label>
                <p class="text-gray-900">{{ $profile['entity_short_name'] ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="label-base">Entity Type</label>
                <p class="text-gray-900">{{ $profile['entity_type'] ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="label-base">Category</label>
                <p class="text-gray-900">{{ $profile['entity_category'] ?? 'N/A' }}</p>
            </div>
            @if (!empty($profile['parent_entity_name']))
            <div>
                <label class="label-base">Parent Entity</label>
                <p class="text-gray-900">{{ $profile['parent_entity_name'] }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Editable Profile Form -->
    <form method="POST" class="flex flex-col space-y-6" action="{{ route('dashboard.web_curator.entity_profile.update') }}" data-web-curator-form @submit.prevent="validateAndSubmit">
        @csrf
        @method('PUT')

        <!-- Basic Information Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Basic Information</h3>
            </div>
            
            <div class="space-y-6">
                <!-- Establishment Date -->
                <div >
                    <label for="establishment_date" class="label-base">
                        Establishment Date
                    </label>
                    <input 
                        type="date" 
                        name="establishment_date" 
                        id="establishment_date" 
                        value="{{ old('establishment_date', $profile['establishment_date']) }}"
                        class="input-base w-full md:w-1/2"
                    >
                    <p class="mt-1 text-xs text-gray-500">The official establishment date of your entity</p>
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="label-base">
                        URL Slug <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="slug" 
                        id="slug" 
                        value="{{ old('slug', $profile['slug']) }}"
                        required
                        maxlength="50"
                        pattern="[a-z0-9\-_]+"
                        class="input-base w-full md:w-1/2"
                        placeholder="my-entity-slug"
                        x-on:input="updateSlugPreview"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        The URL-friendly identifier for your entity (lowercase, alphanumeric, hyphens, and underscores only)
                        <br>
                        <span class="font-medium">URL will be:</span> 
                        <code class="bg-gray-100 px-2 py-0.5 rounded">{{ config('web_curator.entity_web_base_url') }}/<span x-text="slugPreview"></span></code>
                    </p>
                </div>

                <div>
                    <label for="entity_introduction" class="label-base">Entity Introduction</label>
                    <textarea
                        name="entity_introduction"
                        id="entity_introduction"
                        rows="4"
                        maxlength="2000"
                        class="textarea-base w-full"
                        placeholder="Write a brief introduction to the entity"
                        x-model="entityIntroduction"
                    >{{ old('entity_introduction', $profile['entity_introduction'] ?? '') }}</textarea>
                    <div class="mt-1 flex items-center justify-between gap-3 text-xs text-gray-500">
                        <p>A concise introduction shown on the entity website.</p>
                        <p class="shrink-0"><span x-text="entityIntroduction.length"></span> / 2,000</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entity Head Information Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Entity Head Information</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Search for a personnel from IMS and assign them as the head of your entity. You can customize how their information appears on the website.
            </p>

            <div class="space-y-6">
                <!-- Personnel Search -->
                <div>
                    <label for="search_query" class="label-base">
                        Search Personnel
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="search_query" 
                            x-model="searchQuery"
                            class="input-base w-full md:w-2/3"
                            placeholder="Search by name, email, phone, or PIN"
                            @keyup.enter="searchPersonnel"
                        >
                        <button 
                            type="button"
                            @click="searchPersonnel"
                            :disabled="loading || !searchQuery"
                            class="btn-base btn-primary disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Searching...' : 'Search'"></span>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Search for personnel by name, email, phone number, or PIN.
                    </p>
                    
                    <!-- Error Message -->
                    <div x-show="errorMessage" class="mt-2 rounded-xl border border-[var(--destructive)]/20 bg-[color-mix(in_srgb,var(--destructive)_10%,var(--surface-raised))] px-3 py-2 text-sm text-[var(--destructive)]" x-text="errorMessage"></div>
                </div>

                <!-- Search Results -->
                <div x-show="searchResults.length > 0" x-cloak class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Search Results (<span x-text="searchResults.length"></span> found)
                        </label>
                        <button 
                            type="button"
                            @click="clearSearch"
                            class="text-sm text-[var(--text-soft)] hover:text-[var(--text-strong)] flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </button>
                    </div>
                    <div class="max-h-96 overflow-y-auto rounded-2xl border border-[var(--border)] bg-[var(--surface-raised)]">
                        <template x-for="person in searchResults" :key="person.id">
                            <div 
                                @click="selectPersonnel(person)"
                                class="flex items-center gap-3 border-b border-[var(--border-soft)] p-3 transition last:border-b-0 hover:bg-[var(--surface)]"
                                :class="{ 'bg-[color-mix(in_srgb,var(--accent)_10%,var(--surface-raised))]': selectedPersonnelId === person.id }"
                            >
                                <img 
                                    :src="person.photo_url || '/site-images/default_avatar.png'" 
                                    :alt="person.full_name"
                                    class="w-12 h-12 rounded-full object-cover"
                                    x-on:error="$event.target.src='/site-images/default_avatar.png'"
                                >
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900" x-text="person.full_name"></div>
                                    <div class="text-sm text-gray-600" x-text="person.designation?.designation_name || 'No designation'"></div>
                                    <div class="text-xs text-gray-500" x-text="person.institutional_email || person.primary_phone"></div>
                                </div>
                                <div class="text-xs text-gray-400" x-text="'PIN: ' + (person.pin || 'N/A')"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Hidden input for personnel ID -->
                <input type="hidden" name="head_personnel_id" x-model="selectedPersonnelId">

                <!-- Role Selection (Hidden until personnel is selected) -->
                <div x-show="showRoleSelection" x-cloak>
                    <label for="head_role_assignment_id" class="label-base">
                        Select Head Role (Optional)
                    </label>
                    <select 
                        name="head_role_assignment_id" 
                        id="head_role_assignment_id" 
                        x-model="selectedRoleAssignmentId"
                        @change="onRoleSelection"
                        class="select-base w-full md:w-1/2"
                    >
                        <option value="">-- Select the role that represents the head position --</option>
                        <template x-for="role in availableRoles" :key="role.assignment_id">
                            <option :value="role.assignment_id" x-text="`${role.role_name} (${role.entity_name})`"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Select which of this personnel's active roles corresponds to their position as head of your entity. Leave empty if not applicable.
                    </p>
                </div>
                <!-- Head Role Name -->
                <div>
                    <label for="head_role_name" class="label-base">
                        Head Role Title
                    </label>
                    <input 
                        type="text" 
                        name="head_role_name" 
                        id="head_role_name" 
                        value="{{ old('head_role_name', $profile['head_role_name']) }}"
                        x-model="headRoleName"
                        maxlength="240"
                        class="input-base w-full md:w-1/2"
                        placeholder="e.g., Dean, Director, Chairperson"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        The title/designation of the head position (e.g., "Dean" for faculty, "Director" for institute, "Chairperson" for department). Auto-filled when you select a role.
                    </p>
                </div>

                <!-- Head Display Information (Always visible, auto-filled when personnel selected) -->
                <div class="rounded-2xl border border-[var(--border-soft)] bg-[var(--surface)] p-4">
                    <h3 class="text-sm font-semibold text-[var(--text-strong)] mb-3">
                        Display Information
                    </h3>
                    <p class="text-xs text-[var(--text-soft)] mb-4">
                        These fields will be auto-filled when you select a personnel from search results, but you can modify them to control how the information appears on your entity's website.
                    </p>
                    
                    <div class="space-y-4">
                        <!-- Head Name -->
                        <div>
                            <label for="head_info_name" class="label-base">
                                Display Name
                            </label>
                            <input 
                                type="text" 
                                name="head_info_name" 
                                id="head_info_name" 
                                x-model="headInfoName"
                                maxlength="240"
                                class="input-base w-full"
                                placeholder="Full name as it should appear on website"
                            >
                            <p class="mt-1 text-xs text-gray-500">The name that will be displayed on the website</p>
                        </div>

                        <!-- Head Designation -->
                        <div>
                            <label for="head_info_designation" class="label-base">
                                Display Designation
                            </label>
                            <input 
                                type="text" 
                                name="head_info_designation" 
                                id="head_info_designation" 
                                x-model="headInfoDesignation"
                                maxlength="240"
                                class="input-base w-full"
                                placeholder="e.g., Professor, Associate Professor"
                            >
                            <p class="mt-1 text-xs text-gray-500">Academic/official designation (e.g., Professor, Associate Professor)</p>
                        </div>

                        <!-- Head Photo URL -->
                        <div>
                            <label for="head_info_photo_url" class="label-base">
                                Photo URL
                            </label>
                            <input 
                                type="url" 
                                name="head_info_photo_url" 
                                id="head_info_photo_url" 
                                x-model="headInfoPhotoUrl"
                                class="input-base w-full"
                                placeholder="https://example.com/photo.jpg"
                            >
                            <p class="mt-1 text-xs text-gray-500">URL to the head's profile photo</p>
                            
                            <!-- Photo Preview -->
                            <div x-show="headInfoPhotoUrl" class="mt-2">
                                <img :src="headInfoPhotoUrl" alt="Head photo preview" 
                                     class="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                     x-on:error="headInfoPhotoUrl = ''">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Head Message -->
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-2">
                        Message from the Head
                    </label>
                    @include('web_curator::partials.editor-shell', [
                        'shellId' => 'entity-head-message',
                        'fieldName' => 'head_message',
                        'initialContent' => old('head_message', $profile['head_message']),
                        'label' => 'Head Message',
                        'primaryPlaceholder' => 'Enter a message from the head that will be displayed on the website...',
                        'enableVisual' => false,
                        'primaryHeight' => 320,
                        'framedShell' => true,
                        'toolbarBasicTools' => [
                            'heading',
                            'undo',
                            'redo',
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'blockquote',
                            'codeBlock',
                            'link',
                            'image',
                            'imageUpload',
                            'mediaGallery',
                            'youtube',
                            'clearFormatting',
                        ],
                    ])
                    <p class="mt-1 text-xs text-gray-500 flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10s-4.477 10-10 10m0-1.2a8.8 8.8 0 1 0 0-17.6a8.8 8.8 0 0 0 0 17.6m-.66-14.369h1.32l-.089 7.06H11.43l-.088-7.06zM12 17.073a.825.825 0 0 1-.835-.835a.82.82 0 0 1 .835-.835c.476 0 .835.36.835.835a.82.82 0 0 1-.835.835"/></svg>
                        A message or statement from the head of the entity that will be displayed on the entity's website.
                        You can use rich text formatting.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3">
            <a 
                href="{{ route('dashboard.web_curator.index') }}"
                class="btn-base btn-outline"
            >
                Cancel
            </a>
            <button 
                type="submit"
                :disabled="loading"
                class="btn-base btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!loading">Update Profile</span>
                <span x-show="loading">Saving...</span>
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('profileForm', () => ({
        // Form state
        loading: false,
        slugPreview: '{{ $profile["slug"] ?? "your-slug" }}',
        entityIntroduction: @js(old('entity_introduction', $profile['entity_introduction'] ?? '')),
        
        // Personnel search
        searchQuery: '',
        searchResults: [],
        selectedPersonnelId: '{{ old("head_personnel_id", $profile["head_personnel_id"]) }}',
        showRoleSelection: {{ old("head_role_assignment_id", $profile["head_role_assignment_id"]) ? 'true' : 'false' }},
        
        // Head information
        headRoleName: '{{ old("head_role_name", $profile["head_role_name"]) }}',
        selectedRoleAssignmentId: '{{ old("head_role_assignment_id", $profile["head_role_assignment_id"]) }}',
        availableRoles: [],
        
        // Cached head info
        headInfoName: '{{ old("head_info_name", $profile["head_info_name"]) }}',
        headInfoDesignation: '{{ old("head_info_designation", $profile["head_info_designation"]) }}',
        headInfoPhotoUrl: '{{ old("head_info_photo_url", $profile["head_info_photo_url"]) }}',
        
        // Messages
        errorMessage: '',
        
        init() {
            this.selectedRoleAssignmentId = this.normalizeAssignmentId(this.selectedRoleAssignmentId);
        },
        
        updateSlugPreview() {
            this.slugPreview = document.getElementById('slug').value || 'your-slug';
        },
        
        clearSearch() {
            this.searchQuery = '';
            this.searchResults = [];
            this.errorMessage = '';
        },
        
        async searchPersonnel() {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                this.errorMessage = 'Please enter a search term';
                return;
            }
            
            this.loading = true;
            this.errorMessage = '';
            this.searchResults = [];
            
            try {
                const url = '{{ route('dashboard.web_curator.entity_profile.search_personnel') }}';
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        query: this.searchQuery
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to search personnel');
                }
                
                this.searchResults = data.personnel || [];
                
                if (this.searchResults.length === 0) {
                    this.errorMessage = 'No personnel found matching your search';
                }
                
            } catch (error) {
                console.error('Search error:', error);
                this.errorMessage = error.message || 'An error occurred while searching';
                this.searchResults = [];
            } finally {
                this.loading = false;
            }
        },

        async selectPersonnel(person) {
            this.selectedPersonnelId = person.id;
            this.errorMessage = '';
            
            // Auto-fill head information from personnel data
            this.headInfoName = person.full_name || '';
            this.headInfoDesignation = person.designation?.designation_name || '';
            this.headInfoPhotoUrl = person.photo_url || '';
            
            // Fetch roles for this personnel
            await this.fetchPersonnelRoles(person.id);
        },

        async fetchPersonnelRoles(personnelId) {
            this.loading = true;
            this.showRoleSelection = false;
            this.availableRoles = [];
            this.selectedRoleAssignmentId = '';
            
            try {
                const url = '{{ route('dashboard.web_curator.entity_profile.personnel_roles', ['personnelId' => '__ID__']) }}'.replace('__ID__', personnelId);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Failed to fetch roles');
                }
                
                const rolesData = data.roles || [];
                
                // Filter active roles
                this.availableRoles = rolesData
                    .filter(role => role.status === 'Active' || role.status === 'active')
                    .map(role => ({
                        assignment_id: this.normalizeAssignmentId(
                            role.role_assignment_id ?? role.id ?? role.assignment_id ?? role.pivot?.id ?? role.entity_role_assignment_id ?? null
                        ),
                        role_name: role.role?.role_name || role.role_name || 'Unknown Role',
                        entity_name: role.entity?.entity_name || role.entity_name || 'Unknown Entity'
                    }))
                    .filter(role => role.assignment_id !== '');
                
                if (this.availableRoles.length === 0) {
                    this.errorMessage = 'No active roles found for this personnel';
                } else {
                    this.showRoleSelection = true;
                }
                
            } catch (error) {
                console.error('Error fetching roles:', error);
                this.errorMessage = error.message || 'Failed to fetch personnel roles';
            } finally {
                this.loading = false;
            }
        },

        normalizeAssignmentId(value) {
            if (value === null || value === undefined) {
                return '';
            }
            return String(value);
        },

        onRoleSelection() {
            const normalizedId = this.normalizeAssignmentId(this.selectedRoleAssignmentId);
            if (!normalizedId) {
                return;
            }

            this.selectedRoleAssignmentId = normalizedId;

            const selectedRole = this.availableRoles.find(
                role => role.assignment_id === normalizedId
            );
            
            if (selectedRole) {
                this.headRoleName = selectedRole.role_name || '';
            }
        },
        
        async validateAndSubmit(event) {
            await window.WebCuratorEditors.prepareFormSubmission(event.target);
            // Submit the form normally
            event.target.submit();
        }
    }));
});
</script>
@endpush

@endsection
