<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import type { Monitor } from '@/types/monitor';
import { usePage, Link } from '@inertiajs/vue3';
import type { SharedData } from '@/types';

interface Props {
    searchQuery?: string;
    statusFilter?: 'all' | 'up' | 'down' | 'unsubscribed';
}

const props = withDefaults(defineProps<Props>(), {
    searchQuery: '',
    statusFilter: 'all',
});

const publicMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);
const pollingInterval = ref<number | null>(null);
const subscribingMonitors = ref<Set<number>>(new Set());

// Hardcoded pinned monitors - you can modify these IDs as needed
const pinnedMonitors = ref<Set<number>>(new Set([1, 3, 5])); // Example: pin monitors with IDs 1, 3, and 5

const page = usePage<SharedData>();

// Check if user is authenticated using Inertia's auth props
const isAuthenticated = computed(() => {
    return !!page.props.auth.user;
});

const refreshIconClass = computed(() => {
    return loading.value || isPolling.value ? 'animate-spin' : '';
});

// Filter monitors based on search query and status filter
const filteredMonitors = computed(() => {
    let monitors = publicMonitors.value;
    // Filter by status
    if (props.statusFilter === 'up' || props.statusFilter === 'down') {
        monitors = monitors.filter(monitor => monitor.uptime_status === props.statusFilter);
    } else if (props.statusFilter === 'unsubscribed') {
        monitors = monitors.filter(monitor => !monitor.is_subscribed);
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

// Sort monitors to show pinned ones first
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

const fetchPublicMonitors = async (isInitialLoad = false) => {
    try {
        if (isInitialLoad) {
            loading.value = true;
        } else {
            isPolling.value = true;
        }

        const response = await fetch('/public-monitors');
        if (!response.ok) {
            throw new Error('Failed to fetch public monitors');
        }
        publicMonitors.value = await response.json();
        error.value = null;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loading.value = false;
        isPolling.value = false;
    }
};

const subscribeToMonitor = async (monitorId: number) => {
    if (!isAuthenticated.value) {
        // Redirect to login if not authenticated
        window.location.href = '/login';
        return;
    }

    try {
        subscribingMonitors.value.add(monitorId);

        const response = await fetch(`/monitor/${monitorId}/subscribe`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const result = await response.json();

        if (result.success) {
            // Update the monitor's subscription status
            const monitor = publicMonitors.value.find(m => m.id === monitorId);
            if (monitor) {
                monitor.is_subscribed = true;
            }
            // Show success message
            alert(result.message);
        } else {
            // Show error message
            alert(result.message);
        }
    } catch {
        alert('Terjadi kesalahan saat berlangganan monitor');
    } finally {
        subscribingMonitors.value.delete(monitorId);
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
    fetchPublicMonitors(true); // Initial load

    // Start polling every minute (60000ms)
    pollingInterval.value = setInterval(() => {
        fetchPublicMonitors(false); // Polling update
    }, 60000);
});

onUnmounted(() => {
    // Clean up polling interval when component unmounts
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
                    <Icon name="globe" class="text-blue-500" />
                    Public Monitors
                    <div v-if="isPolling" class="flex items-center gap-1 ml-2">
                        <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-500"></div>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                </div>
                <button
                    @click="fetchPublicMonitors(false)"
                    :disabled="loading || isPolling"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
            <!-- Search Results Counter -->
            <div v-if="props.searchQuery && !loading && !error" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                <span v-if="filteredMonitors.length === 1">
                    Ditemukan 1 monitor
                </span>
                <span v-else>
                    Ditemukan {{ filteredMonitors.length }} monitor
                </span>
                <span v-if="publicMonitors.length !== filteredMonitors.length">
                    dari {{ publicMonitors.length }} total monitor
                </span>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>

            <div v-else-if="error" class="text-center py-8 text-red-500">
                {{ error }}
            </div>

            <div v-else-if="publicMonitors.length === 0" class="text-center py-8 text-gray-500">
                No public monitors available
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
                    <!-- Pin Button - Top Right -->
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
                            <h3 class="font-medium text-sm truncate">
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
                        <div v-if="monitor.last_check_date">
                            Last checked: {{ new Date(monitor.last_check_date).toLocaleString() }}
                        </div>
                        <div v-if="monitor.certificate_check_enabled" class="flex items-center gap-2">
                            <span class="text-gray-500">SSL:</span>
                            <span
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.certificate_status === 'valid',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.certificate_status === 'invalid',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': monitor.certificate_status === 'not applicable'
                                }"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ monitor.certificate_status }}
                            </span>
                        </div>
                        <div v-if="monitor.down_for_events_count > 0" class="flex items-center gap-2">
                            <span class="text-gray-500">Down events:</span>
                            <span class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ monitor.down_for_events_count }} times
                            </span>
                        </div>
                    </div>

                    <!-- Subscribe Button -->
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button
                            v-if="!monitor.is_subscribed"
                            @click="subscribeToMonitor(monitor.id)"
                            :disabled="subscribingMonitors.has(monitor.id)"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-600 dark:text-green-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :title="isAuthenticated ? 'Subscribe to this monitor' : 'Login to subscribe'"
                        >
                            <Icon
                                name="plus"
                                :class="subscribingMonitors.has(monitor.id) ? 'animate-spin' : ''"
                                size="14"
                            />
                            <span v-if="subscribingMonitors.has(monitor.id)">
                                Subscribing...
                            </span>
                            <span v-else>
                                Subscribe
                            </span>
                        </button>
                        <div
                            v-else
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg"
                        >
                            <Icon name="check" size="14" />
                            <span>Already Subscribed</span>
                        </div>
                    </div>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
