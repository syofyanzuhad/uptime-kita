<script setup lang="ts">
import type { Monitor } from '@/types/monitor';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Link } from '@inertiajs/vue3';
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
    <div class="overflow-x-auto">
        <Table>
            <TableHeader>
                <TableRow class="hover:bg-transparent">
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider">Monitor</TableHead>
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider">Status</TableHead>
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider text-right whitespace-nowrap">Today Uptime</TableHead>
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider text-right whitespace-nowrap">Avg Resp</TableHead>
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider text-right">Incidents</TableHead>
                    <TableHead class="h-8 py-1 text-xs uppercase tracking-wider text-right">Last Checked</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="monitor in monitors" :key="monitor.id" class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <TableCell class="py-1.5 min-w-[200px]">
                        <Link
                            :href="route('monitor.show', monitor.id)"
                            class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                        >
                            <img
                                v-if="monitor.favicon"
                                :src="monitor.favicon"
                                alt=""
                                class="h-4 w-4 rounded-full"
                            />
                            {{ getDomainFromUrl(monitor.url) }}
                        </Link>
                    </TableCell>
                    <TableCell class="py-1.5">
                        <div class="flex items-center gap-1.5">
                            <Icon
                                :name="getStatusIcon(monitor.uptime_status)"
                                :class="{
                                    'text-green-500': monitor.uptime_status === 'up',
                                    'text-red-500': monitor.uptime_status === 'down',
                                    'text-yellow-500': monitor.uptime_status !== 'up' && monitor.uptime_status !== 'down',
                                }"
                                size="14"
                            />
                            <span class="text-xs font-medium capitalize">{{ monitor.uptime_status }}</span>
                        </div>
                    </TableCell>
                    <TableCell class="py-1.5 text-right">
                        <span
                            v-if="monitor.today_uptime_percentage !== null && monitor.today_uptime_percentage !== undefined"
                            :class="{
                                'text-green-600 dark:text-green-400': monitor.today_uptime_percentage >= 99.5,
                                'text-yellow-600 dark:text-yellow-400': monitor.today_uptime_percentage >= 95 && monitor.today_uptime_percentage < 99.5,
                                'text-red-600 dark:text-red-400': monitor.today_uptime_percentage < 95,
                            }"
                            class="text-xs font-semibold"
                        >
                            {{ monitor.today_uptime_percentage }}%
                        </span>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </TableCell>
                    <TableCell class="py-1.5 text-right text-xs font-mono text-gray-600 dark:text-gray-400">
                        {{ monitor.statistics?.avg_response_time_24h ? monitor.statistics.avg_response_time_24h + 'ms' : '-' }}
                    </TableCell>
                    <TableCell class="py-1.5 text-right text-xs">
                        <span :class="monitor.statistics?.incidents_24h > 0 ? 'text-red-500 font-bold' : 'text-gray-400'">
                            {{ monitor.statistics?.incidents_24h ?? 0 }}
                        </span>
                    </TableCell>
                    <TableCell class="py-1.5 text-right text-xs text-gray-500 dark:text-gray-400">
                        {{ monitor.last_check_date_human }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
