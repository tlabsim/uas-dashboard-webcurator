@extends('web_curator::layouts.default')

@php
    $currentRoleId = session('ims_user.current_db_role_id', null);
    $allRoles = collect(session('ims_user.db_roles', []));
    $currentRole = $allRoles->firstWhere('assignment_id', $currentRoleId);
    $entityName = $currentRole['scope_entity_name'] ?? 'Unknown Entity';
@endphp

@section('dashboard-content')
    <div x-data="menuManager()" class="relative flex flex-1 flex-col gap-y-6">
        <input x-ref="menuImportInput"
               type="file"
               accept="application/json,.json"
               class="hidden"
               @change="importTemplate($event)">

        <div class="page-header">
            <x-dashboard.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('dashboard.home'), 'home' => true],
                ['label' => 'Menu Manager'],
            ]" />
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="page-title">Menu Manager</h2>
                    <p class="mt-1 flex flex-col gap-1 text-sm text-gray-600 sm:flex-row sm:flex-wrap sm:items-center sm:gap-0">
                        <span class="font-semibold text-[var(--accent)]">{{ $entityName }}</span>
                        <span class="mx-1 hidden text-gray-400 sm:inline">|</span>
                        <span class="text-gray-600">Manage menu categories, submenus, and navigation order</span>
                    </p>
                </div>
            </div>
        </div>

        <div x-ref="toolbarStickyWrap" class="menu-manager-toolbar-sticky-wrap">
            <div x-ref="toolbarSticky" class="card menu-manager-toolbar-sticky p-3">
                <div class="flex flex-col gap-3 sm:gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center justify-between lg:contents">
                        <div class="flex items-center gap-1 self-start rounded-2xl border border-[var(--border-soft)] bg-[var(--surface-raised)] p-1">
                            <button type="button" 
                                    @click="undo" 
                                    :disabled="!canUndo"
                                    :class="canUndo ? '' : 'text-gray-300 cursor-not-allowed opacity-60'"
                                    class="btn-icon h-9 w-9"
                                    style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);"
                                    title="Undo">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                            </button>
                            <button type="button" 
                                    @click="redo" 
                                    :disabled="!canRedo"
                                    :class="canRedo ? '' : 'text-gray-300 cursor-not-allowed opacity-60'"
                                    class="btn-icon h-9 w-9"
                                    style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);"
                                    title="Redo">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" />
                                    </svg>
                            </button>
                        </div>

                        <div class="flex items-center justify-end gap-2 lg:order-last">
                            <button type="button"
                                class="btn-icon h-10 w-10"
                                @click="triggerImportTemplate"
                                title="Import menu template"
                                aria-label="Import menu template"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M20 15.006V10.66c0-.818 0-1.227-.152-1.595s-.441-.657-1.02-1.235l-4.736-4.739c-.499-.499-.748-.748-1.058-.896a2 2 0 0 0-.197-.082C12.514 2 12.161 2 11.456 2c-3.245 0-4.868 0-5.967.886a4 4 0 0 0-.603.604C4 4.59 4 6.213 4 9.46v4.545c0 3.773 0 5.66 1.172 6.832C6.115 21.78 7.52 21.964 10 22m3-19.5V3c0 2.83 0 4.245.879 5.124c.878.879 2.293.879 5.121.879h.5"/><path d="M15 22c-.607-.59-3-2.16-3-3s2.393-2.41 3-3m-2 3h7"/></g></svg>
                            </button>

                            <button type="button"
                                class="btn-icon h-10 w-10"
                                @click="exportTemplate"
                                :disabled="categories.length === 0"
                                :class="categories.length === 0 ? 'cursor-not-allowed opacity-60 text-gray-300' : ''"
                                title="Export menu template"
                                aria-label="Export menu template"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--text);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M20 14v-3.343c0-.818 0-1.226-.152-1.594c-.152-.367-.441-.657-1.02-1.235l-4.736-4.736c-.499-.499-.748-.748-1.058-.896a2 2 0 0 0-.197-.082C12.514 2 12.161 2 11.456 2c-3.245 0-4.868 0-5.967.886a4 4 0 0 0-.603.603C4 4.59 4 6.211 4 9.456V14c0 3.771 0 5.657 1.172 6.828S8.229 22 12 22m1-19.5V3c0 2.828 0 4.243.879 5.121C14.757 9 16.172 9 19 9h.5"/><path d="M17 22c.607-.59 3-2.16 3-3s-2.393-2.41-3-3m2 3h-7"/></g></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2 lg:ml-auto lg:min-w-0 lg:flex-row lg:flex-wrap lg:items-center lg:justify-end lg:gap-3">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:flex xl:flex-wrap xl:items-center xl:justify-end xl:gap-3">
                            {{-- PREVIEW NAVIGATION BUTTON --}}
                            <button type="button" class="btn btn-outline w-full justify-center gap-2 sm:w-auto" @click="showPreview = !showPreview">                       
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0" x-show="!showPreview">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0" x-show="showPreview">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                                <span class="text-sm" x-show="showPreview">Hide Preview</span>
                                <span class="text-sm" x-show="!showPreview">Preview <span class="hidden xl:inline">Navigation</span></span>
                            </button>

                            {{-- Compact mode button to collapse details of menus and submenus --}}
                            <button type="button" class="btn btn-outline w-full justify-center gap-2 sm:w-auto"
                                @click="categories.forEach(cat => { cat.showSubcategories = cat.subcategories.length > 0 ? true : false; cat.editing = false; cat.subcategories.forEach(sub => sub.editing = false); })"
                                title="Compact View">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 14 14"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="m.5 13.5l4-4M1 9.5h3.5V13m9 .5l-4-4m3.5 0H9.5V13M.5.5l4 4M1 4.5h3.5V1m9-.5l-4 4m3.5 0H9.5V1"/></svg>
                                <span class="text-sm">Compact View</span>
                            </button>

                            {{-- ADD NEW CATEGORY BUTTON --}}
                            <button type="button" class="btn btn-primary w-full justify-center gap-2 sm:w-auto" @click="addCategory">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add New <span class="hidden xl:inline">Category</span></span>
                            </button>

                            {{-- SAVE BUTTON --}}
                            <button type="button" class="btn btn-primary w-full justify-center gap-2 sm:w-auto" @click="saveChanges"
                                :disabled="loading" x-show="categories.length > 0">
                                <svg x-show="loading" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="h-5 w-5 shrink-0 hds-flight-icon--animation-loading animate-spin text-white"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g fill="#ffffffff" fill-rule="evenodd" clip-rule="evenodd"> <path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" opacity=".2"></path> <path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path> </g> </g></svg>
                                <template x-if="loading">
                                    <span class="flex items-center gap-2">
                                        <span class="spinner-border spinner-border-sm"></span>
                                        Saving...
                                    </span>
                                </template>
                                <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span x-show="!loading">Save <span class="hidden xl:inline">Changes</span></span>
                            </button>
                        </div>                        
                    </div>  
                </div>
            </div>
        </div>

        {{-- NAVIGATION PREVIEW --}}
        <div x-show="showPreview" x-collapse class="card p-4 lg:p-6">
            <div class="mb-4 space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <h4 class="flex items-center gap-2 text-lg font-bold text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 14 14" class="h-5 w-5 text-[var(--accent)]"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="m4.83 12.5l-4.33 1v-12l4.33-1v12zm0 0l4.34 1v-12L4.83.5v12zm8.67 0l-4.33 1v-12l4.33-1v12z"/></svg>
                        <span>Navigation Preview</span>
                    </h4>
                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <label for="includeStaticPages" class="text-sm text-gray-700 cursor-pointer">
                            Include static page menus
                        </label>
                        <input type="checkbox" 
                               x-model="includeStaticPages" 
                               id="includeStaticPages"
                               class="toggle-switch">
                    </div>
                </div>
                <p class="flex items-center gap-2 text-sm font-thin text-emerald-600">
                    <svg width="20" height="20" viewBox="0 0 32 32"><path fill="currentColor" d="M16 13a1 1 0 0 1 1 1v9a1 1 0 1 1-2 0v-9a1 1 0 0 1 1-1m0-2a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3M2 16C2 8.268 8.268 2 16 2s14 6.268 14 14s-6.268 14-14 14S2 23.732 2 16M16 4C9.373 4 4 9.373 4 16s5.373 12 12 12s12-5.373 12-12S22.627 4 16 4"/></svg>
                    This shows how your menu will appear on the entity website.
                </p>
            </div>
            
            <div class="rounded-lg p-4 shadow-sm border border-[var(--border-soft)]">
                <template x-if="menuItems.length === 0">
                    <div class="text-gray-500 text-center py-4">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                        <p>No menu items to display. Enable "Make this a menu" for categories or subcategories.</p>
                    </div>
                </template>
                
                <nav class="flex flex-wrap gap-1" x-show="menuItems.length > 0">
                    <template x-for="(item, index) in menuItems" :key="index">
                        <div class="relative group" x-data="{ open: false }">
                            <template x-if="item.type === 'static_page' && (!item.submenu || item.submenu.length === 0)">
                                <a :href="item.edit_url || '#'"
                                   target="_blank"
                                   rel="noopener"
                                   class="px-4 py-2 text-sm font-medium transition-colors flex items-center gap-1 rounded-md text-purple-700 hover:text-purple-800 hover:bg-purple-50 border border-purple-200">
                                    <span x-text="item.text"></span>
                                    <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                            </template>
                            <template x-if="item.type !== 'static_page' || (item.submenu && item.submenu.length > 0)">
                                <button 
                                    @click="item.submenu && item.submenu.length > 0 ? (open = !open) : null"
                                    class="px-4 py-2 text-sm font-medium transition-colors flex items-center gap-1 rounded-md"
                                    :class="{
                                        'text-gray-700 hover:text-blue-600 hover:bg-blue-50': item.type !== 'static_page',
                                        'text-purple-700 hover:text-purple-800 hover:bg-purple-50 border border-purple-200': item.type === 'static_page',
                                        'cursor-pointer': item.submenu && item.submenu.length > 0
                                    }">
                                    <span x-text="item.text"></span>
                                    <svg x-show="item.type === 'static_page'" class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <svg x-show="item.submenu && item.submenu.length > 0" 
                                         class="w-4 h-4 transition-transform" 
                                         :class="open ? 'rotate-180' : ''"
                                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </template>
                            
                            {{-- Submenu dropdown --}}
                            <div x-show="open && item.submenu && item.submenu.length > 0" 
                                 x-collapse
                                 class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                <div class="py-1">
                                    <template x-for="(subitem, subindex) in item.submenu" :key="subindex">
                                        <a href="#" 
                                           class="flex items-center justify-start gap-2 px-4 py-2 text-sm transition-colors"
                                           :class="{
                                               'text-blue-500 hover:bg-blue-50 hover:text-blue-600': subitem.type !== 'static_page',
                                               'text-purple-700 hover:bg-purple-50 hover:text-purple-800 border-l-2 border-purple-300': subitem.type === 'static_page'
                                           }"
                                           :href="subitem.type === 'static_page' ? (subitem.edit_url || '#') : '#'"
                                           :target="subitem.type === 'static_page' ? '_blank' : null"
                                           :rel="subitem.type === 'static_page' ? 'noopener' : null"
                                           @click="if (subitem.type !== 'static_page') { $event.preventDefault(); }">
                                           <svg x-show="subitem.type !== 'static_page'" style="min-width: 16px !important; height:16px;" class="text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                            </svg>   
                                           <svg x-show="subitem.type === 'static_page'" style="min-width: 16px !important; height:16px;" class="text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span x-text="subitem.text"></span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </nav>
            </div>
            
            {{-- Legend for menu items --}}
            <div x-show="includeStaticPages && staticPages.length > 0" class="mt-4">
                <div class="flex items-center justify-center gap-6 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></div>
                        <span class="text-gray-600">Category/Subcategory Menus</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-purple-100 border border-purple-300"></div>
                        <span class="text-gray-600">Static Page Menus</span>
                    </div>
                </div>
            </div>
        </div>



        {{-- No categories message --}}
        <template x-if="categories.length === 0">
            <div class="border border-gray-150 rounded-lg p-3 bg-gray-100 shadow-sm">
                <div class="text-gray-500">No categories added.</div>
            </div>
        </template>

        {{-- CATEGORY LIST --}}
        <ul class="space-y-1 p-0">
            <template x-for="(category, catIndex) in categories" :key="category.temp_id || category.id">
                <li class="border border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm" @dragover.prevent
                    @drop="dropCategory(catIndex)">

                    {{-- ROW DISPLAY --}}
                    <div class="flex items-center justify-between flex-wrap gap-2">

                        <div class="flex items-center flex-wrap gap-2">
                            {{-- Expand/Collapse toggle --}}
                            <button @click="category.showSubcategories = !category.showSubcategories"
                                class="focus:outline-none">
                                <svg :class="category.showSubcategories ? 'rotate-90' : ''"
                                    class="w-4 h-4 transition-transform" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            {{-- Drag handle --}}
                            <span class="w-6 cursor-move text-gray-400 text-xl hover:text-gray-600 select-none"
                                draggable="true" @dragstart="dragCategoryIndex = catIndex"
                                title="Drag to reorder menus">≡</span>

                            {{-- Name and slug --}}
                            <span class="font-semibold text-gray-800 truncate"
                                x-text="category.category_name"></span>
                            <span class="text-gray-400 truncate">(<span
                                    x-text="category.category_slug"></span>)</span>

                            {{-- Menu badge --}}
                            <template x-if="category.is_menu">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">MENU</span>
                            </template>

                            {{-- Link icon --}}
                            <template x-if="category.link_url">
                                <i class="bi bi-link text-blue-600"></i>
                            </template>
                        </div>

                        {{-- Action icons --}}
                        <div class="flex items-center gap-2">
                            <button type="button"
                                @click="toggleEditCategory(category)"
                                class="btn-icon h-9 w-9"
                                title="Edit menu"
                                aria-label="Edit menu"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                            </button>
                            <button type="button" @click="openDeleteCategoryModal(catIndex, category)" class="btn-icon h-9 w-9"
                                title="Delete menu" aria-label="Delete menu"
                                style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Edit panel --}}
                    <div x-show="category.editing" x-collapse class="ml-6 my-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" x-model="category.is_menu"
                                    @change="menuToggled(category, 'category_name', 'menu_text'); saveToHistory();" class="toggle-switch">
                                <span class="text-gray-700">Make this a menu.</span>
                            </div>

                            <div class="hidden md:block"></div>
                            {{-- Column 1 --}}
                            <div>
                                <label class="label-base">Category Name</label>
                                <input type="text" class="input-base w-full" x-model="category.category_name"
                                    @input="updateSlugAndMenuText(category, 'category_name', 'category_slug', 'menu_text'); debouncedSaveToHistory();"
                                    placeholder="Category Name">
                            </div>

                            {{-- Column 2 --}}
                            <div>
                                <label class="label-base">Category Slug</label>
                                <input type="text" class="input-base w-full" x-model="category.category_slug"
                                    @input="category.slug_overridden = true; debouncedSaveToHistory();" placeholder="Slug">
                            </div>

                            <template x-if="category.is_menu" class="mt-2">
                                <template x-for="field in ['menu_text', 'link_url']" :key="field">
                                    <div>
                                        <label class="label-base"
                                            x-text="field === 'menu_text' ? 'Menu Text' : 'Link URL'"></label>
                                        <input type="text" class="input-base w-full"
                                            :placeholder="field === 'menu_text' ? 'Menu Text' : 'Link URL (optional)'"
                                            x-model="category[field]"
                                            @input="field === 'menu_text' ? (category.menu_text_overridden = true, debouncedSaveToHistory()) : debouncedSaveToHistory();">
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>


                    {{-- SUBCATEGORIES --}}
                    <ul x-show="category.showSubcategories" class="ml-6 my-3 pl-6 border-l border-gray-200 space-y-1">
                        <template x-for="(subcat, subIndex) in category.subcategories" :key="subcat.temp_id || subcat.id">
                            <li class="border border-gray-200 rounded-lg p-2 bg-gray-50 shadow-sm" @dragover.prevent
                                @drop="dropSubcategory(category, subIndex)">

                                <div class="flex justify-between items-center flex-wrap gap-2">
                                    <div class="flex items-center justify-start flex-wrap gap-2">

                                        {{-- Drag handle --}}
                                        <span class="w-6 cursor-move text-gray-400 text-xl hover:text-gray-600 select-none"
                                            draggable="true"
                                            @dragstart="dragSubcategory = { category, subIndex }">≡</span>

                                        {{-- Name and slug --}}
                                        <span class="font-medium text-gray-700 truncate "
                                            x-text="subcat.subcategory_name"></span>
                                        <span class="text-gray-400 truncate ">(<span
                                                x-text="subcat.subcategory_slug"></span>)</span>

                                        {{-- Menu badge --}}
                                        <template x-if="subcat.is_menu">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">MENU</span>
                                        </template>

                                        {{-- Link icon --}}
                                        <template x-if="subcat.link_url">
                                            <i class="bi bi-link text-blue-600"></i>
                                        </template>
                                    </div>

                                    {{-- Action icons --}}
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="toggleEditSubcategory(subcat)"
                                            class="btn-icon h-9 w-9"
                                            title="Edit submenu"
                                            aria-label="Edit submenu"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: var(--accent);">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M11.943 1.25H13.5a.75.75 0 0 1 0 1.5H12c-2.378 0-4.086.002-5.386.176c-1.279.172-2.05.5-2.62 1.069c-.569.57-.896 1.34-1.068 2.619c-.174 1.3-.176 3.008-.176 5.386s.002 4.086.176 5.386c.172 1.279.5 2.05 1.069 2.62c.57.569 1.34.896 2.619 1.068c1.3.174 3.008.176 5.386.176s4.086-.002 5.386-.176c1.279-.172 2.05-.5 2.62-1.069c.569-.57.896-1.34 1.068-2.619c.174-1.3.176-3.008.176-5.386v-1.5a.75.75 0 0 1 1.5 0v1.557c0 2.309 0 4.118-.19 5.53c-.194 1.444-.6 2.584-1.494 3.479c-.895.895-2.035 1.3-3.48 1.494c-1.411.19-3.22.19-5.529.19h-.114c-2.309 0-4.118 0-5.53-.19c-1.444-.194-2.584-.6-3.479-1.494c-.895-.895-1.3-2.035-1.494-3.48c-.19-1.411-.19-3.22-.19-5.529v-.114c0-2.309 0-4.118.19-5.53c.194-1.444.6-2.584 1.494-3.479c.895-.895 2.035-1.3 3.48-1.494c1.411-.19 3.22-.19 5.529-.19m4.827 1.026a3.503 3.503 0 0 1 4.954 4.953l-6.648 6.649c-.371.37-.604.604-.863.806a5.3 5.3 0 0 1-.987.61c-.297.141-.61.245-1.107.411l-2.905.968a1.492 1.492 0 0 1-1.887-1.887l.968-2.905c.166-.498.27-.81.411-1.107q.252-.526.61-.987c.202-.26.435-.492.806-.863zm3.893 1.06a2.003 2.003 0 0 0-2.832 0l-.376.377q.032.145.098.338c.143.413.415.957.927 1.469a3.9 3.9 0 0 0 1.807 1.025l.376-.376a2.003 2.003 0 0 0 0-2.832m-1.558 4.391a5.4 5.4 0 0 1-1.686-1.146a5.4 5.4 0 0 1-1.146-1.686L11.218 9.95c-.417.417-.58.582-.72.76a4 4 0 0 0-.437.71c-.098.203-.172.423-.359.982l-.431 1.295l1.032 1.033l1.295-.432c.56-.187.779-.261.983-.358q.378-.18.71-.439c.177-.139.342-.302.759-.718z" clip-rule="evenodd"/></svg>
                                        </button>
                                        <button type="button" @click="openDeleteSubcategoryModal(category, subIndex, subcat)"
                                            class="btn-icon h-9 w-9"
                                            title="Delete submenu"
                                            aria-label="Delete submenu"
                                            style="border: 1.5px solid var(--border); background: var(--surface-raised); color: #dc2626;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" class="h-5 w-5"><path fill="currentColor" fill-rule="evenodd" d="M10.31 2.25h3.38c.217 0 .406 0 .584.028a2.25 2.25 0 0 1 1.64 1.183c.084.16.143.339.212.544l.111.335l.03.085a1.25 1.25 0 0 0 1.233.825h3a.75.75 0 0 1 0 1.5h-17a.75.75 0 0 1 0-1.5h3.09a1.25 1.25 0 0 0 1.173-.91l.112-.335c.068-.205.127-.384.21-.544a2.25 2.25 0 0 1 1.641-1.183c.178-.028.367-.028.583-.028m-1.302 3a3 3 0 0 0 .175-.428l.1-.3c.091-.273.112-.328.133-.368a.75.75 0 0 1 .547-.395a3 3 0 0 1 .392-.009h3.29c.288 0 .348.002.392.01a.75.75 0 0 1 .547.394c.021.04.042.095.133.369l.1.3l.039.112q.059.164.136.315z" clip-rule="evenodd"/><path fill="currentColor" d="M5.915 8.45a.75.75 0 1 0-1.497.1l.464 6.952c.085 1.282.154 2.318.316 3.132c.169.845.455 1.551 1.047 2.104s1.315.793 2.17.904c.822.108 1.86.108 3.146.108h.879c1.285 0 2.324 0 3.146-.108c.854-.111 1.578-.35 2.17-.904c.591-.553.877-1.26 1.046-2.104c.162-.813.23-1.85.316-3.132l.464-6.952a.75.75 0 0 0-1.497-.1l-.46 6.9c-.09 1.347-.154 2.285-.294 2.99c-.137.685-.327 1.047-.6 1.303c-.274.256-.648.422-1.34.512c-.713.093-1.653.095-3.004.095h-.774c-1.35 0-2.29-.002-3.004-.095c-.692-.09-1.066-.256-1.34-.512c-.273-.256-.463-.618-.6-1.302c-.14-.706-.204-1.644-.294-2.992z"/><path fill="currentColor" d="M9.425 10.254a.75.75 0 0 1 .821.671l.5 5a.75.75 0 0 1-1.492.15l-.5-5a.75.75 0 0 1 .671-.821m5.15 0a.75.75 0 0 1 .671.82l-.5 5a.75.75 0 0 1-1.492-.149l.5-5a.75.75 0 0 1 .82-.671"/></svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Edit panel --}}
                                <div x-show="subcat.editing" x-collapse class="my-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
                                        <div class="flex items-center space-x-2">
                                            <input type="checkbox" x-model="subcat.is_menu"
                                                @change="menuToggled(subcat, 'subcategory_name', 'menu_text'); saveToHistory();"
                                                class="toggle-switch">
                                            <span class="text-gray-700">Make this a menu.</span>
                                        </div>

                                        <div class="hidden md:block"></div>
                                        {{-- Column 1 --}}
                                        <div>
                                            <label class="label-base">Subcategory Name</label>
                                            <input type="text" class="input-base w-full" x-model="subcat.subcategory_name"
                                                @input="updateSlugAndMenuText(subcat, 'subcategory_name', 'subcategory_slug', 'menu_text'); debouncedSaveToHistory();"
                                                placeholder="Subcategory Name">
                                        </div>

                                        {{-- Column 2 --}}
                                        <div>
                                            <label class="label-base">Subcategory Slug</label>
                                            <input type="text" class="input-base w-full" x-model="subcat.subcategory_slug"
                                                @input="subcat.slug_overridden = true; debouncedSaveToHistory();" placeholder="Slug">
                                        </div>

                                        <template x-if="subcat.is_menu" class="mt-2">
                                            <template x-for="field in ['menu_text', 'link_url']" :key="field">
                                                <div>
                                                    <label class="label-base"
                                                        x-text="field === 'menu_text' ? 'Menu Text' : 'Link URL'"></label>
                                                    <input type="text" class="input-base w-full"
                                                        :placeholder="field === 'menu_text' ? 'Menu Text' : 'Link URL (optional)'"
                                                        x-model="subcat[field]"
                                                        @input="field === 'menu_text' ? (subcat.menu_text_overridden = true, debouncedSaveToHistory()) : debouncedSaveToHistory();">
                                                </div>
                                            </template>
                                        </template>
                                    </div>

                                    {{-- <div class="flex justify-end">
                                        <button class="btn btn-primary btn-sm" @click="subcat.editing = false">Save
                                            Changes</button>
                                    </div> --}}
                                </div>
                            </li>
                        </template>

                        <li>
                            <button type="button" @click="addSubcategory(category)"
                                class="btn-s btn-success flex items-center gap-2">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Subcategory
                            </button>
                        </li>
                    </ul>
                </li>
            </template>
        </ul>

        <div x-show="deleteModalOpen"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-gray-600/50 p-4"
             @click.self="closeDeleteModal()"
             @keydown.escape.window="closeDeleteModal()"
             x-cloak>
            <div class="relative mx-auto flex min-h-full max-w-md items-center justify-center">
                <div class="w-full rounded-md border border-[var(--border-soft)] bg-[var(--surface-raised)] p-5 shadow-[var(--shadow-raised)]">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-[var(--text-strong)]" x-text="deleteModalTitle"></h3>
                        <div class="mt-2 px-3 py-3">
                            <p class="text-sm text-[var(--text-soft)]" x-text="deleteModalMessage"></p>
                        </div>
                        <div class="px-1 py-3">
                            <button type="button"
                                    @click="performDelete()"
                                    :disabled="deleteSubmitting"
                                    class="w-full rounded-md bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none"
                                    style="box-shadow: 0 0 0 3px transparent;">
                                <span x-show="!deleteSubmitting">Delete</span>
                                <span x-show="deleteSubmitting" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Deleting...
                                </span>
                            </button>
                            <button type="button"
                                    @click="closeDeleteModal()"
                                    :disabled="deleteSubmitting"
                                    class="mt-3 w-full rounded-md bg-[var(--surface)] px-4 py-2 text-base font-medium text-[var(--text)] shadow-sm transition hover:bg-[var(--surface)] focus:outline-none">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function menuManager() {
            return {
                categories: @js($categories ?? []),
                staticPages: @js($static_pages ?? []),
                loading: false,
                showPreview: false,
                includeStaticPages: true,
                stickyActive: false,
                stickyTicking: false,
                
                // Undo/Redo functionality
                history: [],
                historyIndex: -1,
                maxHistorySize: 5,
                isUndoRedoAction: false,
                saveHistoryTimeout: null,
                
                get canUndo() {
                    return this.historyIndex > 0;
                },
                
                get canRedo() {
                    return this.historyIndex < this.history.length - 1;
                },
                
                // Computed property for complete menu items including static pages
                get menuItems() {
                    let items = this.categories
                        .filter(cat => cat.is_menu)
                        .map(cat => ({
                            text: (cat.menu_text || cat.category_name || '').trim(),
                            url: cat.link_url || '#',
                            type: 'category',
                            menu_order: Number.isFinite(Number(cat.menu_order)) ? Number(cat.menu_order) : 999,
                            id: cat.id,
                            submenu: cat.subcategories
                                .filter(sub => sub.is_menu)
                                .map(sub => ({
                                    text: (sub.menu_text || sub.subcategory_name || '').trim(),
                                    url: sub.link_url || '#',
                                    type: 'subcategory',
                                    menu_order: Number.isFinite(Number(sub.menu_order)) ? Number(sub.menu_order) : 999,
                                    id: sub.id,
                                    submenu: []
                                }))
                                .sort((a, b) => this.compareMenuNodes(a, b))
                        }));
                    
                    if (this.includeStaticPages && this.staticPages) {
                        this.staticPages.forEach(page => {
                            const pageItem = {
                                text: page.menu_text || page.page_title,
                                url: '/' + page.page_slug,
                                type: 'static_page',
                                menu_order: Number.isFinite(Number(page.menu_order)) ? Number(page.menu_order) : 999,
                                edit_url: page.edit_url || null,
                                id: page.id,
                                submenu: [],
                            };
                            
                            if (page.page_subcategory) {
                                let attachedToSubcategory = false;

                                for (const item of items) {
                                    const parentSubcategory = (item.submenu || []).find(sub => Number(sub.id) === Number(page.page_subcategory));
                                    if (parentSubcategory) {
                                        parentSubcategory.submenu = parentSubcategory.submenu || [];
                                        parentSubcategory.submenu.push(pageItem);
                                        attachedToSubcategory = true;
                                        break;
                                    }
                                }

                                if (attachedToSubcategory) {
                                    return;
                                }
                            }

                            if (page.page_category) {
                                const parentCategory = items.find(item => Number(item.id) === Number(page.page_category));

                                if (parentCategory) {
                                    parentCategory.submenu = parentCategory.submenu || [];
                                    parentCategory.submenu.push(pageItem);
                                    return;
                                }
                            }

                            items.push(pageItem);
                        });
                    }

                    items.forEach(item => {
                        if (Array.isArray(item.submenu)) {
                            item.submenu.sort((a, b) => this.compareMenuNodes(a, b));
                            item.submenu.forEach(subitem => {
                                if (Array.isArray(subitem.submenu)) {
                                    subitem.submenu.sort((a, b) => this.compareMenuNodes(a, b));
                                }
                            });
                        }
                    });

                    return items.sort((a, b) => this.compareMenuNodes(a, b));
                },

                compareMenuNodes(a, b) {
                    const leftOrder = Number.isFinite(Number(a?.menu_order)) ? Number(a.menu_order) : 999;
                    const rightOrder = Number.isFinite(Number(b?.menu_order)) ? Number(b.menu_order) : 999;

                    if (leftOrder !== rightOrder) {
                        return leftOrder - rightOrder;
                    }

                    const leftTypeWeight = a?.type === 'category' ? 0 : 1;
                    const rightTypeWeight = b?.type === 'category' ? 0 : 1;

                    if (leftTypeWeight !== rightTypeWeight) {
                        return leftTypeWeight - rightTypeWeight;
                    }

                    return String(a?.text || '').localeCompare(String(b?.text || ''));
                },
                
                init() {
                    this.categories.forEach(category => {
                        category.showSubcategories = category.subcategories.length > 0;
                    });
                    // Save initial state to history
                    this.saveToHistory();
                    this.initStickyToolbar();
                },

                initStickyToolbar() {
                    const scrollContainer = this.$root.closest('.dashboard-page') || window;
                    this._stickyScrollContainer = scrollContainer;

                    const handleSticky = () => {
                        if (this.stickyTicking) {
                            return;
                        }

                        this.stickyTicking = true;
                        requestAnimationFrame(() => {
                            this.updateStickyToolbar();
                            this.stickyTicking = false;
                        });
                    };

                    this._stickyHandler = handleSticky;
                    scrollContainer.addEventListener('scroll', handleSticky, { passive: true });
                    window.addEventListener('resize', handleSticky, { passive: true });

                    this.$nextTick(() => this.updateStickyToolbar());
                },

                updateStickyToolbar() {
                    const wrap = this.$refs.toolbarStickyWrap;
                    const toolbar = this.$refs.toolbarSticky;

                    if (!wrap || !toolbar) {
                        return;
                    }

                    const isLargeScreen = window.innerWidth >= 1024;
                    const topOffset = 96;

                    if (!isLargeScreen) {
                        wrap.style.height = '';
                        toolbar.classList.remove('is-stuck');
                        toolbar.style.top = '';
                        toolbar.style.left = '';
                        toolbar.style.width = '';
                        this.stickyActive = false;
                        return;
                    }

                    const wrapRect = wrap.getBoundingClientRect();
                    const toolbarHeight = toolbar.offsetHeight;
                    const shouldStick = wrapRect.top <= topOffset && wrapRect.bottom > topOffset + toolbarHeight;

                    wrap.style.height = `${toolbarHeight}px`;
                    toolbar.style.top = `${topOffset}px`;

                    if (shouldStick) {
                        toolbar.classList.add('is-stuck');
                        toolbar.style.left = `${wrapRect.left}px`;
                        toolbar.style.width = `${wrapRect.width}px`;
                        this.stickyActive = true;
                    } else {
                        toolbar.classList.remove('is-stuck');
                        toolbar.style.left = '';
                        toolbar.style.width = '';
                        this.stickyActive = false;
                    }
                },

                templateFormat: 'nstu-webcurator-menu-template',
                templateVersion: 1,

                triggerImportTemplate() {
                    this.$refs.menuImportInput?.click();
                },

                async importTemplate(event) {
                    const file = event?.target?.files?.[0];
                    if (!file) {
                        return;
                    }

                    try {
                        const text = await file.text();
                        const parsed = JSON.parse(text);
                        const importedCategories = this.parseImportedTemplate(parsed);

                        if (!Array.isArray(importedCategories) || importedCategories.length === 0) {
                            throw new Error('The selected JSON template does not contain any categories.');
                        }

                        if (this.categories.length > 0) {
                            const shouldReplace = window.confirm('Importing a template will replace the current category structure in the editor. Continue?');
                            if (!shouldReplace) {
                                return;
                            }
                        }

                        this.categories = importedCategories;
                        this.closeDeleteModal();
                        this.saveToHistory();
                        this.$nextTick(() => this.updateStickyToolbar());

                        window.toastNotifier.show({
                            message: `Imported ${importedCategories.length} ${importedCategories.length === 1 ? 'category' : 'categories'} from template.`,
                            type: 'success'
                        });
                    } catch (error) {
                        window.toastNotifier.show({
                            message: error.message || 'Failed to import the menu template.',
                            type: 'error'
                        });
                    } finally {
                        if (event?.target) {
                            event.target.value = '';
                        }
                    }
                },

                exportTemplate() {
                    if (this.categories.length === 0) {
                        window.toastNotifier.show({
                            message: 'Add at least one category before exporting a template.',
                            type: 'error'
                        });
                        return;
                    }

                    const payload = {
                        format: this.templateFormat,
                        version: this.templateVersion,
                        exported_at: new Date().toISOString(),
                        source: {
                            entity_id: {{ $entity_id ?? 'null' }},
                            entity_name: @js($entityName),
                        },
                        categories: this.categories.map((category, catIndex) => this.serializeCategoryTemplate(category, catIndex)),
                        static_page_menus: this.staticPages.map((page) => this.serializeStaticPageTemplate(page)),
                    };

                    const json = JSON.stringify(payload, null, 2);
                    const blob = new Blob([json], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    const entitySlug = this.slugify(@js($entityName) || 'menu-template');

                    link.href = url;
                    link.download = `menus_categories_${entitySlug || 'template'}_${this.timestampForFileName()}.json`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);

                    window.toastNotifier.show({
                        message: 'Menu template exported successfully.',
                        type: 'success'
                    });
                },

                serializeCategoryTemplate(category, catIndex) {
                    return {
                        category_name: category.category_name || '',
                        category_slug: category.category_slug || this.slugify(category.category_name || ''),
                        is_menu: !!category.is_menu,
                        menu_text: category.menu_text || '',
                        link_url: category.link_url || '',
                        menu_order: Number.isFinite(Number(category.menu_order)) ? Number(category.menu_order) : catIndex + 1,
                        subcategories: Array.isArray(category.subcategories)
                            ? category.subcategories.map((subcategory, subIndex) => this.serializeSubcategoryTemplate(subcategory, subIndex))
                            : [],
                    };
                },

                serializeSubcategoryTemplate(subcategory, subIndex) {
                    return {
                        subcategory_name: subcategory.subcategory_name || '',
                        subcategory_slug: subcategory.subcategory_slug || this.slugify(subcategory.subcategory_name || ''),
                        is_menu: !!subcategory.is_menu,
                        menu_text: subcategory.menu_text || '',
                        link_url: subcategory.link_url || '',
                        menu_order: Number.isFinite(Number(subcategory.menu_order)) ? Number(subcategory.menu_order) : subIndex + 1,
                    };
                },

                serializeStaticPageTemplate(page) {
                    const category = this.categories.find((item) => item.id === page.page_category);
                    const subcategory = category?.subcategories?.find((item) => item.id === page.page_subcategory);

                    return {
                        page_title: page.page_title || '',
                        page_slug: page.page_slug || '',
                        menu_text: page.menu_text || page.page_title || '',
                        menu_order: Number.isFinite(Number(page.menu_order)) ? Number(page.menu_order) : 999,
                        page_category_slug: category?.category_slug || null,
                        page_subcategory_slug: subcategory?.subcategory_slug || null,
                    };
                },

                parseImportedTemplate(parsed) {
                    const categories = Array.isArray(parsed)
                        ? parsed
                        : (Array.isArray(parsed?.categories) ? parsed.categories : null);

                    if (!categories) {
                        throw new Error('Unsupported template format. Expected a categories array.');
                    }

                    if (!Array.isArray(parsed) && parsed?.format && parsed.format !== this.templateFormat) {
                        throw new Error(`Unsupported template format "${parsed.format}".`);
                    }

                    if (!Array.isArray(parsed) && parsed?.version && Number(parsed.version) > this.templateVersion) {
                        throw new Error(`Template version ${parsed.version} is newer than this importer supports.`);
                    }

                    return categories.map((category, index) => this.normalizeImportedCategory(category, index));
                },

                normalizeImportedCategory(category, index) {
                    const categoryName = String(category?.category_name || category?.name || '').trim();
                    if (!categoryName) {
                        throw new Error(`Category at position ${index + 1} is missing a category_name.`);
                    }

                    const categorySlug = String(category?.category_slug || category?.slug || this.slugify(categoryName)).trim();
                    const isMenu = Boolean(category?.is_menu);
                    const menuText = String(category?.menu_text || '').trim();

                    return {
                        id: null,
                        category_name: categoryName,
                        category_slug: categorySlug || this.slugify(categoryName),
                        is_menu: isMenu,
                        menu_text: isMenu ? (menuText || categoryName) : menuText,
                        link_url: String(category?.link_url || '').trim(),
                        menu_order: Number.isFinite(Number(category?.menu_order)) ? Number(category.menu_order) : index + 1,
                        slug_overridden: categorySlug !== this.slugify(categoryName),
                        menu_text_overridden: isMenu ? (menuText && menuText !== categoryName) : false,
                        subcategories: Array.isArray(category?.subcategories)
                            ? category.subcategories.map((subcategory, subIndex) => this.normalizeImportedSubcategory(subcategory, subIndex))
                            : [],
                        showSubcategories: Array.isArray(category?.subcategories) && category.subcategories.length > 0,
                        editing: false,
                        temp_id: this.generateTempId('cat'),
                    };
                },

                normalizeImportedSubcategory(subcategory, index) {
                    const subcategoryName = String(subcategory?.subcategory_name || subcategory?.name || '').trim();
                    if (!subcategoryName) {
                        throw new Error(`A subcategory at position ${index + 1} is missing a subcategory_name.`);
                    }

                    const subcategorySlug = String(subcategory?.subcategory_slug || subcategory?.slug || this.slugify(subcategoryName)).trim();
                    const isMenu = Boolean(subcategory?.is_menu);
                    const menuText = String(subcategory?.menu_text || '').trim();

                    return {
                        id: null,
                        subcategory_name: subcategoryName,
                        subcategory_slug: subcategorySlug || this.slugify(subcategoryName),
                        is_menu: isMenu,
                        menu_text: isMenu ? (menuText || subcategoryName) : menuText,
                        link_url: String(subcategory?.link_url || '').trim(),
                        menu_order: Number.isFinite(Number(subcategory?.menu_order)) ? Number(subcategory.menu_order) : index + 1,
                        slug_overridden: subcategorySlug !== this.slugify(subcategoryName),
                        menu_text_overridden: isMenu ? (menuText && menuText !== subcategoryName) : false,
                        editing: false,
                        temp_id: this.generateTempId('sub'),
                    };
                },

                generateTempId(prefix = 'tmp') {
                    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
                },

                slugify(value) {
                    return String(value || '')
                        .trim()
                        .toLowerCase()
                        .replace(/\s+/g, '-')
                        .replace(/[^\w\-]+/g, '')
                        .replace(/\-{2,}/g, '-')
                        .replace(/^\-+|\-+$/g, '');
                },

                timestampForFileName() {
                    const now = new Date();
                    const pad = (value) => String(value).padStart(2, '0');
                    return `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
                },
                
                // Save current state to history
                saveToHistory() {
                    if (this.isUndoRedoAction) {
                        return; // Don't save to history during undo/redo operations
                    }
                    
                    // Deep clone the current state
                    const state = JSON.parse(JSON.stringify(this.categories));
                    
                    // If we're not at the end of history, remove future states
                    if (this.historyIndex < this.history.length - 1) {
                        this.history = this.history.slice(0, this.historyIndex + 1);
                    }
                    
                    // Add new state
                    this.history.push(state);
                    
                    // Limit history size
                    if (this.history.length > this.maxHistorySize) {
                        this.history.shift();
                    } else {
                        this.historyIndex++;
                    }
                },
                
                // Debounced save for text input changes
                debouncedSaveToHistory() {
                    if (this.saveHistoryTimeout) {
                        clearTimeout(this.saveHistoryTimeout);
                    }
                    this.saveHistoryTimeout = setTimeout(() => {
                        this.saveToHistory();
                    }, 1000); // Save after 1 second of inactivity
                },
                
                undo() {
                    if (!this.canUndo) return;
                    
                    this.isUndoRedoAction = true;
                    this.historyIndex--;
                    this.categories = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
                    
                    // Restore UI state
                    this.categories.forEach(category => {
                        if (!category.hasOwnProperty('showSubcategories')) {
                            category.showSubcategories = category.subcategories.length > 0;
                        }
                        if (!category.hasOwnProperty('editing')) {
                            category.editing = false;
                        }
                        category.subcategories.forEach(sub => {
                            if (!sub.hasOwnProperty('editing')) {
                                sub.editing = false;
                            }
                        });
                    });
                    
                    this.$nextTick(() => {
                        this.isUndoRedoAction = false;
                    });
                },
                
                redo() {
                    if (!this.canRedo) return;
                    
                    this.isUndoRedoAction = true;
                    this.historyIndex++;
                    this.categories = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
                    
                    // Restore UI state
                    this.categories.forEach(category => {
                        if (!category.hasOwnProperty('showSubcategories')) {
                            category.showSubcategories = category.subcategories.length > 0;
                        }
                        if (!category.hasOwnProperty('editing')) {
                            category.editing = false;
                        }
                        category.subcategories.forEach(sub => {
                            if (!sub.hasOwnProperty('editing')) {
                                sub.editing = false;
                            }
                        });
                    });
                    
                    this.$nextTick(() => {
                        this.isUndoRedoAction = false;
                    });
                },
                
                deleteModalOpen: false,
                deleteSubmitting: false,
                deleteTarget: null,

                openDeleteCategoryModal(index, category) {
                    this.deleteTarget = {
                        type: 'category',
                        index,
                        name: category.category_name,
                        subcategoryCount: Array.isArray(category.subcategories) ? category.subcategories.length : 0,
                    };
                    this.deleteModalOpen = true;
                },

                openDeleteSubcategoryModal(category, subIndex, subcat) {
                    this.deleteTarget = {
                        type: 'subcategory',
                        categoryId: category.id ?? category.temp_id ?? null,
                        subIndex,
                        name: subcat.subcategory_name,
                    };
                    this.deleteModalOpen = true;
                },

                closeDeleteModal() {
                    if (this.deleteSubmitting) {
                        return;
                    }

                    this.deleteModalOpen = false;
                    this.deleteTarget = null;
                },

                get deleteModalTitle() {
                    return this.deleteTarget?.type === 'subcategory' ? 'Delete Submenu' : 'Delete Menu';
                },

                get deleteModalMessage() {
                    if (!this.deleteTarget) {
                        return '';
                    }

                    if (this.deleteTarget.type === 'subcategory') {
                        return `Are you sure you want to delete "${this.deleteTarget.name}"? This action cannot be undone.`;
                    }

                    if ((this.deleteTarget.subcategoryCount ?? 0) > 0) {
                        const count = this.deleteTarget.subcategoryCount;
                        return `Are you sure you want to delete "${this.deleteTarget.name}"? This will also delete ${count} subcategor${count === 1 ? 'y' : 'ies'}.`;
                    }

                    return `Are you sure you want to delete "${this.deleteTarget.name}"? This action cannot be undone.`;
                },

                async performDelete() {
                    if (!this.deleteTarget || this.deleteSubmitting) {
                        return;
                    }

                    this.deleteSubmitting = true;
                    const previousCategories = JSON.parse(JSON.stringify(this.categories));

                    try {
                        if (this.deleteTarget.type === 'subcategory') {
                            const category = this.categories.find(cat =>
                                (cat.id ?? cat.temp_id ?? null) === this.deleteTarget.categoryId
                            );

                            if (!category) {
                                throw new Error('Unable to locate the submenu category for deletion.');
                            }

                            this.deleteSubcategory(category, this.deleteTarget.subIndex, false);
                        } else {
                            this.deleteCategory(this.deleteTarget.index, false);
                        }

                        const saved = await this.saveChanges({
                            successMessage: this.deleteTarget.type === 'subcategory'
                                ? 'Submenu deleted successfully.'
                                : 'Menu deleted successfully.',
                        });

                        if (saved) {
                            this.saveToHistory();
                            this.deleteModalOpen = false;
                            this.deleteTarget = null;
                        } else {
                            this.categories = previousCategories;
                        }
                    } catch (error) {
                        this.categories = previousCategories;
                        window.toastNotifier.show({
                            message: error.message || 'Failed to delete menu item. Please try again.',
                            type: 'error'
                        });
                    } finally {
                        this.deleteSubmitting = false;
                    }
                },

                addCategory() {
                    this.categories.push({
                        id: null,
                        category_name: 'New Menu',
                        category_slug: 'new-menu',
                        is_menu: false,
                        menu_text: '',
                        link_url: '',
                        slug_overridden: false,
                        menu_text_overridden: false,
                        subcategories: [],
                        showSubcategories: false,
                        editing: true,
                        temp_id: Date.now()
                    });
                    this.saveToHistory();
                },

                addSubcategory(category) {
                    category.subcategories.push({
                        id: null,
                        subcategory_name: 'New Submenu',
                        subcategory_slug: 'new-submenu',
                        is_menu: false,
                        menu_text: '',
                        link_url: '',
                        slug_overridden: false,
                        menu_text_overridden: false,
                        editing: true,
                        temp_id: Date.now()
                    });
                    this.saveToHistory();
                },

                toggleEditCategory(category) {
                    category.editing = !category.editing;
                },

                toggleEditSubcategory(subcat) {
                    subcat.editing = !subcat.editing;
                },

                deleteCategory(index, persistHistory = true) {
                    this.categories.splice(index, 1);
                    if (persistHistory) {
                        this.saveToHistory();
                    }
                },

                deleteSubcategory(category, subIndex, persistHistory = true) {
                    category.subcategories.splice(subIndex, 1);
                    if (persistHistory) {
                        this.saveToHistory();
                    }
                },

                updateSlugAndMenuText(item, nameField, slugField, menuTextField) {
                    if (!item.slug_overridden) {
                        item[slugField] = item[nameField]
                            .toLowerCase()
                            .replace(/\s+/g, '-')
                            .replace(/[^\w\-]+/g, '');
                    }

                    if (!item.menu_text_overridden && item.is_menu) {
                        item[menuTextField] = item[nameField];
                    }
                },

                menuToggled(item, nameField, menuTextField) {
                    if (item.is_menu && !item.menu_text_overridden) {
                        item[menuTextField] = item[nameField];
                    }
                },

                async saveChanges(options = {}) {
                    this.loading = true;

                    // Set menu_order
                    this.categories.forEach((category, catIndex) => {
                        category.menu_order = catIndex + 1;

                        if (category.subcategories) {
                            category.subcategories.forEach((subcat, subIndex) => {
                                subcat.menu_order = subIndex + 1;
                            });
                        }
                    });

                    try {
                        const response = await fetch("{{ route('dashboard.web_curator.menus.update') }}", {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                entity_id: {{ $entity_id ?? 'null' }},
                                categories: this.categories
                            })
                        });

                        if (!response.ok) {
                            const err = await response.json().catch(() => ({}));
                            throw new Error(err.message || "Failed to save menu changes.");
                        }

                        const data = await response.json();
                        this.loading = false;

                        if (data.data && Array.isArray(data.data)) {
                            data.data.forEach((savedCat, index) => {
                                if (this.categories[index]) {
                                    if (savedCat.id) {
                                        this.categories[index].id = savedCat.id;
                                        delete this.categories[index].temp_id;
                                    }

                                    if (savedCat.subcategories && Array.isArray(savedCat.subcategories)) {
                                        savedCat.subcategories.forEach((savedSub, subIndex) => {
                                            if (this.categories[index].subcategories[subIndex] && savedSub.id) {
                                                this.categories[index].subcategories[subIndex].id = savedSub.id;
                                                delete this.categories[index].subcategories[subIndex].temp_id;
                                            }
                                        });
                                    }
                                }
                            });
                        }

                        window.toastNotifier.show({
                            message: options.successMessage || data.message || 'Menu updated successfully!',
                            type: 'success'
                        });

                        return true;
                    } catch (error) {
                        this.loading = false;
                        console.error('Error saving menu:', error);
                        window.toastNotifier.show({
                            message: error.message || 'Error saving menu changes. Please try again.',
                            type: 'error'
                        });
                        return false;
                    }
                },

                dragCategoryIndex: null,
                dragSubcategory: null,


                dropCategory(toIndex) {
                    const fromIndex = this.dragCategoryIndex;
                    if (fromIndex === null || fromIndex === toIndex) return;

                    const moved = this.categories.splice(fromIndex, 1)[0];
                    this.categories.splice(toIndex, 0, moved);
                    this.dragCategoryIndex = null;
                    this.saveToHistory();
                },

                dropSubcategory(category, toIndex) {
                    if (!this.dragSubcategory) return;

                    const {
                        category: fromCategory,
                        subIndex
                    } = this.dragSubcategory;

                    const moved = fromCategory.subcategories.splice(subIndex, 1)[0];
                    category.subcategories.splice(toIndex, 0, moved);

                    this.dragSubcategory = null;
                    this.saveToHistory();
                },

            }
        }
    </script>
@endsection
