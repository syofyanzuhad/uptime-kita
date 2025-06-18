<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import type { Monitor } from '@/types/monitor';

const publicMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);
const pollingInterval = ref<number | null>(null);

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

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'up':
            return 'check-circle';
        case 'down':
            return 'x-circle';
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
            <CardTitle class="flex items-center gap-2">
                <Icon name="globe" class="text-blue-500" />
                Public Monitors
                <div v-if="isPolling" class="flex items-center gap-1 ml-2">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-500"></div>
                    <span class="text-xs text-gray-500">Updating...</span>
                </div>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>

            <div v-else-if="error" class="text-center py-8 text-red-500">
                {{ error }}
            </div>

            <div v-else-if="publicMonitors.length === 0" class="text-center py-8 text-gray-500">
                No public monitors available
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="monitor in publicMonitors"
                    :key="monitor.id"
                    class="p-4 border rounded-lg hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm truncate">
                                {{ getDomainFromUrl(monitor.url) }}
                            </h3>
                            <a
                                :href="monitor.url"
                                target="_blank"
                                class="text-xs text-blue-500 hover:underline truncate block"
                            >
                                {{ monitor.url }}
                            </a>
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
                </div>
            </div>
        </CardContent>
    </Card>
</template>
