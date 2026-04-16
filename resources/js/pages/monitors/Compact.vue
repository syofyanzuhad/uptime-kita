<script setup lang="ts">
import WallboardLayout from '@/layouts/WallboardLayout.vue';
import type { Monitor, Tag } from '@/types/monitor';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import CompactDots from './partials/CompactDots.vue';
import CompactTable from './partials/CompactTable.vue';
import CompactBars from './partials/CompactBars.vue';
import CompactCards from './partials/CompactCards.vue';
import { debounce } from 'lodash';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import CreateMonitorModal from '../uptime/partials/CreateMonitorModal.vue';
import EditMonitorModal from '../uptime/partials/EditMonitorModal.vue';
import DetailMonitorModal from '../uptime/partials/DetailMonitorModal.vue';
import ImportMonitorModal from '../uptime/partials/ImportMonitorModal.vue';

const props = defineProps<{
    monitors: { data: Monitor[] };
    availableTags: Tag[];
    currentSort: string;
    currentDirection: string;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

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

// View State
const viewType = ref(localStorage.getItem('compact_view_type') || 'dots');
const groupBy = ref(localStorage.getItem('compact_group_by') || 'status');
const searchQuery = ref(new URLSearchParams(window.location.search).get('search') || '');

watch(viewType, (val) => localStorage.setItem('compact_view_type', val));
watch(groupBy, (val) => localStorage.setItem('compact_group_by', val));

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
    router.get(route('monitor.compact'), { 
        search: searchQuery.value,
        sort: sortBy.value,
        direction: direction.value
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['monitors', 'availableTags', 'currentSort', 'currentDirection'],
    });
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
                    direction: direction.value
                }
            });
            countdown.value = 60;
        }
    }, 1000);
};

onMounted(() => startTimer());
onUnmounted(() => timer && clearInterval(timer));

// Server-side searching
const handleSearch = debounce(() => {
    updateData();
}, 500);

watch(searchQuery, () => {
    handleSearch();
});

// Grouping
const groups = computed(() => {
    const data = props.monitors.data;
    if (groupBy.value === 'status') {
        return [
            { name: 'Down', monitors: data.filter(m => m.uptime_status === 'down'), color: 'text-red-500' },
            { name: 'Up', monitors: data.filter(m => m.uptime_status === 'up'), color: 'text-green-500' },
            { name: 'Other', monitors: data.filter(m => m.uptime_status !== 'up' && m.uptime_status !== 'down'), color: 'text-yellow-500' }
        ].filter(g => g.monitors.length > 0);
    }
    
    if (groupBy.value === 'tags') {
        const tagGroups = props.availableTags.map(tag => ({
            name: tag.name,
            monitors: data.filter(m => m.tags?.some(t => t.name === tag.name)),
            color: 'text-blue-500'
        })).filter(g => g.monitors.length > 0);
        
        const noTagMonitors = data.filter(m => !m.tags || m.tags.length === 0);
        if (noTagMonitors.length > 0) {
            tagGroups.push({ name: 'No Tag', monitors: noTagMonitors, color: 'text-gray-500' });
        }
        return tagGroups;
    }
    
    return [{ name: 'All Monitors', monitors: data, color: 'text-gray-900 dark:text-gray-100' }];
});

const sortLabels = {
    'url': 'URL/Name',
    'uptime_status': 'Status',
    'today_uptime_percentage': 'Today Uptime',
    'avg_response_time_24h': 'Avg Response',
    'last_checked': 'Last Checked'
};
</script>

<template>
    <WallboardLayout>
        <Head title="Compact Monitors Wallboard" />

        <div class="mx-auto max-w-[1920px]">
            <!-- Wallboard Controls -->
            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <Link :href="isAuthenticated ? route('dashboard') : route('home')" class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <Icon name="arrowLeft" size="16" />
                        </Link>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100 uppercase">Status Wallboard</h1>
                            <div class="flex items-center gap-3 text-[10px] text-gray-500 uppercase tracking-widest font-semibold">
                                <span>{{ monitors.data.length }} Monitors</span>
                                <span class="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                <span class="flex items-center gap-1">
                                    <Icon name="clock" size="10" />
                                    REFRESH IN {{ countdown }}S
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search -->
                    <div class="relative w-full md:w-48 lg:w-64">
                        <Input
                            v-model="searchQuery"
                            placeholder="FILTER..."
                            class="h-9 rounded-lg bg-white/50 px-8 text-[10px] font-bold uppercase tracking-widest backdrop-blur-sm dark:bg-gray-950/50"
                        />
                        <Icon name="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" size="14" />
                    </div>

                    <!-- Sort Controls -->
                    <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="h-7 gap-2 px-2 text-[10px] font-bold uppercase tracking-widest hover:bg-white dark:hover:bg-gray-800">
                                    <Icon name="sortAsc" size="12" />
                                    <span class="hidden sm:inline">SORT:</span> {{ sortLabels[sortBy] }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem v-for="(label, key) in sortLabels" :key="key" @click="handleSort(key)" class="text-[10px] font-bold uppercase tracking-widest">
                                    {{ label }}
                                    <Icon v-if="sortBy === key" name="check" class="ml-auto" size="12" />
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        
                        <Button 
                            variant="ghost" 
                            size="icon" 
                            class="h-7 w-7 hover:bg-white dark:hover:bg-gray-800"
                            @click="toggleDirection"
                        >
                            <Icon :name="direction === 'asc' ? 'arrowUp' : 'arrowDown'" size="14" />
                        </Button>
                    </div>

                    <!-- View Switcher -->
                    <div class="flex rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
                        <button
                            v-for="type in ['dots', 'table', 'bars', 'cards']"
                            :key="type"
                            @click="viewType = type"
                            :class="[
                                'flex h-7 w-9 items-center justify-center rounded-md transition-all',
                                viewType === type 
                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-400' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                            ]"
                            :title="type.toUpperCase() + ' VIEW'"
                        >
                            <Icon :name="type === 'dots' ? 'layoutGrid' : type === 'table' ? 'list' : type === 'bars' ? 'columns' : 'grid'" size="16" />
                        </button>
                    </div>

                    <!-- Group Switcher -->
                    <div class="flex rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
                        <button
                            v-for="group in ['status', 'tags', 'none']"
                            :key="group"
                            @click="groupBy = group"
                            :class="[
                                'h-7 rounded-md px-3 text-[10px] font-bold uppercase tracking-widest transition-all',
                                groupBy === group 
                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-400' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                            ]"
                        >
                            {{ group }}
                        </button>
                    </div>
                    
                    <Link
                        v-if="!isAuthenticated"
                        :href="route('login')"
                        class="h-9 rounded-lg bg-blue-600 px-4 flex items-center text-[10px] font-bold uppercase tracking-widest text-white hover:bg-blue-700 transition-colors"
                    >
                        LOGIN
                    </Link>

                    <template v-if="isAuthenticated">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="h-9 rounded-lg px-4 text-[10px] font-bold uppercase tracking-widest border-gray-200 dark:border-gray-800"
                                >
                                    <Icon name="download" class="mr-2" size="14" /> EXPORT
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                                <DropdownMenuItem as="a" :href="route('monitor.export.csv')" class="text-[10px] font-bold uppercase tracking-widest">
                                    <Icon name="fileText" class="mr-2" size="12" /> CSV
                                </DropdownMenuItem>
                                <DropdownMenuItem as="a" :href="route('monitor.export.json')" class="text-[10px] font-bold uppercase tracking-widest">
                                    <Icon name="fileJson" class="mr-2" size="12" /> JSON
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant="outline"
                            @click="isImportModalOpen = true"
                            class="h-9 rounded-lg px-4 text-[10px] font-bold uppercase tracking-widest border-gray-200 dark:border-gray-800"
                        >
                            <Icon name="upload" class="mr-2" size="14" /> IMPORT
                        </Button>

                        <Button
                            @click="isCreateModalOpen = true"
                            class="h-9 rounded-lg bg-blue-600 px-4 text-[10px] font-bold uppercase tracking-widest text-white hover:bg-blue-700 transition-colors"
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
                        <h2 :class="['text-[10px] font-black uppercase tracking-[0.2em]', group.color]">
                            {{ group.name }}
                            <span class="ml-2 text-gray-500 font-bold">[{{ group.monitors.length }}]</span>
                        </h2>
                        <div class="h-px flex-1 bg-gray-100 dark:bg-gray-900/50"></div>
                    </div>

                    <component
                        :is="viewType === 'dots' ? CompactDots : viewType === 'table' ? CompactTable : viewType === 'bars' ? CompactBars : CompactCards"
                        :monitors="group.monitors"
                        :can-edit="isAuthenticated"
                        @view="openDetailModal"
                        @edit="openEditModal"
                    />
                </div>
                
                <div v-if="monitors.data.length === 0" class="flex flex-col items-center justify-center py-32 text-center">
                    <Icon name="searchX" size="64" class="mb-4 text-gray-200 dark:text-gray-800" />
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-600">No matching monitors found</h3>
                    <Button variant="outline" class="mt-6 border-gray-200 text-[10px] font-bold uppercase tracking-widest dark:border-gray-800" @click="searchQuery = ''">
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
    </WallboardLayout>
</template>
