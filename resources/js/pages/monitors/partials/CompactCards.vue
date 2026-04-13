<script setup lang="ts">
import type { Monitor } from '@/types/monitor';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

const props = defineProps<{
    monitors: Monitor[];
}>();

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
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
        <Link
            v-for="monitor in monitors"
            :key="monitor.id"
            :href="route('monitor.show', monitor.id)"
            class="group flex flex-col items-center gap-1 rounded-xl border border-gray-200 bg-white p-3 text-center transition-all hover:border-blue-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-500"
        >
            <div class="relative mb-1">
                <img
                    v-if="monitor.favicon"
                    :src="monitor.favicon"
                    alt=""
                    class="h-8 w-8 rounded-full"
                />
                <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs font-bold dark:bg-gray-700">
                    {{ getDomainFromUrl(monitor.url).charAt(0).toUpperCase() }}
                </div>
                
                <div class="absolute -bottom-1 -right-1 rounded-full bg-white p-0.5 dark:bg-gray-800">
                    <Icon
                        :name="getStatusIcon(monitor.uptime_status)"
                        :class="{
                            'text-green-500': monitor.uptime_status === 'up',
                            'text-red-500': monitor.uptime_status === 'down',
                            'text-yellow-500': monitor.uptime_status !== 'up' && monitor.uptime_status !== 'down',
                        }"
                        size="14"
                    />
                </div>
            </div>
            
            <div class="w-full">
                <p class="truncate text-[10px] font-bold text-gray-900 dark:text-gray-100 sm:text-xs">
                    {{ getDomainFromUrl(monitor.url) }}
                </p>
                <div class="mt-1 space-y-0.5">
                    <div
                        v-if="monitor.today_uptime_percentage !== null && monitor.today_uptime_percentage !== undefined"
                        :class="{
                            'text-green-600 dark:text-green-400': monitor.today_uptime_percentage >= 99.5,
                            'text-yellow-600 dark:text-yellow-400': monitor.today_uptime_percentage >= 95 && monitor.today_uptime_percentage < 99.5,
                            'text-red-600 dark:text-red-400': monitor.today_uptime_percentage < 95,
                        }"
                        class="text-xs font-bold whitespace-nowrap"
                    >
                        {{ monitor.today_uptime_percentage }}%
                    </div>
                    <p v-else class="text-[10px] font-semibold text-gray-400">-</p>
                    <div class="flex items-center justify-center gap-2 text-[8px] uppercase tracking-tighter font-bold text-gray-400 dark:text-gray-500">
                        <span v-if="monitor.statistics?.avg_response_time_24h">{{ monitor.statistics.avg_response_time_24h }}ms</span>
                        <span v-if="monitor.statistics?.incidents_24h > 0" class="text-red-400">{{ monitor.statistics.incidents_24h }}i</span>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>
