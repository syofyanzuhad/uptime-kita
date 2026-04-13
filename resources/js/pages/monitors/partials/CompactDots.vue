<script setup lang="ts">
import type { Monitor } from '@/types/monitor';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    monitors: Monitor[];
}>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'up':
            return 'bg-green-500 hover:bg-green-600';
        case 'down':
            return 'bg-red-500 hover:bg-red-600';
        default:
            return 'bg-yellow-500 hover:bg-yellow-600';
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
    <div class="flex flex-wrap gap-2">
        <TooltipProvider v-for="monitor in monitors" :key="monitor.id" :delay-duration="0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Link
                        :href="route('monitor.show', monitor.id)"
                        :class="[
                            'h-6 w-6 rounded-md transition-colors sm:h-8 sm:w-8',
                            getStatusColor(monitor.uptime_status)
                        ]"
                    />
                </TooltipTrigger>
                <TooltipContent>
                    <div class="text-xs space-y-1">
                        <div class="font-bold border-b border-white/20 pb-1 mb-1">{{ getDomainFromUrl(monitor.url) }}</div>
                        <div class="flex justify-between gap-4">
                            <span class="opacity-70">Status:</span>
                            <span class="font-semibold capitalize">{{ monitor.uptime_status }}</span>
                        </div>
                        <div v-if="monitor.today_uptime_percentage !== undefined" class="flex justify-between gap-4">
                            <span class="text-gray-400">Today Uptime:</span>
                            <span :class="monitor.today_uptime_percentage < 99 ? 'text-red-400' : 'text-green-400'">
                                {{ monitor.today_uptime_percentage }}%
                            </span>
                        </div>
                        <div v-if="monitor.statistics?.avg_response_time_24h" class="flex justify-between gap-4">
                            <span class="opacity-70">Avg Resp:</span>
                            <span>{{ monitor.statistics.avg_response_time_24h }}ms</span>
                        </div>
                        <div v-if="monitor.statistics?.incidents_24h !== undefined" class="flex justify-between gap-4">
                            <span class="opacity-70">Incidents:</span>
                            <span :class="monitor.statistics.incidents_24h > 0 ? 'text-red-400' : ''">
                                {{ monitor.statistics.incidents_24h }}
                            </span>
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
