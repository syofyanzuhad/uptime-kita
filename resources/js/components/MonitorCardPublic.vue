<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { CardContent } from '@/components/ui/card';
import Card from '@/components/ui/card/Card.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { getResponseTimeColorClass, getStatusText, getTagDisplayName } from '@/composables/useMonitorHelpers';
import type { Monitor } from '@/types/monitor';
import { computed, ref } from 'vue';

const props = defineProps<{ monitor: Monitor }>();
const faviconFailed = ref(false);

const uptime7d = computed(() => props.monitor.statistics?.uptime_7d ?? null);
const responseTime = computed(() => {
    const rt = props.monitor.statistics?.avg_response_time_24h;
    return rt ? Math.round(rt) : null;
});
const incidents24h = computed(() => props.monitor.statistics?.incidents_24h ?? 0);

const monogram = computed(() => {
    const raw = props.monitor.name || props.monitor.host || props.monitor.url || '';
    const clean = raw.replace(/^https?:\/\//, '').replace(/^www\./, '');
    return clean.slice(0, 2).toUpperCase() || 'UK';
});

const displayTitle = computed(() => {
    return props.monitor.name && props.monitor.name !== props.monitor.host && props.monitor.name !== props.monitor.url
        ? props.monitor.name
        : (props.monitor.host || props.monitor.url || '');
});

const displaySubtitle = computed(() => {
    if (props.monitor.name && props.monitor.name !== props.monitor.host && props.monitor.name !== props.monitor.url) {
        return props.monitor.host || props.monitor.url;
    }
    return null;
});

const displayUptimePct = computed(() => {
    if (uptime7d.value !== null) {
        return Number(uptime7d.value).toFixed(1).replace(/\.0$/, '');
    }
    if (props.monitor.today_uptime_percentage !== undefined && props.monitor.today_uptime_percentage !== null) {
        return Number(props.monitor.today_uptime_percentage).toFixed(1).replace(/\.0$/, '');
    }
    return null;
});

const uptimePeriodLabel = computed(() => {
    return uptime7d.value !== null ? '7d' : 'today';
});

const uptimePercentageColor = computed(() => {
    if (displayUptimePct.value === null) return 'text-gray-400 dark:text-gray-500';
    const val = Number(displayUptimePct.value);
    if (isNaN(val)) return 'text-gray-400 dark:text-gray-500';
    if (val >= 99.5) return 'text-emerald-600 dark:text-emerald-400';
    if (val >= 95) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
});

function sparklineData() {
    const days = props.monitor.uptimes_daily ?? [];
    const out: { date: string; pct: number | null }[] = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(d.getDate() - i);
        const ds = d.toISOString().split('T')[0];
        const found = days.find((x) => x.date === ds);
        out.push({ date: ds, pct: found?.uptime_percentage ?? null });
    }
    return out;
}

function sparkColor(pct: number | null): string {
    if (pct === null) return 'bg-gray-200 dark:bg-gray-700';
    if (pct >= 100) return 'bg-emerald-500';
    if (pct >= 99) return 'bg-emerald-400';
    if (pct >= 95) return 'bg-amber-400';
    return 'bg-rose-500';
}
</script>

<template>
    <TooltipProvider>
        <Card
            class="group relative cursor-pointer overflow-hidden rounded-2xl border border-gray-200/80 bg-white/90 p-0 shadow-sm backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-400/60 hover:shadow-md active:scale-[0.99] dark:border-gray-800/80 dark:bg-gray-900/90 dark:hover:border-blue-500/40"
            @click="$emit('click', monitor)"
        >
            <!-- Top Status Accent Line -->
            <div
                class="h-1 w-full transition-colors"
                :class="[monitor.uptime_status === 'up' ? 'bg-emerald-500' : monitor.uptime_status === 'down' ? 'bg-rose-500' : 'bg-amber-500']"
            />

            <CardContent class="p-4 sm:p-5">
                <!-- Top Row: Favicon, Domain (once!), and Smart Status Indicator -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <img
                            v-if="monitor.favicon && !faviconFailed"
                            :src="monitor.favicon"
                            :alt="`${displayTitle} favicon`"
                            class="h-6 w-6 shrink-0 rounded-lg object-contain drop-shadow-sm transition-transform group-hover:scale-105"
                            @error="faviconFailed = true"
                        />
                        <div
                            v-else
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100 font-mono text-[10px] font-bold text-blue-700 dark:from-blue-950 dark:to-indigo-900/50 dark:text-blue-300"
                        >
                            {{ monogram }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h3 class="truncate text-sm font-bold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                    {{ displayTitle }}
                                </h3>
                                <Icon name="externalLink" class="h-3 w-3 shrink-0 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-500" />
                            </div>
                            <p v-if="displaySubtitle" class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ displaySubtitle }}
                            </p>
                        </div>
                    </div>

                    <!-- Smart Status Indicator -->
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <!-- Operational (Up): Minimalist pulsing dot badge -->
                            <span
                                v-if="monitor.uptime_status === 'up'"
                                role="status"
                                aria-label="Operational"
                                class="inline-flex shrink-0 items-center rounded-full bg-emerald-50 p-1.5 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:ring-emerald-500/30"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                            </span>

                            <!-- Down: Bold, high-contrast alert badge with text -->
                            <span
                                v-else-if="monitor.uptime_status === 'down'"
                                role="status"
                                aria-label="Down"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 ring-1 ring-rose-600/30 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-500/40"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping" />
                                <span>Down</span>
                            </span>

                            <!-- Checking / Unknown: Neutral badge -->
                            <span
                                v-else
                                role="status"
                                :aria-label="getStatusText(monitor.uptime_status)"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                                <span>{{ getStatusText(monitor.uptime_status) }}</span>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent side="top">
                            <p class="text-xs font-medium">{{ getStatusText(monitor.uptime_status) }}</p>
                        </TooltipContent>
                    </Tooltip>
                </div>

                <!-- Highlighted Uptime Metric & Sparkline Row -->
                <div class="mt-4 flex items-baseline justify-between gap-3">
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-2xl font-black tracking-tight"
                                :class="uptimePercentageColor"
                            >
                                {{ displayUptimePct !== null ? `${displayUptimePct}%` : '—' }}
                            </span>
                            <span v-if="displayUptimePct !== null" class="text-xs font-medium text-gray-400 dark:text-gray-500">
                                {{ uptimePeriodLabel }}
                            </span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">
                            Uptime
                        </span>
                    </div>

                    <!-- 7-day Sparkline Micro-Bars -->
                    <div class="flex flex-col items-end gap-1" title="7-day uptime history">
                        <div class="flex items-center gap-1">
                            <Tooltip v-for="(d, i) in sparklineData()" :key="i">
                                <TooltipTrigger as-child>
                                    <div class="h-5 w-1.5 rounded-full transition-all hover:scale-125" :class="sparkColor(d.pct)" />
                                </TooltipTrigger>
                                <TooltipContent side="top" class="text-xs">
                                    <p class="font-medium">{{ d.date }}</p>
                                    <p>{{ d.pct !== null ? `${d.pct}% uptime` : 'No checks recorded' }}</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">7-day trend</span>
                    </div>
                </div>

                <!-- Secondary Metrics & Meta Info Row -->
                <div class="mt-3.5 flex items-center justify-between border-t border-gray-100 pt-3 text-[11px] text-gray-500 dark:border-gray-800/80 dark:text-gray-400">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="responseTime !== null"
                            class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-1.5 py-0.5 font-mono text-[11px] font-medium dark:bg-gray-800/60"
                            :class="getResponseTimeColorClass(responseTime)"
                            title="Average response time in last 24 hours"
                        >
                            <Icon name="zap" class="h-3 w-3" />
                            {{ responseTime }}ms
                        </span>

                        <span
                            v-if="incidents24h > 0"
                            class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-1.5 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                            :title="`${incidents24h} incidents in last 24 hours`"
                        >
                            <Icon name="alertTriangle" class="h-3 w-3" />
                            {{ incidents24h }}
                        </span>

                        <!-- Tags -->
                        <div v-if="monitor.tags?.length" class="flex flex-wrap items-center gap-1">
                            <span
                                v-for="tag in monitor.tags.slice(0, 2)"
                                :key="(tag as any).id || getTagDisplayName(tag)"
                                class="rounded-md bg-gray-100 px-1.5 py-0.5 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >
                                #{{ getTagDisplayName(tag) }}
                            </span>
                            <span v-if="(monitor.tags.length ?? 0) > 2" class="text-[10px] text-gray-400">
                                +{{ monitor.tags.length - 2 }}
                            </span>
                        </div>
                    </div>

                    <!-- Right side: Page views or Last checked -->
                    <div v-if="(monitor.page_views_count ?? 0) > 0" class="flex items-center gap-1 text-gray-400">
                        <Icon name="eye" class="h-3 w-3" />
                        <span>{{ monitor.formatted_page_views }}</span>
                    </div>
                    <div v-else-if="monitor.last_check_date_human" class="text-gray-400 dark:text-gray-500">
                        {{ monitor.last_check_date_human }}
                    </div>
                </div>
            </CardContent>
        </Card>
    </TooltipProvider>
</template>
