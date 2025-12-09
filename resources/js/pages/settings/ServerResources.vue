<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface ServerMetrics {
    cpu: {
        usage_percent: number;
        cores: number;
    };
    memory: {
        total: number;
        used: number;
        free: number;
        usage_percent: number;
        total_formatted: string;
        used_formatted: string;
        free_formatted: string;
    };
    disk: {
        total: number;
        used: number;
        free: number;
        usage_percent: number;
        total_formatted: string;
        used_formatted: string;
        free_formatted: string;
        path: string;
    };
    uptime: {
        seconds: number;
        formatted: string;
    };
    load_average: {
        '1min': number;
        '5min': number;
        '15min': number;
    };
    php: {
        version: string;
        memory_limit: string;
        max_execution_time: string;
        upload_max_filesize: string;
        post_max_size: string;
        extensions: Record<string, boolean>;
        current_memory: number;
        current_memory_formatted: string;
        peak_memory: number;
        peak_memory_formatted: string;
    };
    laravel: {
        version: string;
        environment: string;
        debug_mode: boolean;
        timezone: string;
        locale: string;
    };
    database: {
        connection: string;
        status: string;
        size: number;
        size_formatted: string;
    };
    queue: {
        driver: string;
        pending_jobs: number;
        failed_jobs: number;
    };
    cache: {
        driver: string;
        status: string;
    };
    timestamp: string;
}

interface Props {
    initialMetrics: ServerMetrics;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Server Resources',
        href: '/settings/server-resources',
    },
];

const metrics = ref<ServerMetrics>(props.initialMetrics);
const loading = ref(false);
const lastUpdated = ref<Date>(new Date());
const autoRefresh = ref(true);
const refreshInterval = ref(5); // seconds
let intervalId: ReturnType<typeof setInterval> | null = null;

const formattedLastUpdated = computed(() => {
    return lastUpdated.value.toLocaleTimeString();
});

async function fetchMetrics() {
    loading.value = true;
    try {
        const response = await fetch('/api/server-resources');
        if (response.ok) {
            metrics.value = await response.json();
            lastUpdated.value = new Date();
        }
    } catch (error) {
        console.error('Failed to fetch metrics:', error);
    } finally {
        loading.value = false;
    }
}

function startAutoRefresh() {
    if (intervalId) {
        clearInterval(intervalId);
    }
    intervalId = setInterval(fetchMetrics, refreshInterval.value * 1000);
}

function stopAutoRefresh() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

function toggleAutoRefresh() {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

function getProgressColorClass(percent: number): string {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
}

function getBadgeClass(variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'success'): string {
    const base = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium';
    switch (variant) {
        case 'default':
            return `${base} bg-primary text-primary-foreground`;
        case 'destructive':
            return `${base} bg-red-500 text-white`;
        case 'outline':
            return `${base} border border-gray-300 dark:border-gray-600`;
        case 'secondary':
            return `${base} bg-gray-200 dark:bg-gray-700`;
        case 'success':
            return `${base} bg-green-500 text-white`;
        default:
            return base;
    }
}

function getStatusBadgeVariant(status: string): 'default' | 'destructive' | 'outline' | 'secondary' | 'success' {
    switch (status) {
        case 'connected':
        case 'working':
            return 'success';
        case 'error':
            return 'destructive';
        default:
            return 'secondary';
    }
}

onMounted(() => {
    if (autoRefresh.value) {
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Server Resources" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="flex items-center justify-between">
                    <HeadingSmall title="Server Resources" description="Real-time server resource monitoring" />
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-xs">
                            Updated: {{ formattedLastUpdated }}
                        </span>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="fetchMetrics"
                            :disabled="loading"
                        >
                            <Icon name="RefreshCw" :class="loading ? 'h-4 w-4 animate-spin' : 'h-4 w-4'" />
                        </Button>
                        <Button
                            size="sm"
                            :variant="autoRefresh ? 'default' : 'outline'"
                            @click="toggleAutoRefresh"
                        >
                            {{ autoRefresh ? 'Auto: ON' : 'Auto: OFF' }}
                        </Button>
                    </div>
                </div>

                <!-- System Resources Grid -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- CPU Card -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Cpu" class="h-4 w-4" />
                                CPU
                            </CardTitle>
                            <CardDescription>{{ metrics.cpu.cores }} cores</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Usage</span>
                                    <span class="font-medium">{{ metrics.cpu.usage_percent }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full transition-all duration-300"
                                        :class="getProgressColorClass(metrics.cpu.usage_percent)"
                                        :style="{ width: `${metrics.cpu.usage_percent}%` }"
                                    ></div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Memory Card -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="MemoryStick" class="h-4 w-4" />
                                Memory
                            </CardTitle>
                            <CardDescription>{{ metrics.memory.used_formatted }} / {{ metrics.memory.total_formatted }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Usage</span>
                                    <span class="font-medium">{{ metrics.memory.usage_percent }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full transition-all duration-300"
                                        :class="getProgressColorClass(metrics.memory.usage_percent)"
                                        :style="{ width: `${metrics.memory.usage_percent}%` }"
                                    ></div>
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    Free: {{ metrics.memory.free_formatted }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Disk Card -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="HardDrive" class="h-4 w-4" />
                                Disk
                            </CardTitle>
                            <CardDescription>{{ metrics.disk.used_formatted }} / {{ metrics.disk.total_formatted }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Usage</span>
                                    <span class="font-medium">{{ metrics.disk.usage_percent }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full transition-all duration-300"
                                        :class="getProgressColorClass(metrics.disk.usage_percent)"
                                        :style="{ width: `${metrics.disk.usage_percent}%` }"
                                    ></div>
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    Free: {{ metrics.disk.free_formatted }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Server Info -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Uptime & Load -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Clock" class="h-4 w-4" />
                                Uptime & Load
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground text-sm">Server Uptime</span>
                                    <span class="font-medium">{{ metrics.uptime.formatted }}</span>
                                </div>
                                <div class="flex flex-wrap justify-between gap-2">
                                    <span class="text-muted-foreground text-sm">Load Average</span>
                                    <div class="flex gap-2 text-sm">
                                        <span :class="getBadgeClass('outline')">1m: {{ metrics.load_average['1min'] }}</span>
                                        <span :class="getBadgeClass('outline')">5m: {{ metrics.load_average['5min'] }}</span>
                                        <span :class="getBadgeClass('outline')">15m: {{ metrics.load_average['15min'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- PHP Info -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Code" class="h-4 w-4" />
                                PHP
                            </CardTitle>
                            <CardDescription>v{{ metrics.php.version }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Memory Limit</span>
                                    <span>{{ metrics.php.memory_limit }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Current Memory</span>
                                    <span>{{ metrics.php.current_memory_formatted }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Peak Memory</span>
                                    <span>{{ metrics.php.peak_memory_formatted }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Max Upload</span>
                                    <span>{{ metrics.php.upload_max_filesize }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Application Info -->
                <div class="grid gap-4 md:grid-cols-3">
                    <!-- Laravel Info -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Box" class="h-4 w-4" />
                                Laravel
                            </CardTitle>
                            <CardDescription>v{{ metrics.laravel.version }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Environment</span>
                                    <span :class="getBadgeClass(metrics.laravel.environment === 'production' ? 'default' : 'secondary')">
                                        {{ metrics.laravel.environment }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Debug Mode</span>
                                    <span :class="getBadgeClass(metrics.laravel.debug_mode ? 'destructive' : 'success')">
                                        {{ metrics.laravel.debug_mode ? 'ON' : 'OFF' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Timezone</span>
                                    <span>{{ metrics.laravel.timezone }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Database Info -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Database" class="h-4 w-4" />
                                Database
                            </CardTitle>
                            <CardDescription>{{ metrics.database.connection }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Status</span>
                                    <span :class="getBadgeClass(getStatusBadgeVariant(metrics.database.status))">
                                        {{ metrics.database.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Size</span>
                                    <span>{{ metrics.database.size_formatted }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Queue & Cache -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Layers" class="h-4 w-4" />
                                Queue & Cache
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Queue Driver</span>
                                    <span>{{ metrics.queue.driver }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Pending Jobs</span>
                                    <span :class="getBadgeClass('outline')">{{ metrics.queue.pending_jobs }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Failed Jobs</span>
                                    <span :class="getBadgeClass(metrics.queue.failed_jobs > 0 ? 'destructive' : 'outline')">
                                        {{ metrics.queue.failed_jobs }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Cache</span>
                                    <span :class="getBadgeClass(getStatusBadgeVariant(metrics.cache.status))">
                                        {{ metrics.cache.driver }} ({{ metrics.cache.status }})
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- PHP Extensions -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">PHP Extensions</CardTitle>
                        <CardDescription>Important extensions status</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(loaded, ext) in metrics.php.extensions"
                                :key="ext"
                                :class="getBadgeClass(loaded ? 'success' : 'destructive')"
                            >
                                {{ ext }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
