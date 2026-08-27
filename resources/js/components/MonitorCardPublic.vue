<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorLink from '@/components/MonitorLink.vue';
import { CardContent } from '@/components/ui/card';
import Card from '@/components/ui/card/Card.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { getResponseTimeColorClass, getStatusIcon, getStatusText, getTagDisplayName } from '@/composables/useMonitorHelpers';
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
                :class="[
                    monitor.uptime_status === 'up'
                        ? 'bg-emerald-500'
                        : monitor.uptime_status === 'down'
                          ? 'bg-rose-500'
                          : 'bg-amber-500',
                ]"
            />

            <CardContent class="p-4 sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <img
                            v-if="monitor.favicon && !faviconFailed"
                            :src="monitor.favicon"
                            :alt="`${monitor.name} favicon`"
                            class="h-6 w-6 rounded-lg object-contain drop-shadow-sm transition-transform group-hover:scale-105"
                            @error="faviconFailed = true"
                        />
                        <div
                            v-else
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100 font-mono text-[10px] font-bold text-blue-700 dark:from-blue-950 dark:to-indigo-900/50 dark:text-blue-300"
                        >
                            {{ monogram }}
                        </div>
                        <span class="truncate text-xs font-medium text-gray-500 dark:text-gray-400" :title="monitor.url">
                            {{ monitor.host || monitor.url }}
                        </span>
                    </div>

                    <!-- Live status pill -->
                    <span
                        role="status"
                        :aria-label="getStatusText(monitor.uptime_status)"
                        :class="[
                            'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tracking-tight transition-colors',
                            monitor.uptime_status === 'up'
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'
                                : monitor.uptime_status === 'down'
                                  ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30'
                                  : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30',
                        ]"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="[
                                monitor.uptime_status === 'up'
                                    ? 'bg-emerald-500 animate-pulse'
                                    : monitor.uptime_status === 'down'
                                      ? 'bg-rose-500 animate-ping'
                                      : 'bg-amber-500',
                            ]"
                        />
                        <span>{{ getStatusText(monitor.uptime_status) }}</span>
                    </span>
                </div>

                <MonitorLink
                    :monitor="monitor"
                    :show-favicon="false"
                    class-name="mb-1.5"
                    link-class-name="text-base font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-1 leading-tight tracking-tight transition-colors"
                />

                <!-- Key Metrics & Sparkline Row -->
                <div class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800/80">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span
                            v-if="uptime7d !== null"
                            class="inline-flex items-center font-bold text-gray-900 dark:text-white"
                            :title="`7-day uptime: ${uptime7d}%`"
                        >
                            {{ uptime7d }}%
                            <span class="ml-1 text-[10px] font-normal text-gray-400">7d</span>
                        </span>
                        <span
                            v-else-if="monitor.today_uptime_percentage"
                            class="inline-flex items-center font-bold text-gray-900 dark:text-white"
                        >
                            {{ monitor.today_uptime_percentage }}%
                            <span class="ml-1 text-[10px] font-normal text-gray-400">today</span>
                        </span>

                        <span
                            v-if="responseTime !== null"
                            class="inline-flex items-center gap-0.5 rounded-md bg-gray-50 px-1.5 py-0.5 font-mono text-[11px] font-medium dark:bg-gray-800/60"
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
                    </div>

                    <!-- 7-day Sparkline Micro-Bars -->
                    <div class="flex items-center gap-1" title="7-day uptime history">
                        <Tooltip v-for="(d, i) in sparklineData()" :key="i">
                            <TooltipTrigger as-child>
                                <div
                                    class="h-4 w-1.5 rounded-full transition-all hover:scale-125"
                                    :class="sparkColor(d.pct)"
                                />
                            </TooltipTrigger>
                            <TooltipContent side="top" class="text-xs">
                                <p class="font-medium">{{ d.date }}</p>
                                <p>{{ d.pct !== null ? `${d.pct}% uptime` : 'No checks recorded' }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </div>

                <!-- Tags & Meta Info -->
                <div class="mt-3 flex flex-wrap items-center justify-between gap-1.5 text-[11px] text-gray-400">
                    <div v-if="monitor.tags?.length" class="flex flex-wrap items-center gap-1">
                        <span
                            v-for="tag in monitor.tags.slice(0, 3)"
                            :key="(tag as any).id || getTagDisplayName(tag)"
                            class="rounded-md bg-gray-100 px-1.5 py-0.5 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            #{{ getTagDisplayName(tag) }}
                        </span>
                        <span v-if="(monitor.tags.length ?? 0) > 3" class="text-[10px] text-gray-400">
                            +{{ monitor.tags.length - 3 }}
                        </span>
                    </div>
                    <div v-else class="text-gray-400 text-[11px]">
                        {{ monitor.last_check_date_human || 'Active' }}
                    </div>

                    <div v-if="(monitor.page_views_count ?? 0) > 0" class="flex items-center gap-1 text-gray-400">
                        <Icon name="eye" class="h-3 w-3" />
                        <span>{{ monitor.formatted_page_views }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </TooltipProvider>
</template>
