@props(['subcategory', 'index'])

<div 
    class="border border-gray-200 rounded p-3 bg-gray-50 shadow-sm transition-all duration-300 hover:bg-gray-100"
    x-data="subcategoryBox(@js($subcategory))"
    x-transition
>
    <div class="flex flex-wrap items-center gap-2 mb-2">
        {{-- Drag Handle --}}
        <span class="cursor-move text-gray-300 text-xl hover:text-gray-500 select-none">≡</span>

        {{-- Subcategory Name --}}
        <input 
            type="text"
            class="form-control w-1/4"
            x-model="subcategory_name"
            placeholder="Subcategory Name"
            @input="generateSlug"
        >

        {{-- Slug --}}
        <input 
            type="text"
            class="form-control w-1/4"
            x-model="subcategory_slug"
            placeholder="Slug"
            @input="slug_overridden = true"
        >

        {{-- Is Menu Toggle --}}
        <div class="flex items-center space-x-2">
            <span class="text-gray-700">Menu?</span>
            <input type="checkbox" x-model="is_menu" class="toggle toggle-success">
        </div>

        {{-- Delete Subcategory --}}
        <button 
            type="button"
            class="btn btn-outline-danger btn-sm ml-auto"
            @click="$dispatch('delete-subcategory', {{ $index }})"
            x-tooltip="'Delete Subcategory'"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>

    {{-- Menu fields (animated collapse) --}}
    <div 
        x-show="is_menu"
        x-collapse
        class="flex flex-wrap gap-2"
    >
        <input 
            type="text"
            class="form-control w-1/4"
            x-model="menu_text"
            placeholder="Menu Text"
        >
        <input 
            type="number"
            class="form-control w-1/6"
            x-model="menu_order"
            placeholder="Menu Order"
        >
        <input 
            type="text"
            class="form-control flex-1"
            x-model="link_url"
            placeholder="Link URL (optional)"
        >
    </div>
</div>

<script>
    function subcategoryBox(data) {
        return {
            ...data,
            generateSlug() {
                if (!this.slug_overridden) {
                    this.subcategory_slug = this.subcategory_name
                        .toLowerCase()
                        .replace(/\s+/g, '-')
                        .replace(/[^\w\-]+/g, '');
                }
            },
            slug_overridden: false,
        }
    }
</script>
