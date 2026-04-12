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
                    <div class="text-xs">
                        <div class="font-bold">{{ getDomainFromUrl(monitor.url) }}</div>
                        <div>Status: {{ monitor.uptime_status }}</div>
                        <div v-if="monitor.today_uptime_percentage !== undefined">
                            Uptime: {{ monitor.today_uptime_percentage }}%
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
