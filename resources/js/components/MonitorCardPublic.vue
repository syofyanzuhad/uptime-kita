<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorLink from '@/components/MonitorLink.vue';
import { CardContent } from '@/components/ui/card';
import Card from '@/components/ui/card/Card.vue';
import { getResponseTimeColorClass, getStatusIcon, getStatusText, getTagDisplayName } from '@/composables/useMonitorHelpers';
import type { Monitor } from '@/types/monitor';
import { computed } from 'vue';

const props = defineProps<{ monitor: Monitor }>();

const uptime7d = computed(() => props.monitor.statistics?.uptime_7d ?? null);
const responseTime = computed(() => {
    const rt = props.monitor.statistics?.avg_response_time_24h;
    return rt ? Math.round(rt) : null;
});
const incidents24h = computed(() => props.monitor.statistics?.incidents_24h ?? 0);

function sparklineData() {
    const days = props.monitor.uptimes_daily ?? [];
    const out: { date: string; pct: number | null }[] = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
        const d = new Date(today); d.setDate(d.getDate() - i);
        const ds = d.toISOString().split('T')[0];
        const found = days.find((x) => x.date === ds);
        out.push({ date: ds, pct: found?.uptime_percentage ?? null });
    }
    return out;
}
function sparkColor(pct: number | null): string {
    if (pct === null) return 'bg-gray-300 dark:bg-gray-600';
    if (pct >= 100) return 'bg-green-500';
    if (pct >= 99) return 'bg-green-300';
    if (pct >= 65) return 'bg-yellow-500';
    return 'bg-red-500';
}
</script>

<template>
    <Card class="cursor-pointer p-0 transition-all hover:shadow-md active:scale-[0.98]" @click="$emit('click', monitor)">
        <CardContent class="p-4">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img v-if="monitor.favicon" :src="monitor.favicon" :alt="`${monitor.name} favicon`" class="h-5 w-5 rounded drop-shadow-sm" @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')" />
                    <div v-else class="flex h-5 w-5 items-center justify-center rounded bg-gray-200 dark:bg-gray-700"><Icon name="globe" class="h-3 w-3 text-gray-500" /></div>
                </div>
                <span role="status" :aria-label="getStatusText(monitor.uptime_status)" :class="['inline-flex items-center justify-center rounded-full p-1.5', monitor.uptime_status === 'up' ? 'bg-green-500' : monitor.uptime_status === 'down' ? 'bg-red-500' : 'bg-gray-400']">
                    <Icon :name="getStatusIcon(monitor.uptime_status)" class="h-3.5 w-3.5 text-white" />
                </span>
            </div>

            <MonitorLink :monitor="monitor" :show-favicon="false" class-name="mb-1" link-class-name="text-base font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 line-clamp-2 leading-tight md:text-lg md:truncate" />

            <p class="mb-3 truncate text-sm text-gray-500 dark:text-gray-400">{{ monitor.url }}</p>

            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium', monitor.uptime_status === 'up' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : monitor.uptime_status === 'down' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300']">
                    <Icon :name="getStatusIcon(monitor.uptime_status)" class="h-3 w-3" />
                    <span v-if="uptime7d !== null">{{ uptime7d }}%<span class="hidden md:inline"> (7d)</span></span>
                    <span v-else-if="monitor.today_uptime_percentage">{{ monitor.today_uptime_percentage }}%</span>
                    <span v-else>{{ getStatusText(monitor.uptime_status) }}</span>
                </span>
                <span v-if="responseTime !== null" class="flex items-center gap-0.5 text-xs" :class="getResponseTimeColorClass(responseTime)"><Icon name="zap" class="h-3 w-3" />{{ responseTime }}ms</span>
                <span v-if="incidents24h > 0" class="flex items-center gap-0.5 rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300"><Icon name="alertTriangle" class="h-3 w-3" />{{ incidents24h }}</span>
                <span v-if="(monitor.page_views_count ?? 0) > 0" class="flex items-center gap-0.5 text-xs text-gray-500 dark:text-gray-400"><Icon name="eye" class="h-3 w-3" />{{ monitor.formatted_page_views }}</span>
                <div v-if="(monitor.uptimes_daily?.length ?? 0) > 0" class="hidden items-center gap-0.5 md:flex">
                    <div v-for="(d, i) in sparklineData()" :key="i" class="h-3 w-1.5 rounded-sm" :class="sparkColor(d.pct)" :title="`${d.date}: ${d.pct !== null ? d.pct + '%' : 'No data'}`" />
                </div>
            </div>

            <div v-if="monitor.last_check_date_human" class="text-xs text-gray-500 dark:text-gray-400"><Icon name="clock" class="mr-1 inline h-3 w-3" />{{ monitor.last_check_date_human }}</div>

            <div v-if="monitor.tags?.length" class="mt-2 flex flex-wrap gap-1">
                <span v-for="tag in monitor.tags.slice(0, 4)" :key="(tag as any).id || getTagDisplayName(tag)" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ getTagDisplayName(tag) }}</span>
                <span v-if="(monitor.tags.length ?? 0) > 4" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">+{{ monitor.tags.length - 4 }}</span>
            </div>
        </CardContent>
    </Card>
</template>
