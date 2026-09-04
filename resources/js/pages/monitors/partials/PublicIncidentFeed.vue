<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { formatRelativeTime } from '@/composables/useMonitorHelpers';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Incident {
    id: number;
    monitor_id: number;
    type: string;
    started_at: string;
    ended_at: string | null;
    duration_minutes: number | null;
    reason: string | null;
    status_code: number | null;
    monitor: {
        id: number;
        url?: string | Record<string, any>;
        name?: string | null;
        display_name?: string | null;
        is_public: boolean;
        raw_url?: string;
    };
}

const props = defineProps<{
    incidents?: Incident[];
}>();

const emit = defineEmits<{
    (e: 'selectIncident', incident: Incident): void;
}>();

const incidentsExpanded = ref(false);

const visibleIncidents = computed(() => {
    const list = props.incidents || [];
    return incidentsExpanded.value ? list : list.slice(0, 3);
});

function getIncidentMonitorName(inc: Incident): string {
    if (!inc.monitor) return 'Unknown Service';
    if (inc.monitor.display_name) return inc.monitor.display_name;
    if (inc.monitor.name) return inc.monitor.name;

    const raw = inc.monitor.raw_url || (typeof inc.monitor.url === 'string' ? inc.monitor.url : '');
    if (raw) {
        return raw.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
    }
    return `Monitor #${inc.monitor_id}`;
}

function formatIncidentDuration(inc: Incident): string {
    if (inc.duration_minutes) {
        const hrs = Math.floor(inc.duration_minutes / 60);
        const mins = Math.floor(inc.duration_minutes % 60);
        const secs = 0;
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    if (inc.started_at && inc.ended_at) {
        const start = new Date(inc.started_at).getTime();
        const end = new Date(inc.ended_at).getTime();
        const diffSec = Math.max(0, Math.floor((end - start) / 1000));
        const hrs = Math.floor(diffSec / 3600);
        const mins = Math.floor((diffSec % 3600) / 60);
        const secs = diffSec % 60;
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return '00:00:00';
}
</script>

<template>
    <!-- Recent Incident & Event Activity Card -->
    <div class="mb-4 rounded-2xl border border-gray-200/80 bg-white p-4 shadow-xs sm:rounded-3xl sm:p-5 dark:border-gray-800/80 dark:bg-gray-900/90">
        <!-- Header Row -->
        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-1.5 sm:gap-2">
                <Icon name="alertTriangle" class="h-4 w-4 shrink-0 text-rose-500" />
                <h2 class="truncate text-xs font-bold whitespace-nowrap text-gray-900 sm:text-sm dark:text-white">
                    <span class="sm:hidden">Recent Incidents</span>
                    <span class="hidden sm:inline">Recent Incident & Event Activity</span>
                </h2>
                <span
                    class="inline-flex shrink-0 items-center gap-1 rounded-full border border-emerald-200/60 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold whitespace-nowrap text-emerald-600 sm:px-2 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-400"
                >
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                    <span class="sm:hidden">Live</span>
                    <span class="hidden sm:inline">Live Feed</span>
                </span>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <Link
                    href="/incidents"
                    class="inline-flex items-center gap-0.5 text-xs font-semibold whitespace-nowrap text-blue-600 transition-colors hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    title="View full incident history"
                >
                    <span class="sm:hidden">History &nearr;</span>
                    <span class="hidden sm:inline">Full History &rarr;</span>
                </Link>
                <button
                    type="button"
                    @click="incidentsExpanded = !incidentsExpanded"
                    class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium whitespace-nowrap text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <template v-if="incidents !== undefined">
                        <span class="hidden sm:inline">{{ incidentsExpanded ? 'Collapse' : 'Expand' }} ({{ incidents?.length || 0 }})</span>
                        <span class="sm:hidden">({{ incidents?.length || 0 }})</span>
                    </template>
                    <div v-else class="h-3.5 w-8 animate-pulse rounded bg-gray-200 sm:w-16 dark:bg-gray-800"></div>
                    <Icon :name="incidentsExpanded ? 'chevronUp' : 'chevronDown'" class="h-3.5 w-3.5 shrink-0" />
                </button>
            </div>
        </div>

        <!-- Feed Content -->
        <div class="mt-3 divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-800/70 dark:border-gray-800/70">
            <!-- Skeleton Loading State when deferred prop is in flight -->
            <template v-if="incidents === undefined">
                <div v-for="i in 3" :key="i" class="flex animate-pulse items-center justify-between py-2.5">
                    <div class="flex items-center gap-3">
                        <div class="h-3.5 w-14 rounded bg-gray-200 dark:bg-gray-800"></div>
                        <div class="h-4 w-16 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                        <div class="h-3.5 w-32 rounded bg-gray-200 sm:w-48 dark:bg-gray-800"></div>
                    </div>
                    <div class="h-3 w-12 rounded bg-gray-200 dark:bg-gray-800"></div>
                </div>
            </template>

            <template v-else-if="incidents && incidents.length > 0">
                <div
                    v-for="inc in visibleIncidents"
                    :key="inc.id"
                    @click="emit('selectIncident', inc)"
                    class="flex cursor-pointer items-center justify-between gap-3 py-2.5 transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-800/30"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex shrink-0 items-center gap-1.5 font-mono text-xs text-gray-400 dark:text-gray-500">
                            <Icon name="clock" class="h-3.5 w-3.5 text-rose-400" />
                            <span>{{ formatIncidentDuration(inc) }}</span>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="
                                inc.ended_at
                                    ? 'border border-emerald-200/60 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                    : 'border border-rose-200/60 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                            "
                        >
                            {{ inc.ended_at ? 'Operational' : 'Down' }}
                        </span>
                        <span class="truncate text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ getIncidentMonitorName(inc) }}
                        </span>
                    </div>
                    <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
                        {{ formatRelativeTime(inc.started_at) }}
                    </span>
                </div>
            </template>

            <!-- Clean fallback when no incidents recorded -->
            <div v-else class="flex items-center justify-between py-3 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">All public monitors operational</span>
                    <span class="hidden text-gray-400 sm:inline">— No active incidents in the last 24h</span>
                </div>
                <span class="text-[11px] text-gray-400">Updated live</span>
            </div>
        </div>
    </div>
</template>
