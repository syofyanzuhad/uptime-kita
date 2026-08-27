<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { onMounted, onUnmounted, ref } from 'vue';

interface ServerStats {
    enabled: boolean;
    cpu_percent?: number;
    memory_percent?: number;
    uptime?: string;
    uptime_seconds?: number;
    response_time?: number;
    timestamp?: string;
}

const stats = ref<ServerStats | null>(null);
const loading = ref(true);
const error = ref(false);
const expanded = ref(false);

let refreshInterval: ReturnType<typeof setInterval> | null = null;
let hoverTimeout: ReturnType<typeof setTimeout> | null = null;

async function fetchStats() {
    try {
        const response = await fetch('/api/server-stats');
        if (response.ok) {
            stats.value = await response.json();
            error.value = false;
        } else {
            error.value = true;
        }
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

function getStatusColor(percent: number): string {
    if (percent >= 90) return 'text-rose-600 dark:text-rose-400';
    if (percent >= 70) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
}

function getResponseTimeColor(ms: number): string {
    if (ms >= 500) return 'text-rose-600 dark:text-rose-400';
    if (ms >= 200) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
}

function handleMouseEnter() {
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
        hoverTimeout = null;
    }
    expanded.value = true;
}

function handleMouseLeave() {
    hoverTimeout = setTimeout(() => {
        expanded.value = false;
    }, 200);
}

onMounted(() => {
    fetchStats();
    // Refresh every 30 seconds
    refreshInterval = setInterval(fetchStats, 30000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (hoverTimeout) clearTimeout(hoverTimeout);
});
</script>

<template>
    <div v-if="stats?.enabled !== false" class="relative inline-block" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
        <!-- Collapsed Badge - Mobile (Icon only) -->
        <button
            @click="expanded = !expanded"
            class="flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/80 p-2 text-xs shadow-sm backdrop-blur-sm transition-all hover:bg-gray-100 sm:hidden dark:border-gray-800 dark:bg-gray-800/80 dark:hover:bg-gray-700"
            :class="{ 'ring-2 ring-blue-500/20': expanded }"
            title="Server Stats"
            aria-label="Toggle Server Stats"
        >
            <template v-if="loading">
                <Icon name="loader" class="h-4 w-4 animate-spin text-gray-400" />
            </template>
            <template v-else-if="error">
                <Icon name="alertCircle" class="h-4 w-4 text-rose-500" />
            </template>
            <template v-else-if="stats">
                <Icon name="server" class="h-4 w-4" :class="getStatusColor(Math.max(stats.cpu_percent || 0, stats.memory_percent || 0))" />
            </template>
        </button>

        <!-- Collapsed Badge - Desktop (Full info) -->
        <button
            @click="expanded = !expanded"
            class="hidden cursor-pointer items-center gap-2 rounded-xl border border-gray-200/80 bg-white/80 px-3 py-1.5 text-xs shadow-sm backdrop-blur-sm transition-all hover:border-gray-300 hover:bg-gray-100 sm:flex dark:border-gray-800 dark:bg-gray-800/80 dark:hover:border-gray-700 dark:hover:bg-gray-700"
            :class="{ 'border-blue-400 ring-2 ring-blue-500/20': expanded }"
            aria-label="Server Stats Overview"
        >
            <template v-if="loading">
                <Icon name="loader" class="h-3.5 w-3.5 animate-spin text-gray-400" />
                <span class="text-xs font-medium text-gray-400">Node stats…</span>
            </template>
            <template v-else-if="error">
                <Icon name="alertCircle" class="h-3.5 w-3.5 text-rose-500" />
                <span class="text-xs font-medium text-gray-400">Offline</span>
            </template>
            <template v-else-if="stats">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                    <Icon name="server" class="h-3.5 w-3.5 text-gray-400" />
                </div>
                <div class="flex items-center gap-1 font-mono text-[11px] font-bold">
                    <span class="text-gray-400">CPU</span>
                    <span :class="getStatusColor(stats.cpu_percent || 0)">{{ stats.cpu_percent }}%</span>
                </div>
                <span class="text-gray-300 dark:text-gray-700">/</span>
                <div class="flex items-center gap-1 font-mono text-[11px] font-bold">
                    <span class="text-gray-400">RAM</span>
                    <span :class="getStatusColor(stats.memory_percent || 0)">{{ stats.memory_percent }}%</span>
                </div>
                <Icon name="chevronDown" class="h-3 w-3 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" />
            </template>
        </button>

        <!-- Hover / Expanded Detail Card -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="expanded && stats && !loading && !error"
                class="absolute top-full right-0 z-50 mt-2 w-72 origin-top-right overflow-hidden rounded-2xl border border-gray-200/80 bg-white/95 p-4 shadow-xl backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/95"
            >
                <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-2.5 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            <Icon name="activity" class="h-3.5 w-3.5" />
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">Live Node Telemetry</span>
                    </div>
                    <span class="flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        Operational
                    </span>
                </div>

                <div class="space-y-3">
                    <!-- CPU Load -->
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <Icon name="cpu" class="h-3.5 w-3.5" />
                                <span>CPU Utilization</span>
                            </span>
                            <span class="font-mono font-bold" :class="getStatusColor(stats.cpu_percent || 0)"> {{ stats.cpu_percent }}% </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="[
                                    (stats.cpu_percent || 0) < 70 ? 'bg-emerald-500' : (stats.cpu_percent || 0) < 90 ? 'bg-amber-500' : 'bg-rose-500',
                                ]"
                                :style="{ width: `${Math.min(100, Math.max(2, stats.cpu_percent || 0))}%` }"
                            />
                        </div>
                    </div>

                    <!-- Memory Load -->
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <Icon name="hardDrive" class="h-3.5 w-3.5" />
                                <span>RAM Memory</span>
                            </span>
                            <span class="font-mono font-bold" :class="getStatusColor(stats.memory_percent || 0)"> {{ stats.memory_percent }}% </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="[
                                    (stats.memory_percent || 0) < 70
                                        ? 'bg-emerald-500'
                                        : (stats.memory_percent || 0) < 90
                                          ? 'bg-amber-500'
                                          : 'bg-rose-500',
                                ]"
                                :style="{ width: `${Math.min(100, Math.max(2, stats.memory_percent || 0))}%` }"
                            />
                        </div>
                    </div>

                    <!-- Response Time & Server Uptime Grid -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-2 text-left dark:border-gray-800 dark:bg-gray-800/50">
                            <span class="block text-[10px] font-medium text-gray-400">DB Ping</span>
                            <span class="font-mono text-xs font-bold" :class="getResponseTimeColor(stats.response_time || 0)">
                                ⚡ {{ stats.response_time }}ms
                            </span>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-2 text-left dark:border-gray-800 dark:bg-gray-800/50">
                            <span class="block text-[10px] font-medium text-gray-400">System Uptime</span>
                            <span class="block truncate text-xs font-bold text-emerald-600 dark:text-emerald-400" :title="stats.uptime">
                                {{ stats.uptime || 'Active' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 border-t border-gray-100 pt-2 text-center text-[10px] text-gray-400 dark:border-gray-800">
                    Auto-refreshes every 30 seconds
                </div>
            </div>
        </Transition>
    </div>
</template>
