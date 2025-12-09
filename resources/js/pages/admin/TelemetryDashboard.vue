<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Statistics {
    total_instances: number;
    active_last_7_days: number;
    active_last_30_days: number;
    new_this_month: number;
    new_last_month: number;
}

interface VersionDistribution {
    app: Record<string, number>;
    php: Record<string, number>;
    laravel: Record<string, number>;
}

interface GrowthData {
    month: string;
    count: number;
}

interface RecentPing {
    id: number;
    instance_id: string;
    app_version: string;
    php_version: string;
    laravel_version: string;
    monitors_total: number;
    users_total: number;
    os_type: string;
    first_seen_at: string;
    last_ping_at: string;
    ping_count: number;
}

interface Props {
    receiverEnabled: boolean;
    statistics: Statistics | null;
    versionDistribution: VersionDistribution | null;
    osDistribution: Record<string, number> | null;
    growthData: GrowthData[] | null;
    recentPings: RecentPing[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Telemetry Dashboard', href: '/admin/telemetry' }];

function getBadgeClass(variant: 'default' | 'success' | 'secondary' | 'outline'): string {
    const base = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium';
    switch (variant) {
        case 'success':
            return `${base} bg-green-500 text-white`;
        case 'secondary':
            return `${base} bg-gray-200 dark:bg-gray-700`;
        case 'outline':
            return `${base} border border-gray-300 dark:border-gray-600`;
        default:
            return `${base} bg-primary text-primary-foreground`;
    }
}

function formatDistribution(data: Record<string, number> | null): { label: string; count: number; percent: number }[] {
    if (!data) return [];
    const total = Object.values(data).reduce((a, b) => a + b, 0);
    return Object.entries(data).map(([label, count]) => ({
        label,
        count,
        percent: total > 0 ? Math.round((count / total) * 100) : 0,
    }));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Telemetry Dashboard" />

        <div class="px-4 py-6">
            <HeadingSmall
                title="Telemetry Dashboard"
                description="View anonymous usage statistics from self-hosted Uptime-Kita instances"
            />

            <!-- Receiver Not Enabled Warning -->
            <div v-if="!receiverEnabled" class="mt-6">
                <Card class="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950">
                    <CardContent class="pt-4">
                        <div class="flex gap-3">
                            <Icon name="AlertTriangle" class="h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400" />
                            <div class="text-sm text-yellow-800 dark:text-yellow-200">
                                <p class="mb-1 font-medium">Telemetry Receiver Disabled</p>
                                <p>
                                    To receive telemetry pings from other Uptime-Kita instances, enable the receiver in
                                    your .env file:
                                </p>
                                <pre class="bg-muted mt-2 rounded p-2 text-xs">TELEMETRY_RECEIVER_ENABLED=true</pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Dashboard Content -->
            <div v-else class="mt-6 space-y-6">
                <!-- Stats Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">Total Instances</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">{{ statistics?.total_instances || 0 }}</div>
                            <p class="text-muted-foreground text-xs">All time</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">Active (7 days)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-green-600">
                                {{ statistics?.active_last_7_days || 0 }}
                            </div>
                            <p class="text-muted-foreground text-xs">Pinged in last 7 days</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">Active (30 days)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-blue-600">
                                {{ statistics?.active_last_30_days || 0 }}
                            </div>
                            <p class="text-muted-foreground text-xs">Pinged in last 30 days</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium">New This Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-purple-600">{{ statistics?.new_this_month || 0 }}</div>
                            <p class="text-muted-foreground text-xs">
                                vs {{ statistics?.new_last_month || 0 }} last month
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Distribution Charts -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- PHP Version Distribution -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Code" class="h-4 w-4" />
                                PHP Versions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="item in formatDistribution(versionDistribution?.php)"
                                    :key="item.label"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm">{{ item.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full bg-blue-500"
                                                :style="{ width: `${item.percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-muted-foreground w-12 text-right text-xs"
                                            >{{ item.count }}</span
                                        >
                                    </div>
                                </div>
                                <p
                                    v-if="!versionDistribution?.php || Object.keys(versionDistribution.php).length === 0"
                                    class="text-muted-foreground text-sm"
                                >
                                    No data yet
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Laravel Version Distribution -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Box" class="h-4 w-4" />
                                Laravel Versions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="item in formatDistribution(versionDistribution?.laravel)"
                                    :key="item.label"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm">{{ item.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full bg-red-500"
                                                :style="{ width: `${item.percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-muted-foreground w-12 text-right text-xs"
                                            >{{ item.count }}</span
                                        >
                                    </div>
                                </div>
                                <p
                                    v-if="
                                        !versionDistribution?.laravel ||
                                        Object.keys(versionDistribution.laravel).length === 0
                                    "
                                    class="text-muted-foreground text-sm"
                                >
                                    No data yet
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- OS Distribution -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <Icon name="Monitor" class="h-4 w-4" />
                                Operating Systems
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="item in formatDistribution(osDistribution)"
                                    :key="item.label"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm">{{ item.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full bg-green-500"
                                                :style="{ width: `${item.percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-muted-foreground w-12 text-right text-xs"
                                            >{{ item.count }}</span
                                        >
                                    </div>
                                </div>
                                <p
                                    v-if="!osDistribution || Object.keys(osDistribution).length === 0"
                                    class="text-muted-foreground text-sm"
                                >
                                    No data yet
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Growth Chart -->
                <Card v-if="growthData && growthData.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Icon name="TrendingUp" class="h-4 w-4" />
                            Instance Growth (Last 12 Months)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-40 items-end gap-1">
                            <div
                                v-for="item in growthData"
                                :key="item.month"
                                class="group relative flex flex-1 flex-col items-center"
                            >
                                <div
                                    class="w-full rounded-t bg-primary transition-all hover:bg-primary/80"
                                    :style="{
                                        height: `${Math.max(
                                            (item.count / Math.max(...growthData.map((d) => d.count), 1)) * 100,
                                            4,
                                        )}%`,
                                    }"
                                ></div>
                                <span class="text-muted-foreground mt-1 text-[10px]">{{
                                    item.month.split(' ')[0]
                                }}</span>
                                <div
                                    class="bg-popover absolute -top-8 hidden rounded px-2 py-1 text-xs shadow group-hover:block"
                                >
                                    {{ item.count }} instances
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Pings Table -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Icon name="Activity" class="h-4 w-4" />
                            Recent Pings
                        </CardTitle>
                        <CardDescription>Latest telemetry pings received</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-2 py-2 text-left font-medium">Instance</th>
                                        <th class="px-2 py-2 text-left font-medium">Versions</th>
                                        <th class="px-2 py-2 text-left font-medium">Stats</th>
                                        <th class="px-2 py-2 text-left font-medium">OS</th>
                                        <th class="px-2 py-2 text-left font-medium">Last Ping</th>
                                        <th class="px-2 py-2 text-left font-medium">Pings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="ping in recentPings"
                                        :key="ping.id"
                                        class="border-b last:border-0"
                                    >
                                        <td class="px-2 py-2">
                                            <code class="text-xs">{{ ping.instance_id }}</code>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="flex flex-wrap gap-1">
                                                <span :class="getBadgeClass('outline')">PHP {{ ping.php_version }}</span>
                                                <span :class="getBadgeClass('outline')"
                                                    >Laravel {{ ping.laravel_version }}</span
                                                >
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-muted-foreground text-xs">
                                                {{ ping.monitors_total }} monitors, {{ ping.users_total }} users
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <span :class="getBadgeClass('secondary')">{{ ping.os_type }}</span>
                                        </td>
                                        <td class="text-muted-foreground px-2 py-2 text-xs">
                                            {{ ping.last_ping_at }}
                                        </td>
                                        <td class="px-2 py-2">
                                            <span :class="getBadgeClass('default')">{{ ping.ping_count }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentPings.length === 0">
                                        <td colspan="6" class="text-muted-foreground px-2 py-4 text-center">
                                            No telemetry pings received yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
