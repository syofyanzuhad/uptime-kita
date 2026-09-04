<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { computed } from 'vue';

interface Stats {
    total: number;
    up: number;
    down: number;
    total_public: number;
    daily_checks?: number;
    monthly_checks?: number;
    avg_response_time?: number | null;
}

const props = defineProps<{
    stats: Stats;
    statusFilter: string;
    avgLatency: string;
}>();

const emit = defineEmits<{
    (e: 'filterByStatus', status: string): void;
}>();

function formatCompactNumber(num?: number): string {
    if (!num) return '0';
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1_000) return (num / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
    return num.toLocaleString();
}

const metricItems = computed(() => [
    {
        key: 'all',
        label: 'Monitors',
        value: formatCompactNumber(props.stats.total_public),
        icon: 'monitor',
        iconColor: 'text-gray-500 dark:text-gray-400',
        valueColor: 'text-gray-900 dark:text-white',
        clickable: true,
        action: () => emit('filterByStatus', 'all'),
        active: props.statusFilter === 'all',
        activeClass: 'bg-blue-50/30 dark:bg-blue-950/20',
        hasBorderRight: true,
    },
    {
        key: 'up',
        label: 'Operational',
        value: formatCompactNumber(props.stats.up),
        icon: 'checkCircle',
        iconColor: 'text-emerald-500',
        valueColor: 'text-emerald-600 dark:text-emerald-400',
        clickable: true,
        action: () => emit('filterByStatus', 'up'),
        active: props.statusFilter === 'up',
        activeClass: 'bg-emerald-50/30 dark:bg-emerald-950/20',
        hasBorderRight: true,
    },
    {
        key: 'down',
        label: 'Active Incidents',
        value: String(props.stats.down),
        icon: 'alertCircle',
        iconColor: 'text-rose-500',
        valueColor: 'text-rose-600 dark:text-rose-400',
        clickable: true,
        action: () => emit('filterByStatus', 'down'),
        active: props.statusFilter === 'down',
        activeClass: 'bg-rose-50/30 dark:bg-rose-950/20',
        hasBorderRight: false,
    },
    {
        key: 'pings',
        label: '24h Pings',
        value: props.stats.daily_checks !== undefined && props.stats.daily_checks !== null ? formatCompactNumber(props.stats.daily_checks) : null,
        loading: props.stats.daily_checks === undefined,
        icon: 'globe',
        iconColor: 'text-indigo-500 dark:text-indigo-400',
        valueColor: 'text-indigo-600 dark:text-indigo-400',
        clickable: false,
        hasBorderRight: true,
    },
    {
        key: 'latency',
        label: 'Avg Latency',
        value: props.avgLatency,
        loading: props.stats.avg_response_time === undefined && !props.avgLatency,
        icon: 'zap',
        iconColor: 'text-amber-500 dark:text-amber-400',
        valueColor: 'text-amber-600 dark:text-amber-400',
        clickable: false,
        hasBorderRight: true,
    },
    {
        key: 'uptime',
        label: 'Network Uptime',
        value: `${Math.round((props.stats.up / (props.stats.total_public || 1)) * 100)}%`,
        loading: false,
        icon: 'gauge',
        iconColor: 'text-emerald-500 dark:text-emerald-400',
        valueColor: 'text-gray-900 dark:text-white',
        clickable: false,
        hasBorderRight: false,
    },
]);
</script>

<template>
    <!-- Top Metric Strip: Unified 6-Item Card with Vertical Dividers -->
    <div
        class="mb-4 overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xs sm:rounded-3xl dark:border-gray-800/80 dark:bg-gray-900/90"
    >
        <div class="grid grid-cols-3 divide-y divide-gray-100 sm:grid-cols-6 sm:divide-x sm:divide-y-0 dark:divide-gray-800/80">
            <component
                :is="item.clickable ? 'button' : 'div'"
                v-for="item in metricItems"
                :key="item.key"
                type="button"
                @click="item.action ? item.action() : undefined"
                class="group flex flex-col items-center justify-center px-3 py-4 text-center transition-colors"
                :class="[
                    item.hasBorderRight ? 'border-r border-gray-100 sm:border-r-0 dark:border-gray-800/80' : '',
                    item.clickable ? 'cursor-pointer hover:bg-gray-50/80 dark:hover:bg-gray-800/40' : '',
                    item.active ? item.activeClass : '',
                ]"
            >
                <div class="flex items-center gap-1.5">
                    <Icon :name="item.icon" class="h-4 w-4" :class="item.iconColor" />
                    <div v-if="item.loading" class="my-0.5 h-6 w-12 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></div>
                    <span v-else class="text-xl font-black tracking-tight sm:text-2xl" :class="item.valueColor">
                        {{ item.value }}
                    </span>
                </div>
                <span class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ item.label }}
                </span>
            </component>
        </div>
    </div>
</template>
