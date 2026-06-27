<div 
    class="border border-gray-300 rounded-lg p-4 mb-4 bg-white shadow-sm transition-all duration-300 hover:shadow-md"
    x-data="categoryBox(@js($category))"
    x-transition
>
    {{-- HEADER ROW --}}
    <div class="flex flex-wrap items-center gap-2 mb-3">
        {{-- Drag Handle --}}
        <span class="cursor-move text-gray-400 text-xl hover:text-gray-600 select-none">≡</span>

        {{-- Category Name --}}
        <input 
            type="text"
            class="form-control w-1/4"
            x-model="category_name"
            placeholder="Category Name"
            @input="generateSlug"
        >

        {{-- Slug --}}
        <input 
            type="text"
            class="form-control w-1/4"
            x-model="category_slug"
            placeholder="Slug"
            @input="slug_overridden = true"
        >

        {{-- Is Menu Toggle --}}
        <div class="flex items-center space-x-2">
            <span class="text-gray-700">Menu?</span>
            <input type="checkbox" x-model="is_menu" class="toggle toggle-success">
        </div>

        {{-- Delete Category --}}
        <button 
            type="button"
            class="btn btn-outline-danger btn-sm ml-auto"
            @click="$dispatch('delete-category', {{ $index }})"
            x-tooltip="'Delete Category'"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>

    {{-- MENU FIELDS (animated collapse) --}}
    <div 
        x-show="is_menu"
        x-collapse
        class="flex flex-wrap gap-2 mb-4"
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

    {{-- ADD SUBCATEGORY BUTTON --}}
    <button 
        type="button"
        class="btn btn-primary btn-sm mb-2 transition-all duration-300 hover:scale-105"
        @click="addSubcategory"
    >
        <i class="bi bi-plus-circle"></i> Add Subcategory
    </button>

    {{-- SUBCATEGORIES --}}
    <div class="ml-8 space-y-3">
        <template x-for="(subcat, subIndex) in subcategories" :key="subcat.temp_id || subcat.id">
            <div
                class="border border-gray-200 rounded p-3 bg-gray-50 shadow-sm transition-all duration-300 hover:bg-gray-100"
                x-data="subcategoryBox(subcat)"
                x-transition
            >
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="cursor-move text-gray-300 text-xl hover:text-gray-500 select-none">≡</span>

                    <input
                        type="text"
                        class="form-control w-1/4"
                        x-model="subcategory_name"
                        placeholder="Subcategory Name"
                        @input="generateSlug"
                    >

                    <input
                        type="text"
                        class="form-control w-1/4"
                        x-model="subcategory_slug"
                        placeholder="Slug"
                        @input="slug_overridden = true"
                    >

                    <div class="flex items-center space-x-2">
                        <span class="text-gray-700">Menu?</span>
                        <input type="checkbox" x-model="is_menu" class="toggle toggle-success">
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm ml-auto"
                        @click="$dispatch('delete-subcategory', subIndex)"
                        x-tooltip="'Delete Subcategory'"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

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
        </template>
    </div>
</div>

<script>
    function categoryBox(data) {
        return {
            ...data,
            generateSlug() {
                if (!this.slug_overridden) {
                    this.category_slug = this.category_name
                        .toLowerCase()
                        .replace(/\s+/g, '-')
                        .replace(/[^\w\-]+/g, '');
                }
            },
            slug_overridden: false,
            subcategories: data.subcategories || [],
            addSubcategory() {
                this.subcategories.push({
                    id: null,
                    subcategory_name: '',
                    subcategory_slug: '',
                    is_menu: false,
                    menu_text: '',
                    menu_order: 0,
                    link_url: '',
                    temp_id: Date.now()
                });
            }
        }
    }

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
