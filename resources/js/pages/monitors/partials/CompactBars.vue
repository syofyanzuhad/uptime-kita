<script setup lang="ts">
import type { Monitor } from '@/types/monitor';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    monitors: Monitor[];
}>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'up':
            return 'bg-green-500';
        case 'down':
            return 'bg-red-500';
        default:
            return 'bg-yellow-500';
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
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-4">
        <Link
            v-for="monitor in monitors"
            :key="monitor.id"
            :href="route('monitor.show', monitor.id)"
            class="group relative flex items-center gap-3 overflow-hidden rounded-lg border border-gray-200 bg-white p-2 transition-all hover:border-gray-300 hover:shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
        >
            <div
                :class="getStatusColor(monitor.uptime_status)"
                class="absolute left-0 top-0 h-full w-1 transition-all group-hover:w-1.5"
            />
            
            <img
                v-if="monitor.favicon"
                :src="monitor.favicon"
                alt=""
                class="h-4 w-4 shrink-0 rounded-full"
            />
            
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-xs font-semibold text-gray-900 dark:text-gray-100">{{ getDomainFromUrl(monitor.url) }}</span>
                    <span
                        v-if="monitor.statistics?.uptime_24h !== null && monitor.statistics?.uptime_24h !== undefined"
                        :class="{
                            'text-green-600 dark:text-green-400': monitor.statistics.uptime_24h >= 99.5,
                            'text-yellow-600 dark:text-yellow-400': monitor.statistics.uptime_24h >= 95 && monitor.statistics.uptime_24h < 99.5,
                            'text-red-600 dark:text-red-400': monitor.statistics.uptime_24h < 95,
                        }"
                        class="text-[10px] font-bold"
                    >
                        {{ monitor.statistics.uptime_24h }}%
                    </span>
                    <span v-else class="text-[10px] font-bold text-gray-400">-</span>
                </div>
                <div class="flex items-center gap-3 mt-0.5 text-[9px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500">
                    <span v-if="monitor.statistics?.avg_response_time_24h">{{ monitor.statistics.avg_response_time_24h }}ms</span>
                    <span v-if="monitor.statistics?.incidents_24h > 0" class="text-red-400">{{ monitor.statistics.incidents_24h }} incidents</span>
                </div>
            </div>
        </Link>
    </div>
</template>
