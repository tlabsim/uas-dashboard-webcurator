@props([
    'entities' => [],
    'initialSelectedEntities' => [],
    'postId' => null,
    'saveUrl' => null,
    'editable' => true,
])

@php
    $normalizedEntities = collect($entities)
        ->map(function ($entity) {
            $entity = is_array($entity) ? (object) $entity : $entity;

            return [
                'value' => (string) ($entity->id ?? ''),
                'label' => (string) ($entity->display_name ?? $entity->entity_name ?? $entity->name ?? 'Unknown Entity'),
                'type' => (string) ($entity->entity_type ?? 'Entity'),
            ];
        })
        ->filter(fn ($entity) => $entity['value'] !== '')
        ->values();

    $normalizedInitialSelectedEntities = collect($initialSelectedEntities)
        ->map(function ($taggedEntity) {
            if (is_numeric($taggedEntity)) {
                return [
                    'id' => (int) $taggedEntity,
                    'tag_id' => null,
                    'status' => 'Pending',
                    'approved_by' => null,
                ];
            }

            $taggedEntity = is_array($taggedEntity) ? $taggedEntity : (array) $taggedEntity;

            return [
                'id' => (int) ($taggedEntity['id'] ?? 0),
                'tag_id' => isset($taggedEntity['tag_id']) ? (int) $taggedEntity['tag_id'] : null,
                'status' => (string) ($taggedEntity['status'] ?? 'Pending'),
                'approved_by' => $taggedEntity['approved_by'] ?? null,
            ];
        })
        ->filter(fn ($taggedEntity) => $taggedEntity['id'] > 0)
        ->values();

    $canEdit = (bool) $editable && !empty($saveUrl);
@endphp

<div class="card mt-6"
     x-data="taggedEntityEditor({
        entities: @js($normalizedEntities),
        initialSelectedEntities: @js($normalizedInitialSelectedEntities),
        editable: @js($canEdit),
     })"
     @tag-entity-picked="addEntity($event.detail.value)">
    <div class="card-header border-b border-slate-200 pb-4">
        <div class="flex flex-col gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="card-title">Tagged Entities</h3>
                    <span class="text-xs px-2 py-0.5 badge-tint badge-tint-green" x-text="selectedEntityCards.length === 1 ? '1 tagged' : `${selectedEntityCards.length} tagged`"></span>
                </div>
                <p class="mt-1 text-xs text-slate-600">                    
                    Use this to request republication by other entities. Subject to approval by the target entity.
                </p>
            </div>

            @if(!$canEdit)
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <svg width="20" height="20" viewBox="0 0 32 32"><path fill="currentColor" d="M16 13a1 1 0 0 1 1 1v9a1 1 0 1 1-2 0v-9a1 1 0 0 1 1-1m0-2a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3M2 16C2 8.268 8.268 2 16 2s14 6.268 14 14s-6.268 14-14 14S2 23.732 2 16M16 4C9.373 4 4 9.373 4 16s5.373 12 12 12s12-5.373 12-12S22.627 4 16 4"/></svg>
                    Create the post first. After that, tagged entities can be managed independently from the main post content.
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="space-y-2">
            <label class="label-base">Add Entity</label>
            <div class="flex items-stretch gap-3">
                <div class="min-w-0 flex-1"
                     x-data="customSelect({
                        options: entityOptions,
                        value: '',
                        name: '',
                        placeholder: editable ? 'Search and select an entity' : 'Available after the post is created',
                        required: false,
                        editable: false,
                        disabled: !editable,
                        size: 'base',
                        searchThreshold: 8
                     })"
                     @select-change="pendingEntityId = $event.detail.value; selectedValue = ''">
                    <x-custom-select-template />
                </div>

                <button type="button"
                        @click="if (editable && pendingEntityId) { addEntity(pendingEntityId); }"
                        :disabled="!editable || !pendingEntityId"
                        class="btn-base btn-primary gap-2 whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add</span>
                </button>
            </div>
        </div>

        <div x-show="selectedEntityCards.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
            No entities tagged yet.
        </div>

        <div x-show="selectedEntityCards.length > 0" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <template x-for="entity in selectedEntityCards" :key="entity.id">
                <div class="border-b border-slate-500/20 px-4 py-4 last:border-b-0">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-3">
                                <div class="mt-1 shrink-0 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <g>
                                            <circle cx="1.5" cy="1.5" r="1.5" stroke-linecap="round" stroke-linejoin="round" transform="matrix(1 0 0 -1 16 8)"/>
                                            <path d="M2.774 11.144c-1.003 1.12-1.024 2.81-.104 4a34 34 0 0 0 6.186 6.186c1.19.92 2.88.899 4-.104a92 92 0 0 0 8.516-8.698a1.95 1.95 0 0 0 .47-1.094c.164-1.796.503-6.97-.902-8.374s-6.578-1.066-8.374-.901a1.95 1.95 0 0 0-1.094.47a92 92 0 0 0-8.698 8.515Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m7 14l3 3"/>
                                        </g>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="min-w-0 truncate text-sm font-semibold text-slate-900 sm:text-base" x-text="entity.name"></h4>
                                        <span class="text-xs px-2 py-0.5 rounded-md bg-blue-200 text-blue-700" x-text="entity.type"></span>
                                        <span class="text-xs px-2 py-0.5 rounded-md bg-yellow-200 text-yellow-700" :class="statusBadgeClass(entity.status)" x-text="entity.status"></span>
                                    </div>

                                    <div class="mt-2 text-sm text-slate-500">
                                        <p class="text-sm" x-show="entity.approved_by">
                                            Approved by <span x-text="entity.approved_by"></span>
                                        </p>
                                        <p class="text-sm" x-show="!entity.approved_by && entity.status === 'Pending'">
                                            Waiting for review by the target entity.
                                        </p>
                                        <p class="text-sm" x-show="entity.status === 'Denied'">
                                            Denied earlier. Keep it tagged if you want it available for later approval.
                                        </p>
                                        <p class="text-sm" x-show="entity.status === 'Withdrawn'">
                                            Approval was withdrawn earlier. It can be approved again later.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0">
                            <button type="button"
                                    @click="removeEntity(entity.id)"
                                    :disabled="!editable"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    @if($saveUrl)
        <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <!-- <p class="text-sm text-slate-500">
                This saves only the tagged entities for this post.
            </p> -->

            <form method="POST" action="{{ $saveUrl }}">
                <button type="button"
                        @click="saveTaggedEntities()"
                        :disabled="isSaving"
                        class="btn-base btn-primary gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span x-text="isSaving ? 'Saving...' : 'Tag Selected Entities'"></span>
                </button>
            </form>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('taggedEntityEditor', ({ entities = [], initialSelectedEntities = [], editable = true } = {}) => ({
                editable,
                entityOptions: entities || [],
                selectedEntityState: {},
                pendingEntityId: '',
                isSaving: false,

                init() {
                    (initialSelectedEntities || []).forEach((taggedEntity) => {
                        const entityId = Number(taggedEntity.id || 0);
                        if (entityId > 0) {
                            this.selectedEntityState[entityId] = {
                                id: entityId,
                                tagId: taggedEntity.tag_id || null,
                                status: taggedEntity.status || 'Pending',
                                approved_by: taggedEntity.approved_by || null,
                            };
                        }
                    });
                },

                addEntity(entityId) {
                    if (!this.editable) return;

                    const normalizedId = Number(entityId);
                    if (normalizedId <= 0 || this.isSelected(normalizedId)) {
                        return;
                    }

                    this.selectedEntityState[normalizedId] = {
                        id: normalizedId,
                        tagId: this.selectedEntityState[normalizedId]?.tagId || null,
                        status: this.selectedEntityState[normalizedId]?.status || 'Pending',
                        approved_by: this.selectedEntityState[normalizedId]?.approved_by || null,
                    };

                    this.pendingEntityId = '';
                },

                removeEntity(entityId) {
                    if (!this.editable) return;
                    delete this.selectedEntityState[Number(entityId)];
                },

                async saveTaggedEntities() {
                    if (!this.editable || this.isSaving) return;

                    const saveUrl = @js($saveUrl);
                    if (!saveUrl) return;

                    this.isSaving = true;

                    try {
                        const formData = new FormData();
                        formData.append('_method', 'PUT');

                        this.selectedEntityIds.forEach((entityId) => {
                            formData.append('tagged_entities[]', entityId);
                        });

                        const response = await fetch(saveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Failed to save tagged entities.');
                        }

                        const toastType = payload.success === false ? 'warning' : 'success';
                        window.showToast?.(
                            payload.message || 'Tagged entities updated successfully.',
                            toastType,
                            { duration: payload.success === false ? 5000 : 3000 }
                        );
                    } catch (error) {
                        window.showToast?.(
                            error.message || 'Failed to save tagged entities.',
                            'error',
                            { duration: 5000 }
                        );
                    } finally {
                        this.isSaving = false;
                    }
                },

                isSelected(entityId) {
                    return Object.prototype.hasOwnProperty.call(this.selectedEntityState, Number(entityId));
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'Approved':
                            return 'badge-green';
                        case 'Denied':
                            return 'badge-red';
                        case 'Withdrawn':
                            return 'badge-gray';
                        default:
                            return 'badge-yellow';
                    }
                },

                get selectedEntityIds() {
                    return Object.keys(this.selectedEntityState)
                        .map((entityId) => Number(entityId))
                        .sort((a, b) => a - b);
                },

                get selectedEntityCards() {
                    return this.selectedEntityIds.map((entityId) => {
                        const entity = this.entityOptions.find((item) => Number(item.value) === entityId);
                        const state = this.selectedEntityState[entityId] || {};

                        return {
                            id: entityId,
                            name: entity?.label || 'Unknown Entity',
                            type: entity?.type || 'Entity',
                            status: state.status || 'Pending',
                            approved_by: state.approved_by || null,
                        };
                    });
                },
            }));
        });
        </script>
    @endpush
@endonce
