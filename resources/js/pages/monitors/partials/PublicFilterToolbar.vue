<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Stats {
    total_public: number;
    up: number;
    down: number;
}

interface ActivePill {
    key: string;
    label: string;
    clear: () => void;
}

const props = defineProps<{
    searchQuery: string;
    statusFilter: string;
    tagFilter: string;
    sortBy: string;
    stats: Stats;
    availableTags?: Array<{ id: number; name: { en: string } }>;
    sortOptions: Array<{ value: string; label: string }>;
    showingText: string;
    activePills: ActivePill[];
}>();

const emit = defineEmits<{
    (e: 'update:searchQuery', val: string): void;
    (e: 'update:statusFilter', val: string): void;
    (e: 'update:tagFilter', val: string): void;
    (e: 'update:sortBy', val: string): void;
    (e: 'filterByStatus', status: string): void;
    (e: 'debounceSearch'): void;
    (e: 'clearSearch'): void;
    (e: 'resetFilters'): void;
    (e: 'applyFilters'): void;
}>();

const searchInputRef = ref<HTMLInputElement | null>(null);

const statusTabs = computed(() => [
    {
        key: 'all',
        label: 'All',
        count: props.stats.total_public,
        activeClass: 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white',
        inactiveClass: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
    },
    {
        key: 'up',
        label: 'Online',
        count: props.stats.up,
        activeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        inactiveClass: 'text-gray-500 hover:text-emerald-600 dark:text-gray-400',
    },
    {
        key: 'down',
        label: 'Down',
        count: props.stats.down,
        activeClass: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
        inactiveClass: 'text-gray-500 hover:text-rose-600 dark:text-gray-400',
    },
]);

function handleKeyDown(e: KeyboardEvent) {
    if (
        e.key === '/' &&
        document.activeElement !== searchInputRef.value &&
        (document.activeElement as HTMLElement)?.tagName !== 'INPUT' &&
        (document.activeElement as HTMLElement)?.tagName !== 'TEXTAREA'
    ) {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    focusSearch: () => searchInputRef.value?.focus(),
});
</script>

<template>
    <!-- Integrated Search & Filter Toolbar: Single Rounded Pill Container -->
    <div class="mb-2 rounded-2xl border border-gray-200/80 bg-white p-1.5 shadow-xs sm:rounded-full dark:border-gray-800/80 dark:bg-gray-900/90">
        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <!-- Search Input with Keyboard Shortcut Indicator -->
            <div class="relative flex min-w-[200px] flex-1 items-center">
                <label for="search-monitors" class="sr-only">Search monitors</label>
                <Icon name="search" class="pointer-events-none absolute left-3.5 h-3.5 w-3.5 text-gray-400" />
                <input
                    ref="searchInputRef"
                    id="search-monitors"
                    :value="searchQuery"
                    type="text"
                    placeholder="Search public monitors (e.g. google, api, blog)..."
                    class="w-full rounded-full border-0 bg-transparent py-1.5 pr-9 pl-9 text-xs text-gray-900 placeholder-gray-400 focus:ring-0 focus:outline-none dark:text-white dark:placeholder-gray-500"
                    @input="
                        emit('update:searchQuery', ($event.target as HTMLInputElement).value);
                        emit('debounceSearch');
                    "
                />
                <div class="absolute right-2.5 flex items-center">
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="emit('clearSearch')"
                        class="cursor-pointer rounded-full p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        aria-label="Clear search"
                    >
                        <Icon name="x" class="h-3 w-3" />
                    </button>
                    <kbd
                        v-else
                        class="py-0.2 hidden items-center rounded border border-gray-200 bg-gray-100 px-1.5 font-mono text-[9px] text-gray-400 sm:inline-flex dark:border-gray-700 dark:bg-gray-800"
                    >
                        /
                    </kbd>
                </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-1">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    @click="emit('filterByStatus', tab.key)"
                    class="cursor-pointer rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                    :class="statusFilter === tab.key ? tab.activeClass : tab.inactiveClass"
                >
                    {{ tab.label }} ({{ tab.count }})
                </button>
            </div>

            <!-- Dropdowns: Sort and Tags -->
            <div class="flex items-center gap-1.5">
                <!-- Sort Pill Dropdown -->
                <div class="relative inline-flex items-center">
                    <label for="sort-by" class="sr-only">Sort by</label>
                    <select
                        id="sort-by"
                        :value="sortBy"
                        @change="
                            emit('update:sortBy', ($event.target as HTMLSelectElement).value);
                            emit('applyFilters');
                        "
                        class="cursor-pointer appearance-none rounded-full bg-gray-100/90 py-1.5 pr-7 pl-3 text-xs font-medium text-gray-700 focus:outline-none dark:bg-gray-800/90 dark:text-gray-200"
                    >
                        <option v-for="o in sortOptions" :key="o.value" :value="o.value">Sort: {{ o.label }}</option>
                    </select>
                    <Icon name="chevronDown" class="pointer-events-none absolute right-2.5 h-3 w-3 text-gray-500" />
                </div>

                <!-- Tags Pill Dropdown -->
                <div class="relative inline-flex items-center">
                    <label for="tag-filter" class="sr-only">Filter by tag</label>
                    <select
                        id="tag-filter"
                        :value="tagFilter"
                        @change="
                            emit('update:tagFilter', ($event.target as HTMLSelectElement).value);
                            emit('applyFilters');
                        "
                        class="cursor-pointer appearance-none rounded-full bg-gray-100/90 py-1.5 pr-7 pl-3 text-xs font-medium text-gray-700 focus:outline-none dark:bg-gray-800/90 dark:text-gray-200"
                    >
                        <option value="">All Tags</option>
                        <option v-for="tag in availableTags" :key="tag.id" :value="tag.name.en">#{{ tag.name.en }}</option>
                    </select>
                    <Icon name="chevronDown" class="pointer-events-none absolute right-2.5 h-3 w-3 text-gray-500" />
                </div>

                <!-- Add Button -->
                <button
                    @click="router.visit('/monitor/create')"
                    class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1 rounded-full bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95"
                >
                    <Icon name="plus" class="h-3 w-3" />
                    <span>Add</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Left-aligned Monitors Count Text -->
    <div class="mb-4 flex items-center justify-between px-2 text-xs font-medium text-gray-400 dark:text-gray-500">
        <span>{{ showingText }}</span>

        <!-- Active filter reset if any -->
        <div v-if="activePills.length" class="flex items-center gap-1.5">
            <span
                v-for="pill in activePills"
                :key="pill.key"
                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
            >
                {{ pill.label }}
                <button type="button" @click="pill.clear()" class="cursor-pointer hover:text-blue-900" :aria-label="`Remove ${pill.key} filter`">
                    <Icon name="x" class="h-2.5 w-2.5" />
                </button>
            </span>
            <button type="button" @click="emit('resetFilters')" class="cursor-pointer text-[11px] text-blue-600 hover:underline dark:text-blue-400">
                Reset all
            </button>
        </div>
    </div>
</template>
