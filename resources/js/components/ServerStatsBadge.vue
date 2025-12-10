<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

import Icon from '@/components/Icon.vue';

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
    if (percent >= 90) return 'text-red-500';
    if (percent >= 70) return 'text-yellow-500';
    return 'text-green-500';
}

function getResponseTimeColor(ms: number): string {
    if (ms >= 500) return 'text-red-500';
    if (ms >= 200) return 'text-yellow-500';
    return 'text-green-500';
}

onMounted(() => {
    fetchStats();
    // Refresh every 30 seconds
    refreshInterval = setInterval(fetchStats, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <div v-if="stats?.enabled !== false" class="relative inline-block">
        <!-- Collapsed Badge - Mobile (Icon only) -->
        <button
            @click="expanded = !expanded"
            class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-white/80 p-2 text-xs shadow-sm backdrop-blur-sm transition-all hover:bg-white hover:shadow sm:hidden dark:border-gray-700 dark:bg-gray-800/80 dark:hover:bg-gray-800"
            :class="{ 'ring-2 ring-primary/20': expanded }"
            title="Server Stats"
        >
            <template v-if="loading">
                <Icon name="Loader2" class="h-4 w-4 animate-spin text-gray-400" />
            </template>
            <template v-else-if="error">
                <Icon name="AlertCircle" class="h-4 w-4 text-red-500" />
            </template>
            <template v-else-if="stats">
                <Icon
                    name="Server"
                    class="h-4 w-4"
                    :class="getStatusColor(Math.max(stats.cpu_percent || 0, stats.memory_percent || 0))"
                />
            </template>
        </button>

        <!-- Collapsed Badge - Desktop (Full info) -->
        <button
            @click="expanded = !expanded"
            class="hidden items-center gap-1.5 rounded-full border border-gray-200 bg-white/80 px-2.5 py-1 text-xs shadow-sm backdrop-blur-sm transition-all hover:bg-white hover:shadow sm:flex dark:border-gray-700 dark:bg-gray-800/80 dark:hover:bg-gray-800"
            :class="{ 'ring-2 ring-primary/20': expanded }"
        >
            <template v-if="loading">
                <Icon name="Loader2" class="h-3 w-3 animate-spin text-gray-400" />
                <span class="text-gray-500">Loading...</span>
            </template>
            <template v-else-if="error">
                <Icon name="AlertCircle" class="h-3 w-3 text-red-500" />
                <span class="text-gray-500">Offline</span>
            </template>
            <template v-else-if="stats">
                <Icon name="Server" class="h-3 w-3 text-gray-500" />
                <span class="font-medium" :class="getStatusColor(stats.cpu_percent || 0)">
                    {{ stats.cpu_percent }}%
                </span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <Icon name="MemoryStick" class="h-3 w-3 text-gray-500" />
                <span class="font-medium" :class="getStatusColor(stats.memory_percent || 0)">
                    {{ stats.memory_percent }}%
                </span>
                <Icon
                    :name="expanded ? 'ChevronUp' : 'ChevronDown'"
                    class="h-3 w-3 text-gray-400"
                />
            </template>
        </button>

        <!-- Expanded Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="expanded && stats && !loading && !error"
                class="absolute right-0 top-full z-50 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Server Status</span>
                    <span class="flex items-center gap-1 text-[10px] text-gray-400">
                        <Icon name="RefreshCw" class="h-2.5 w-2.5" />
                        Auto-refresh
                    </span>
                </div>

                <div class="space-y-2.5">
                    <!-- CPU -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <Icon name="Cpu" class="h-3.5 w-3.5 text-gray-500" />
                            <span class="text-xs text-gray-600 dark:text-gray-400">CPU</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-full transition-all"
                                    :class="{
                                        'bg-green-500': (stats.cpu_percent || 0) < 70,
                                        'bg-yellow-500':
                                            (stats.cpu_percent || 0) >= 70 && (stats.cpu_percent || 0) < 90,
                                        'bg-red-500': (stats.cpu_percent || 0) >= 90,
                                    }"
                                    :style="{ width: `${stats.cpu_percent}%` }"
                                ></div>
                            </div>
                            <span class="w-8 text-right text-xs font-medium" :class="getStatusColor(stats.cpu_percent || 0)">
                                {{ stats.cpu_percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Memory -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <Icon name="MemoryStick" class="h-3.5 w-3.5 text-gray-500" />
                            <span class="text-xs text-gray-600 dark:text-gray-400">Memory</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-full transition-all"
                                    :class="{
                                        'bg-green-500': (stats.memory_percent || 0) < 70,
                                        'bg-yellow-500':
                                            (stats.memory_percent || 0) >= 70 && (stats.memory_percent || 0) < 90,
                                        'bg-red-500': (stats.memory_percent || 0) >= 90,
                                    }"
                                    :style="{ width: `${stats.memory_percent}%` }"
                                ></div>
                            </div>
                            <span class="w-8 text-right text-xs font-medium" :class="getStatusColor(stats.memory_percent || 0)">
                                {{ stats.memory_percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Response Time -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <Icon name="Zap" class="h-3.5 w-3.5 text-gray-500" />
                            <span class="text-xs text-gray-600 dark:text-gray-400">Response</span>
                        </div>
                        <span class="text-xs font-medium" :class="getResponseTimeColor(stats.response_time || 0)">
                            {{ stats.response_time }}ms
                        </span>
                    </div>

                    <!-- Uptime -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <Icon name="Clock" class="h-3.5 w-3.5 text-gray-500" />
                            <span class="text-xs text-gray-600 dark:text-gray-400">Uptime</span>
                        </div>
                        <span class="text-xs font-medium text-green-500">
                            {{ stats.uptime }}
                        </span>
                    </div>
                </div>

                <div class="mt-2.5 border-t border-gray-100 pt-2 dark:border-gray-700">
                    <p class="text-center text-[10px] text-gray-400">
                        Server resource transparency
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>
