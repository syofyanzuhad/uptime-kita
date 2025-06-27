<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Monitor } from '@/types/monitor';
import { Link } from '@inertiajs/vue3';

interface Props {
    searchQuery?: string;
    statusFilter?: 'all' | 'up' | 'down' | 'unsubscribed';
}

const props = withDefaults(defineProps<Props>(), {
    searchQuery: '',
    statusFilter: 'all',
});

const privateMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);
const pollingInterval = ref<number | null>(null);

// Pagination state
const currentPage = ref(1);
const hasMorePages = ref(false);
const loadingMore = ref(false);
const totalMonitors = ref(0);
const showingFrom = ref(0);
const showingTo = ref(0);

const pinnedMonitors = ref<Set<number>>(new Set());

const refreshIconClass = computed(() => {
    return loading.value || isPolling.value ? 'animate-spin' : '';
});

const filteredMonitors = computed(() => {
    if (!privateMonitors.value.length) {
        return [];
    }
    let monitors = privateMonitors.value;
    // Filter by status
    if (props.statusFilter === 'up' || props.statusFilter === 'down') {
        monitors = monitors.filter(monitor => monitor.uptime_status === props.statusFilter);
    }
    // Filter by search query
    if (props.searchQuery.trim()) {
        const query = props.searchQuery.toLowerCase().trim();
        monitors = monitors.filter(monitor => {
            const domain = getDomainFromUrl(monitor.url).toLowerCase();
            const url = monitor.url.toLowerCase();
            return domain.includes(query) || url.includes(query);
        });
    }
    return monitors;
});

const sortedMonitors = computed(() => {
    return [...filteredMonitors.value].sort((a, b) => {
        const aPinned = pinnedMonitors.value.has(a.id);
        const bPinned = pinnedMonitors.value.has(b.id);
        if (aPinned && !bPinned) return -1;
        if (!aPinned && bPinned) return 1;
        return 0;
    });
});

const togglePin = (monitorId: number) => {
    if (pinnedMonitors.value.has(monitorId)) {
        pinnedMonitors.value.delete(monitorId);
    } else {
        pinnedMonitors.value.add(monitorId);
    }
};

const isPinned = (monitorId: number) => {
    return pinnedMonitors.value.has(monitorId);
};

const fetchPrivateMonitors = async (isInitialLoad = false, page = 1) => {
    try {
        if (isInitialLoad) {
            loading.value = true;
            currentPage.value = 1;
        } else if (page > 1) {
            loadingMore.value = true;
        } else {
            isPolling.value = true;
        }

        const response = await fetch(`/private-monitors?page=${page}`);
        if (!response.ok) {
            throw new Error('Failed to fetch private monitors');
        }

        const result = await response.json();

        if (isInitialLoad || page === 1) {
            privateMonitors.value = result.data;
        } else {
            // Append new monitors to existing ones
            privateMonitors.value = [...privateMonitors.value, ...result.data];
        }

        // Update pagination state
        hasMorePages.value = result.pagination.has_more_pages;
        totalMonitors.value = result.pagination.total;
        showingFrom.value = result.pagination.from;
        showingTo.value = result.pagination.to;
        currentPage.value = result.pagination.current_page;

        error.value = null;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loading.value = false;
        isPolling.value = false;
        loadingMore.value = false;
    }
};

const loadMore = async () => {
    if (hasMorePages.value && !loadingMore.value) {
        await fetchPrivateMonitors(false, currentPage.value + 1);
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'up':
            return 'checkCircle';
        case 'down':
            return 'xCircle';
        default:
            return 'clock';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'up':
            return 'Online';
        case 'down':
            return 'Offline';
        default:
            return 'Checking...';
    }
};

const getDomainFromUrl = (url: string) => {
    try {
        const domain = new URL(url).hostname;
        return domain.replace('www.', '');
    } catch {
        return url;
    }
};

onMounted(() => {
    fetchPrivateMonitors(true);
    pollingInterval.value = setInterval(() => {
        fetchPrivateMonitors(false, 1); // Polling update - always fetch first page
    }, 60000);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Icon name="lock" class="text-yellow-500" />
                    Private Monitors
                    <div v-if="isPolling" class="flex items-center gap-1 ml-2">
                        <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-yellow-500"></div>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                </div>
                <button
                    @click="fetchPrivateMonitors(false)"
                    :disabled="loading || isPolling"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Refresh monitors"
                >
                    <Icon
                        name="refresh-cw"
                        :class="refreshIconClass"
                        size="16"
                    />
                    Refresh
                </button>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="props.searchQuery && !loading && !error" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                <span v-if="filteredMonitors.length === 1">
                    Ditemukan 1 monitor
                </span>
                <span v-else>
                    Ditemukan {{ filteredMonitors.length }} monitor
                </span>
                <span v-if="privateMonitors.length !== filteredMonitors.length">
                    dari {{ privateMonitors.length }} total monitor
                </span>
            </div>

            <!-- Pagination Info -->
            <div v-if="!loading && !error && privateMonitors.length > 0 && !props.searchQuery" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Showing {{ showingFrom }} to {{ showingTo }} of {{ totalMonitors }} monitors
            </div>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
            </div>
            <div v-else-if="error" class="text-center py-8 text-red-500">
                {{ error }}
            </div>
            <div v-else-if="privateMonitors.length === 0" class="text-center py-8 text-gray-500">
                No private monitors available
            </div>
            <div v-else-if="props.searchQuery && filteredMonitors.length === 0" class="text-center py-8 text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <Icon name="search" class="h-8 w-8 text-gray-400" />
                    <p>Tidak ada monitor yang ditemukan untuk "{{ props.searchQuery }}"</p>
                    <p class="text-sm">Coba kata kunci yang berbeda</p>
                </div>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="monitor in sortedMonitors"
                    :key="monitor.id"
                    class="relative group border rounded-lg hover:shadow-md transition-shadow cursor-pointer p-0"
                >
                    <Link
                        :href="route('monitor.show', monitor.id)"
                        class="block p-4 w-full h-full focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg"
                        style="text-decoration: none; color: inherit;"
                        tabindex="0"
                    >
                    <button
                        @click="togglePin(monitor.id)"
                        :class="{
                            'text-yellow-500': isPinned(monitor.id),
                            'text-gray-400 hover:text-gray-600': !isPinned(monitor.id)
                        }"
                        class="absolute top-2 right-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        :title="isPinned(monitor.id) ? 'Unpin this monitor' : 'Pin this monitor'"
                    >
                        <Icon
                            name="bookmark"
                            :class="isPinned(monitor.id) ? 'fill-current' : ''"
                            size="16"
                        />
                    </button>
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm truncate flex items-center gap-2">
                                {{ getDomainFromUrl(monitor.url) }}
                            </h3>
                            <span
                                class="text-xs text-blue-500 hover:underline truncate block"
                                @click.stop
                                @keydown.stop
                            >
                                {{ monitor.url }}
                            </span>
                        </div>
                        <div class="flex items-center ml-2">
                            <span
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.uptime_status === 'up',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.uptime_status === 'down',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': monitor.uptime_status !== 'up' && monitor.uptime_status !== 'down'
                                }"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                            >
                                <Icon
                                    :name="getStatusIcon(monitor.uptime_status)"
                                    size="12"
                                />
                                {{ getStatusText(monitor.uptime_status) }}
                            </span>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div v-if="monitor.certificate_check_enabled" class="flex items-center gap-1">
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <p
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.certificate_status === 'valid',
                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.certificate_status === 'invalid',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': monitor.certificate_status === 'not applicable'
                                            }"
                                            class="inline-flex uppercase items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            <span
                                                v-if="monitor.certificate_status !== undefined"
                                                :class="[
                                                    'py-0.5 rounded-full text-xs font-semibold uppercase flex items-center mr-1',
                                                    monitor.certificate_status === 'valid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                                    monitor.certificate_status === 'invalid' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                                ]"
                                            >
                                                <Icon :name="
                                                    monitor.certificate_status === 'valid' ? 'lock' :
                                                    monitor.certificate_status === 'invalid' ? 'lockOpen' :
                                                    'alertTriangle'
                                                " class="w-4 h-4" />
                                            </span>
                                            SSL
                                            {{ monitor.certificate_status }}
                                        </p>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p class="text-sm">
                                            {{ monitor.certificate_status === 'valid' ? 'Certificate is valid' :
                                                monitor.certificate_status === 'invalid' ? 'Certificate is invalid' :
                                                'Certificate check not applicable' }}
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                        <div v-if="monitor.last_check_date">
                            Last checked: {{ new Date(monitor.last_check_date).toLocaleString() }}
                        </div>
                        <div v-if="monitor.down_for_events_count > 0" class="flex items-center gap-2">
                            <span class="text-gray-500">Down events:</span>
                            <span class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ monitor.down_for_events_count }} times
                            </span>
                        </div>
                    </div>
                    </Link>
                </div>
            </div>

            <!-- Load More Button -->
            <div v-if="hasMorePages && !loading && !error && !props.searchQuery" class="mt-6 text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="flex items-center gap-2 px-6 py-3 bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    <Icon
                        name="arrow-down"
                        :class="loadingMore ? 'animate-spin' : ''"
                        size="16"
                    />
                    <span v-if="loadingMore">Loading...</span>
                    <span v-else>Load More Monitors</span>
                </button>
            </div>

            <!-- Loading More Indicator -->
            <div v-if="loadingMore" class="mt-4 text-center">
                <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-yellow-500"></div>
                    Loading more monitors...
                </div>
            </div>
        </CardContent>
    </Card>
</template>
