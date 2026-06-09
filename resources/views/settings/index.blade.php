@extends('web_curator::layouts.default')

@section('title', 'Entity Settings')

@section('dashboard-content')
<div class="flex flex-col gap-y-6" x-data="settingsManager()" style="min-height: calc(100vh - var(--header-height) - 7rem);">
    <div class="page-header mb-0">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <x-dashboard.breadcrumbs :items="[
                    ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
                    ['label' => 'Entity Settings'],
                ]" />
                <h2 class="page-title">Entity Settings</h2>
                <p class="text-sm text-gray-600 mt-1">Manage your entity's website settings</p>
            </div>
            <button 
                @click="showAddModal = true"
                class="btn-base btn-primary flex items-center gap-2 self-start md:self-auto"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Setting
            </button>
        </div>
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
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (empty($groupedSettings))
        <div class="card flex flex-1 items-center justify-center text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No settings configured</h3>
            <p class="mt-1 text-sm text-gray-500">Click "Add New Setting" to create your first setting.</p>
        </div>
    @else
        <div class="flex-1 gap-y-4">
        <!-- Settings Groups -->
        @foreach ($groupedSettings as $group => $settings)
            <div class="card p-0 overflow-hidden {{ $loop->first && $loop->count === 1 ? 'flex flex-col' : '' }}">
                <!-- Group Header -->
                <div 
                    class="card-header !m-0 p-4 lg:px-6 cursor-pointer transition-colors"
                    @click="toggleGroup('{{ $group }}')"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="card-title capitalize">
                            {{ str_replace('_', ' ', $group) }}
                            <span class="text-sm text-gray-500 font-normal ml-2">({{ count($settings) }} settings)</span>
                        </h3>
                        <svg 
                            class="w-5 h-5 text-gray-600 transition-transform"
                            :class="{ 'rotate-180': openGroups.includes('{{ $group }}') }"
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Group Settings Table -->
                <div 
                    x-show="openGroups.includes('{{ $group }}')"
                    x-transition
                    class="{{ $loop->first && $loop->count === 1 ? 'flex-1' : '' }}"
                >
                    <div class="md:hidden">
                        @foreach ($settings as $setting)
                            <div class="border border-[var(--border-soft)] bg-[var(--surface-raised)] p-3 {{ $loop->first ? 'border-t-0' : '' }} {{ $loop->last ? 'rounded-b-2xl' : 'border-b-0' }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-900">
                                        {{ $setting['setting_key'] }}
                                    </h4>
                                    <span class="badge-tint badge-tint-blue shrink-0">
                                        {{ $setting['value_type'] }}
                                    </span>
                                </div>

                                <div class="mt-3 text-sm text-gray-900">
                                    <span class="mr-2 text-[11px] font-medium uppercase tracking-wide text-[var(--text-soft)]">Value:</span>
                                    @if ($setting['value_type'] === 'bool')
                                        <span class="badge-tint {{ $setting['value'] ? 'badge-tint-green' : 'badge-tint-gray' }}">
                                            {{ $setting['value'] ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    @elseif ($setting['value_type'] === 'json')
                                        <code class="inline-block break-all rounded-lg bg-[var(--surface)] px-2 py-1 text-xs">{{ Str::limit($setting['value'], 100) }}</code>
                                    @else
                                        <span class="break-words">{{ $setting['value'] }}</span>
                                    @endif
                                </div>

                                <div class="mt-3 flex items-center justify-end gap-1.5">
                                    <button 
                                        @click="viewSetting({{ json_encode($setting) }})"
                                        class="btn-icon h-9 w-9"
                                        title="View setting"
                                        aria-label="View setting"
                                        style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed; outline: none; -webkit-appearance: none; appearance: none;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                                    </button>
                                    <button 
                                        @click="editSetting({{ json_encode($setting) }})"
                                        class="btn-icon h-9 w-9"
                                        title="Edit setting"
                                        aria-label="Edit setting"
                                        style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent); outline: none; -webkit-appearance: none; appearance: none;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                    </button>
                                    <button 
                                        @click="confirmDelete({{ $setting['id'] }}, '{{ $setting['setting_key'] }}')"
                                        class="btn-icon h-9 w-9"
                                        title="Delete setting"
                                        aria-label="Delete setting"
                                        style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626; outline: none; -webkit-appearance: none; appearance: none;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hidden overflow-x-auto md:block {{ $loop->first && $loop->count === 1 ? 'flex-1' : '' }}">
                    <table class="w-full">
                        <thead class="bg-[var(--surface-muted)] border-y border-[var(--border-soft)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[var(--text-soft)] w-1/4">Setting Key</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[var(--text-soft)] w-2/5">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[var(--text-soft)] w-1/6">Type</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-[var(--text-soft)] w-1/6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--surface-raised)] divide-y divide-[var(--border-soft)]">
                            @foreach ($settings as $setting)
                                <tr class="transition-colors hover:bg-surface-muted-st">
                                    <td class="px-6 py-4 align-center w-1/4">
                                        <div class="text-sm font-medium text-gray-900">{{ $setting['setting_key'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-center w-2/5">
                                        <div class="text-sm text-gray-900">
                                            @if ($setting['value_type'] === 'bool')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting['value'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $setting['value'] ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            @elseif ($setting['value_type'] === 'json')
                                                <code class="inline-block rounded-lg bg-[var(--surface)] px-2 py-1 text-xs break-all">{{ Str::limit($setting['value'], 80) }}</code>
                                            @else
                                                <span class="break-words">{{ $setting['value'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-center w-1/6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $setting['value_type'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-center whitespace-nowrap text-right w-1/6">
                                        <button 
                                            @click="viewSetting({{ json_encode($setting) }})"
                                            class="btn-icon mr-2"
                                            title="View setting"
                                            aria-label="View setting"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #7c3aed; outline: none; -webkit-appearance: none; appearance: none;"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10m0 0L14 14.75M16.75 12L14 9.25"/><path d="M2 15V9a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></g></svg>
                                        </button>
                                        <button 
                                            @click="editSetting({{ json_encode($setting) }})"
                                            class="btn-icon mr-2"
                                            title="Edit setting"
                                            aria-label="Edit setting"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent); outline: none; -webkit-appearance: none; appearance: none;"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                        </button>
                                        <button 
                                            @click="confirmDelete({{ $setting['id'] }}, '{{ $setting['setting_key'] }}')"
                                            class="btn-icon"
                                            title="Delete setting"
                                            aria-label="Delete setting"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626; outline: none; -webkit-appearance: none; appearance: none;"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    @endif
    

    <!-- Add/Edit Setting Modal -->
    <div 
        x-show="showAddModal || showEditModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div 
            @click.away="closeModal()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="w-11/12 max-w-2xl max-h-[90vh] overflow-hidden hadow-2xl flex flex-col"
        >
            <form :action="showEditModal ? '{{ route('dashboard.web_curator.settings.update-single') }}' : '{{ route('dashboard.web_curator.settings.store') }}'" method="POST" class="card flex flex-col h-full">
                @csrf
                <input type="hidden" name="setting_id" x-model="currentSetting.id" x-show="showEditModal">
                
                <div class="card-header border-b border-[var(--border-soft)]">
                    <h3 class="card-title" x-text="showEditModal ? 'Edit Setting' : 'Add New Setting'"></h3>
                </div>

                <div class="overflow-y-auto flex-1">
                    <!-- Key Group -->
                    <div class="mb-4">
                        <label for="key_group" class="label-base label-required">Group</label>
                        <input 
                            type="text" 
                            name="key_group" 
                            id="key_group"
                            x-model="currentSetting.key_group"
                            required
                            class="input-base"
                            placeholder="e.g., general, contact, social"
                        >
                        <p class="help-text">Group name for organizing related settings</p>
                    </div>

                    <!-- Setting Key -->
                    <div class="mb-4">
                        <label for="setting_key" class="label-base label-required">Setting Key</label>
                        <input 
                            type="text" 
                            name="setting_key" 
                            id="setting_key"
                            x-model="currentSetting.setting_key"
                            required
                            :readonly="showEditModal"
                            class="input-base"
                            :class="{ 'bg-gray-100 cursor-not-allowed': showEditModal }"
                            placeholder="e.g., site_title, phone_number"
                        >
                        <p class="help-text">Unique identifier for this setting <span x-show="showEditModal" class="text-amber-600">(cannot be changed after creation)</span></p>
                    </div>

                    <!-- Value Type -->
                    <div class="mb-4">
                        <label class="label-base label-required">Value Type</label>
                        <div x-data="customSelect({
                            options: [
                                {value: 'string', label: 'String (Text)'},
                                {value: 'int', label: 'Integer (Number)'},
                                {value: 'float', label: 'Float (Decimal)'},
                                {value: 'bool', label: 'Boolean (True/False)'},
                                {value: 'json', label: 'JSON (Object/Array)'}
                            ],
                            placeholder: 'Select value type',
                            name: 'value_type',
                            value: currentSetting.value_type,
                            required: true,
                            editable: false
                        })" x-init="$watch('selectedValue', value => currentSetting.value_type = value); selectedValue = currentSetting.value_type">
                            <x-custom-select-template />
                        </div>
                    </div>

                    <!-- Value (conditional based on type) -->
                    <div class="mb-4">
                        <label for="value" class="label-base label-required">Value</label>
                        
                        <!-- Boolean -->
                        <div x-show="currentSetting.value_type === 'bool'" class="mt-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="value"
                                    value="1"
                                    :checked="currentSetting.value == 1 || currentSetting.value == 'true' || currentSetting.value == true"
                                    class="checkbox-base"
                                >
                                <span class="ml-2 text-sm text-gray-700">Enable this setting</span>
                            </label>
                        </div>

                        <!-- Integer -->
                        <input 
                            x-show="currentSetting.value_type === 'int'"
                            type="number" 
                            name="value"
                            x-model="currentSetting.value"
                            class="input-base"
                            placeholder="Enter a whole number"
                        >

                        <!-- Float -->
                        <input 
                            x-show="currentSetting.value_type === 'float'"
                            type="number" 
                            step="0.01"
                            name="value"
                            x-model="currentSetting.value"
                            class="input-base"
                            placeholder="Enter a decimal number"
                        >

                        <!-- JSON -->
                        <textarea 
                            x-show="currentSetting.value_type === 'json'"
                            name="value"
                            rows="6"
                            x-model="currentSetting.value"
                            class="textarea-base font-mono text-sm"
                            placeholder='{"key": "value"}'
                        ></textarea>
                        <p x-show="currentSetting.value_type === 'json'" class="help-text">Enter valid JSON format</p>

                        <!-- String (default) -->
                        <input 
                            x-show="currentSetting.value_type === 'string'"
                            type="text" 
                            name="value"
                            x-model="currentSetting.value"
                            class="input-base"
                            placeholder="Enter text value"
                        >
                    </div>
                </div>

                <div class="card-footer flex justify-end gap-2">
                    <button 
                        type="button"
                        @click="closeModal()"
                        class="btn-base btn-secondary"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="btn-base btn-primary"
                        x-text="showEditModal ? 'Update Setting' : 'Create Setting'"
                    >
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Setting Modal -->
    <div 
        x-show="showViewModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div 
            @click.away="closeModal()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="w-full max-w-2xl rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-2xl"
        >
            <div class="card-header border-b border-[var(--border-soft)]">
                <h3 class="card-title">Setting Details</h3>
            </div>

            <div class="p-4 lg:p-6 space-y-4">
                <div>
                    <label class="label-base">Group</label>
                    <p class="mt-1 text-gray-900 font-medium" x-text="currentSetting.key_group"></p>
                </div>
                <div>
                    <label class="label-base">Setting Key</label>
                    <p class="mt-1 text-gray-900 font-medium font-mono" x-text="currentSetting.setting_key"></p>
                </div>
                <div>
                    <label class="label-base">Type</label>
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="currentSetting.value_type"></span>
                    </div>
                </div>
                <div>
                    <label class="label-base">Value</label>
                    <div class="mt-2 rounded-xl border border-[var(--border-soft)] bg-[var(--surface)] p-4">
                        <template x-if="currentSetting.value_type === 'bool'">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" 
                                  :class="currentSetting.value ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                  x-text="currentSetting.value ? '✓ Enabled' : '✗ Disabled'"></span>
                        </template>
                        <template x-if="currentSetting.value_type === 'json'">
                            <pre class="text-sm font-mono whitespace-pre-wrap text-gray-900" x-text="currentSetting.value"></pre>
                        </template>
                        <template x-if="currentSetting.value_type !== 'bool' && currentSetting.value_type !== 'json'">
                            <p class="text-gray-900" x-text="currentSetting.value || '(empty)'"></p>
                        </template>
                    </div>
                </div>
            </div>

            <div class="border-t border-[var(--border-soft)] bg-[var(--surface)] px-4 py-3 lg:px-6 lg:py-4 flex justify-end">
                <button 
                    type="button"
                    @click="closeModal()"
                    class="btn-base btn-secondary"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div 
        x-show="showDeleteModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div 
            @click.away="closeModal()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="w-full max-w-md rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] shadow-2xl"
        >
            <div class="p-4 lg:p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/15">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Setting</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Are you sure you want to delete the setting <span class="font-semibold text-gray-900" x-text="'\"' + deleteSettingKey + '\"'"></span>? 
                            <span class="text-red-600 font-medium">This action cannot be undone.</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-[var(--border-soft)] bg-[var(--surface)] px-4 py-3 lg:px-6 lg:py-4 flex justify-end space-x-3">
                <button 
                    type="button"
                    @click="closeModal()"
                    class="btn-base btn-secondary"
                >
                    Cancel
                </button>
                <form :action="'{{ route('dashboard.web_curator.settings.destroy') }}'" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="setting_id" x-model="deleteSettingId">
                    <button 
                        type="submit"
                        class="btn-base btn-danger"
                    >
                        Delete Setting
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function settingsManager() {
        return {
            openGroups: @json(array_keys($groupedSettings)),
            showAddModal: false,
            showEditModal: false,
            showViewModal: false,
            showDeleteModal: false,
            currentSetting: {
                id: null,
                key_group: '',
                setting_key: '',
                value: '',
                value_type: 'string'
            },
            deleteSettingId: null,
            deleteSettingKey: '',
            
            toggleGroup(group) {
                const index = this.openGroups.indexOf(group);
                if (index > -1) {
                    this.openGroups.splice(index, 1);
                } else {
                    this.openGroups.push(group);
                }
            },
            
            viewSetting(setting) {
                this.currentSetting = { ...setting };
                this.showViewModal = true;
            },
            
            editSetting(setting) {
                this.currentSetting = { ...setting };
                this.showEditModal = true;
            },
            
            confirmDelete(id, key) {
                this.deleteSettingId = id;
                this.deleteSettingKey = key;
                this.showDeleteModal = true;
            },
            
            closeModal() {
                this.showAddModal = false;
                this.showEditModal = false;
                this.showViewModal = false;
                this.showDeleteModal = false;
                this.currentSetting = {
                    id: null,
                    key_group: '',
                    setting_key: '',
                    value: '',
                    value_type: 'string'
                };
                this.deleteSettingId = null;
                this.deleteSettingKey = '';
            }
        }
    }
</script>
@endsection

