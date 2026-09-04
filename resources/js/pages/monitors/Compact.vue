<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Input from '@/components/ui/input/Input.vue';
import type { Monitor, Tag } from '@/types/monitor';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import CreateMonitorModal from '../uptime/partials/CreateMonitorModal.vue';
import DetailMonitorModal from '../uptime/partials/DetailMonitorModal.vue';
import EditMonitorModal from '../uptime/partials/EditMonitorModal.vue';
import ImportMonitorModal from '../uptime/partials/ImportMonitorModal.vue';
import CompactBars from './partials/CompactBars.vue';
import CompactCards from './partials/CompactCards.vue';
import CompactDots from './partials/CompactDots.vue';
import CompactTable from './partials/CompactTable.vue';

const props = defineProps<{
    monitors: { data: Monitor[] };
    availableTags: Tag[];
    currentSort: string;
    currentDirection: string;
    currentView?: string | null;
    currentGroup?: string | null;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!(page.props as any).auth?.user);

// Modal state
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const isImportModalOpen = ref(false);

const monitorToEdit = ref<Monitor | null>(null);
const monitorToView = ref<Monitor | null>(null);

const openEditModal = (monitor: Monitor) => {
    monitorToEdit.value = monitor;
    isEditModalOpen.value = true;
};

const openDetailModal = (monitor: Monitor) => {
    monitorToView.value = monitor;
    isDetailModalOpen.value = true;
};

// URL query parameter helper
const getInitialParam = (param: string, storageKey: string, defaultValue: string, validValues: string[]): string => {
    if (typeof window === 'undefined') return defaultValue;
    const urlParam = new URLSearchParams(window.location.search).get(param);
    if (urlParam && validValues.includes(urlParam)) {
        localStorage.setItem(storageKey, urlParam);
        return urlParam;
    }
    const stored = localStorage.getItem(storageKey);
    if (stored && validValues.includes(stored)) {
        return stored;
    }
    return defaultValue;
};

// View State
const viewType = ref(props.currentView || getInitialParam('view', 'compact_view_type', 'dots', ['dots', 'table', 'bars', 'cards']));
const groupBy = ref(props.currentGroup || getInitialParam('group', 'compact_group_by', 'status', ['status', 'tags', 'none']));
const searchQuery = ref(typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('search') || '' : '');

const syncUrlParams = () => {
    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    if (viewType.value && viewType.value !== 'dots') {
        url.searchParams.set('view', viewType.value);
    } else {
        url.searchParams.delete('view');
    }
    if (groupBy.value && groupBy.value !== 'status') {
        url.searchParams.set('group', groupBy.value);
    } else {
        url.searchParams.delete('group');
    }
    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value);
    } else {
        url.searchParams.delete('search');
    }
    if (sortBy.value && sortBy.value !== 'url') {
        url.searchParams.set('sort', sortBy.value);
    } else {
        url.searchParams.delete('sort');
    }
    if (direction.value && direction.value !== 'asc') {
        url.searchParams.set('direction', direction.value);
    } else {
        url.searchParams.delete('direction');
    }
    window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
};

const setViewType = (type: string) => {
    viewType.value = type;
    localStorage.setItem('compact_view_type', type);
    syncUrlParams();
};

const setGroupBy = (group: string) => {
    groupBy.value = group;
    localStorage.setItem('compact_group_by', group);
    syncUrlParams();
};

// Sort logic
const sortBy = ref(props.currentSort);
const direction = ref(props.currentDirection);

const handleSort = (key: string) => {
    if (sortBy.value === key) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = key;
        direction.value = 'asc';
    }

    updateData();
};

const toggleDirection = () => {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    updateData();
};

const updateData = () => {
    syncUrlParams();
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '/monitors';
    router.get(
        pathname,
        {
            search: searchQuery.value,
            sort: sortBy.value,
            direction: direction.value,
            view: viewType.value,
            group: groupBy.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['monitors', 'availableTags', 'currentSort', 'currentDirection', 'currentView', 'currentGroup'],
        },
    );
};

// Refresh logic
const countdown = ref(60);
let timer: number | null = null;

const startTimer = () => {
    timer = window.setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            router.reload({
                only: ['monitors', 'availableTags'],
                data: {
                    search: searchQuery.value,
                    sort: sortBy.value,
                    direction: direction.value,
                },
            });
            countdown.value = 60;
        }
    }, 1000);
};

onMounted(() => startTimer());
onUnmounted(() => {
    if (timer) clearInterval(timer);
    if (searchTimeout) clearTimeout(searchTimeout);
});

// Server-side searching
let searchTimeout: number | null = null;
const handleSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        updateData();
    }, 500);
};

watch(searchQuery, () => {
    handleSearch();
});

// Grouping
const groups = computed(() => {
    const data = props.monitors.data;
    if (groupBy.value === 'status') {
        return [
            { name: 'Down', monitors: data.filter((m) => m.uptime_status === 'down'), color: 'text-red-500' },
            { name: 'Up', monitors: data.filter((m) => m.uptime_status === 'up'), color: 'text-green-500' },
            { name: 'Other', monitors: data.filter((m) => m.uptime_status !== 'up' && m.uptime_status !== 'down'), color: 'text-yellow-500' },
        ].filter((g) => g.monitors.length > 0);
    }

    if (groupBy.value === 'tags') {
        const tagGroups = props.availableTags
            .map((tag) => ({
                name: tag.name,
                monitors: data.filter((m) => m.tags?.some((t) => t.name === tag.name)),
                color: 'text-blue-500',
            }))
            .filter((g) => g.monitors.length > 0);

        const noTagMonitors = data.filter((m) => !m.tags || m.tags.length === 0);
        if (noTagMonitors.length > 0) {
            tagGroups.push({ name: 'No Tag', monitors: noTagMonitors, color: 'text-gray-500' });
        }
        return tagGroups;
    }

    return [{ name: 'All Monitors', monitors: data, color: 'text-gray-900 dark:text-gray-100' }];
});

const sortLabels: Record<string, string> = {
    url: 'URL/Name',
    uptime_status: 'Status',
    today_uptime_percentage: 'Today Uptime',
    avg_response_time_24h: 'Avg Response',
    last_checked: 'Last Checked',
};
</script>

<template>
    <PublicLayout
        title="Status Wallboard"
        description="High-density multi-view monitor wallboard"
        share-url="/monitors"
        share-text="Check out the Uptime Kita Status Wallboard"
        container-class="max-w-[1920px]"
    >
        <template #header-left>
            <div class="flex items-center gap-3">
                <Link
                    :href="isAuthenticated ? '/dashboard' : '/'"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200/80 bg-white p-2 text-gray-600 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <Icon name="arrowLeft" class="h-4 w-4" />
                </Link>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="truncate text-base font-extrabold tracking-tight text-gray-900 uppercase sm:text-xl dark:text-white">
                            Status Wallboard
                        </h1>
                        <span
                            class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-600 uppercase dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            {{ monitors.data.length }} Monitors
                        </span>
                    </div>
                    <p class="truncate text-xs font-semibold text-emerald-600 dark:text-emerald-400">Auto-refresh in {{ countdown }}s</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-[1920px]">
            <!-- Wallboard Controls Toolbar -->
            <div
                class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-gray-200/80 bg-white/80 p-4 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
            >
                <div class="flex min-w-[280px] flex-1 flex-wrap items-center gap-2.5">
                    <!-- Search -->
                    <div class="relative max-w-xs flex-1">
                        <Input
                            v-model="searchQuery"
                            placeholder="FILTER MONITORS..."
                            class="h-9 rounded-xl border-gray-200/80 bg-gray-50/50 px-8 text-[11px] font-bold tracking-wider uppercase backdrop-blur-sm dark:border-gray-800 dark:bg-gray-800/50"
                        />
                        <Icon name="search" class="absolute top-1/2 left-2.5 -translate-y-1/2 text-gray-400" size="14" />
                    </div>

                    <!-- Sort Controls -->
                    <div
                        class="flex items-center gap-1 rounded-xl border border-gray-200/80 bg-gray-100/80 p-1 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    class="h-7 gap-2 rounded-lg px-2 text-[10px] font-bold tracking-widest uppercase hover:bg-white dark:hover:bg-gray-800"
                                >
                                    <Icon name="sortAsc" size="12" />
                                    <span class="hidden sm:inline">SORT:</span> {{ sortLabels[sortBy] }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48 rounded-xl border border-gray-200/80 dark:border-gray-800">
                                <DropdownMenuItem
                                    v-for="(label, key) in sortLabels"
                                    :key="key"
                                    @click="handleSort(key)"
                                    class="text-[10px] font-bold tracking-widest uppercase"
                                >
                                    {{ label }}
                                    <Icon v-if="sortBy === key" name="check" class="ml-auto" size="12" />
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button variant="ghost" size="icon" class="h-7 w-7 rounded-lg hover:bg-white dark:hover:bg-gray-800" @click="toggleDirection">
                            <Icon :name="direction === 'asc' ? 'arrowUp' : 'arrowDown'" size="14" />
                        </Button>
                    </div>

                    <!-- View Switcher -->
                    <div class="flex rounded-xl border border-gray-200/80 bg-gray-100/80 p-1 dark:border-gray-800 dark:bg-gray-900">
                        <button
                            v-for="type in ['dots', 'table', 'bars', 'cards']"
                            :key="type"
                            @click="setViewType(type)"
                            :class="[
                                'flex h-7 w-9 items-center justify-center rounded-lg transition-all',
                                viewType === type
                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-400'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                            :title="type.toUpperCase() + ' VIEW'"
                        >
                            <Icon
                                :name="type === 'dots' ? 'layoutGrid' : type === 'table' ? 'list' : type === 'bars' ? 'columns' : 'grid'"
                                size="16"
                            />
                        </button>
                    </div>

                    <!-- Group Switcher -->
                    <div class="flex rounded-xl border border-gray-200/80 bg-gray-100/80 p-1 dark:border-gray-800 dark:bg-gray-900">
                        <button
                            v-for="group in ['status', 'tags', 'none']"
                            :key="group"
                            @click="setGroupBy(group)"
                            :class="[
                                'h-7 rounded-lg px-3 text-[10px] font-bold tracking-widest uppercase transition-all',
                                groupBy === group
                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-400'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                            ]"
                        >
                            {{ group }}
                        </button>
                    </div>

                    <Link
                        v-if="!isAuthenticated"
                        href="/login"
                        class="flex h-9 items-center rounded-xl bg-blue-600 px-4 text-[10px] font-bold tracking-widest text-white uppercase shadow-sm transition-all hover:bg-blue-700 active:scale-95"
                    >
                        LOGIN
                    </Link>

                    <template v-if="isAuthenticated">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="h-9 rounded-xl border-gray-200/80 px-4 text-[10px] font-bold tracking-widest uppercase dark:border-gray-800"
                                >
                                    <Icon name="download" class="mr-2" size="14" /> EXPORT
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40 rounded-xl border border-gray-200/80 dark:border-gray-800">
                                <DropdownMenuItem as="a" href="/monitors/export/csv" class="text-[10px] font-bold tracking-widest uppercase">
                                    <Icon name="fileText" class="mr-2" size="12" /> CSV
                                </DropdownMenuItem>
                                <DropdownMenuItem as="a" href="/monitors/export/json" class="text-[10px] font-bold tracking-widest uppercase">
                                    <Icon name="fileJson" class="mr-2" size="12" /> JSON
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant="outline"
                            @click="isImportModalOpen = true"
                            class="h-9 rounded-xl border-gray-200/80 px-4 text-[10px] font-bold tracking-widest uppercase dark:border-gray-800"
                        >
                            <Icon name="upload" class="mr-2" size="14" /> IMPORT
                        </Button>

                        <Button
                            @click="isCreateModalOpen = true"
                            class="h-9 rounded-xl bg-blue-600 px-4 text-[10px] font-bold tracking-widest text-white uppercase shadow-sm transition-all hover:bg-blue-700 active:scale-95"
                        >
                            ADD MONITOR
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="space-y-12">
                <div v-for="group in groups" :key="group.name" class="animate-in fade-in slide-in-from-top-2 duration-500">
                    <div class="mb-4 flex items-center gap-3">
                        <h2 :class="['text-[10px] font-black tracking-[0.2em] uppercase', group.color]">
                            {{ group.name }}
                            <span class="ml-2 font-bold text-gray-500">[{{ group.monitors.length }}]</span>
                        </h2>
                        <div class="h-px flex-1 bg-gray-100 dark:bg-gray-900/50"></div>
                    </div>

                    <component
                        :is="
                            viewType === 'dots' ? CompactDots : viewType === 'table' ? CompactTable : viewType === 'bars' ? CompactBars : CompactCards
                        "
                        :monitors="group.monitors"
                        :can-edit="isAuthenticated"
                        @view="openDetailModal"
                        @edit="openEditModal"
                    />
                </div>

                <div v-if="monitors.data.length === 0" class="flex flex-col items-center justify-center py-32 text-center">
                    <Icon name="searchX" size="64" class="mb-4 text-gray-200 dark:text-gray-800" />
                    <h3 class="text-xs font-black tracking-[0.3em] text-gray-400 uppercase dark:text-gray-600">No matching monitors found</h3>
                    <Button
                        variant="outline"
                        class="mt-6 border-gray-200 text-[10px] font-bold tracking-widest uppercase dark:border-gray-800"
                        @click="searchQuery = ''"
                    >
                        RESET FILTER
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CreateMonitorModal v-model:open="isCreateModalOpen" />
        <EditMonitorModal v-model:open="isEditModalOpen" :monitor="monitorToEdit" />
        <DetailMonitorModal v-model:open="isDetailModalOpen" :monitor="monitorToView" @edit="openEditModal" />
        <ImportMonitorModal v-model:open="isImportModalOpen" />
    </PublicLayout>
</template>
