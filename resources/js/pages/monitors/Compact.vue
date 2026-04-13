<script setup lang="ts">
import WallboardLayout from '@/layouts/WallboardLayout.vue';
import type { Monitor, Tag } from '@/types/monitor';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Pagination from '@/components/Pagination.vue';
import CompactDots from './partials/CompactDots.vue';
import CompactTable from './partials/CompactTable.vue';
import CompactBars from './partials/CompactBars.vue';
import CompactCards from './partials/CompactCards.vue';
import { debounce } from 'lodash';

const props = defineProps<{
    monitors: { data: Monitor[] };
    pagination: any;
    availableTags: Tag[];
    totalCount: number;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

// View State
const viewType = ref(localStorage.getItem('compact_view_type') || 'dots');
const groupBy = ref(localStorage.getItem('compact_group_by') || 'status');
const searchQuery = ref(new URLSearchParams(window.location.search).get('search') || '');

watch(viewType, (val) => localStorage.setItem('compact_view_type', val));
watch(groupBy, (val) => localStorage.setItem('compact_group_by', val));

// Refresh logic
const countdown = ref(60);
let timer: number | null = null;

const startTimer = () => {
    timer = window.setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            router.reload({ only: ['monitors', 'pagination', 'availableTags', 'totalCount'] });
            countdown.value = 60;
        }
    }, 1000);
};

onMounted(() => startTimer());
onUnmounted(() => timer && clearInterval(timer));

// Server-side searching
const handleSearch = debounce(() => {
    router.get(route('monitor.compact'), { search: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
        only: ['monitors', 'pagination', 'availableTags', 'totalCount'],
    });
}, 300);

watch(searchQuery, () => {
    handleSearch();
});

// Filtering (Local filtering on paginated data)
const filteredMonitors = computed(() => {
    return props.monitors.data;
});

// Grouping
const groups = computed(() => {
    const data = filteredMonitors.value;
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
</script>

<template>
    <WallboardLayout>
        <Head title="Compact Monitors Wallboard" />

        <div class="mx-auto max-w-[1920px]">
            <!-- Wallboard Controls -->
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <Link :href="isAuthenticated ? route('dashboard') : route('home')" class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <Icon name="arrowLeft" size="16" />
                        </Link>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100 uppercase">Status Wallboard</h1>
                            <div class="flex items-center gap-3 text-[10px] text-gray-500 uppercase tracking-widest font-semibold">
                                <span>{{ totalCount }} Monitors</span>
                                <span class="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                <span class="flex items-center gap-1">
                                    <Icon name="clock" size="10" />
                                    REFRESH IN {{ countdown }}S
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="relative w-full md:w-64">
                        <Input
                            v-model="searchQuery"
                            placeholder="FILTER..."
                            class="h-9 rounded-lg bg-white/50 px-8 text-[10px] font-bold uppercase tracking-widest backdrop-blur-sm dark:bg-gray-950/50"
                        />
                        <Icon name="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" size="14" />
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

            <!-- Pagination -->
            <div v-if="pagination.total > pagination.per_page" class="mt-12 flex items-center justify-center border-t border-gray-100 py-8 dark:border-gray-900/50">
                <Pagination :data="{ meta: pagination }" />
            </div>
        </div>
    </WallboardLayout>
</template>
