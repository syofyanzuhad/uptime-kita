<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useBookmarks } from '@/composables/useBookmarks';
import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';
import { Link, usePage } from '@inertiajs/vue3';
import { Minus, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import Button from './ui/button/Button.vue';

interface Props {
    monitor: Monitor;
    type: 'private' | 'public';
    isPinned?: boolean;
    onTogglePin?: (monitorId: number) => void;
    onToggleActive?: (monitorId: number) => void;
    onSubscribe?: (monitorId: number) => void;
    onUnsubscribe?: (monitorId: number) => void;
    togglingMonitors?: Set<number>;
    subscribingMonitors?: Set<number>;
    unsubscribingMonitors?: Set<number>;
    loadingMonitors?: Set<number>;
    showSubscribeButton?: boolean;
    showToggleButton?: boolean;
    showPinButton?: boolean;
    showAddButton?: boolean;
    showUptimePercentage?: boolean;
    showCertificateStatus?: boolean;
    showLastChecked?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isPinned: false,
    togglingMonitors: () => new Set(),
    subscribingMonitors: () => new Set(),
    unsubscribingMonitors: () => new Set(),
    loadingMonitors: () => new Set(),
    showSubscribeButton: true,
    showToggleButton: true,
    showPinButton: true,
    showAddButton: false,
    showUptimePercentage: true,
    showCertificateStatus: true,
    showLastChecked: true,
});

const page = usePage<SharedData>();
const { isPinned: isMonitorPinned, togglePin } = useBookmarks();

// Check if user is authenticated using Inertia's auth props
const isAuthenticated = computed(() => {
    return !!page.props.auth.user;
});

// Check if user is admin
const isAdmin = computed(() => {
    return page.props.auth.user?.is_admin || false;
});

// Bookmarks are initialized by parent components (PrivateMonitorsCard, PublicMonitorsCard, etc.)

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

const getStatusText = (status: string) => {
    switch (status) {
        case 'up':
            return 'Online';
        case 'down':
            return 'Offline';
        default:
            return 'Checking...';
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

const formatUptimePercentage = (percentage: number) => {
    return percentage.toFixed(1);
};

const getUptimePercentageColor = (percentage: number) => {
    if (percentage >= 99.5) return 'text-green-600 dark:text-green-400';
    if (percentage >= 95) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const openMonitorUrl = (url: string) => {
    window.open(url, '_blank');
};

const handleTogglePin = async () => {
    if (!isAuthenticated.value) {
        // Redirect to login if not authenticated
        window.location.href = '/login';
        return;
    }

    try {
        await togglePin(props.monitor.id);
    } catch (error) {
        console.error('Failed to toggle pin:', error);
        // You could show a toast notification here
    }
};

const handleToggleActive = () => {
    if (props.onToggleActive) {
        props.onToggleActive(props.monitor.id);
    }
};

const handleSubscribe = () => {
    if (props.onSubscribe) {
        props.onSubscribe(props.monitor.id);
    }
};

const handleUnsubscribe = () => {
    if (props.onUnsubscribe) {
        props.onUnsubscribe(props.monitor.id);
    }
};
</script>

<template>
    <div class="group relative cursor-pointer rounded-lg border p-0 transition-shadow hover:shadow-md">
        <Link
            :href="route('monitor.show', monitor.id)"
            class="block h-full w-full rounded-lg p-4 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            style="text-decoration: none; color: inherit"
        >
            <!-- Action Buttons - Top Right -->
            <div class="absolute top-2 right-2 flex items-center gap-1">
                <!-- Public View Button -->
                <Link
                    v-if="monitor.is_public && monitor.uptime_check_enabled"
                    :href="route('monitor.public.show', { domain: getDomainFromUrl(monitor.url) })"
                    @click.stop
                    class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    title="View public status page"
                >
                    <Icon name="external-link" size="16" />
                </Link>

                <!-- Pin Button -->
                <button
                    v-if="showPinButton && isAuthenticated"
                    @click.stop.prevent="handleTogglePin"
                    :disabled="props.loadingMonitors.has(monitor.id)"
                    :class="{
                        'text-yellow-500': isMonitorPinned(monitor.id),
                        'text-gray-400 hover:text-gray-600': !isMonitorPinned(monitor.id),
                        'cursor-not-allowed opacity-50': props.loadingMonitors.has(monitor.id),
                    }"
                    class="rounded-full p-1 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-gray-700"
                    :title="(props.isPinned ?? isMonitorPinned(monitor.id)) ? 'Unpin this monitor' : 'Pin this monitor'"
                >
                    <Icon
                        name="bookmark"
                        :class="(props.isPinned ?? isMonitorPinned(monitor.id)) ? 'fill-current text-amber-500' : 'text-gray-400'"
                        size="16"
                    />
                </button>
            </div>

            <div class="mb-2 flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <!-- favicon -->
                    <h3 class="flex items-center gap-2 truncate text-sm font-medium">
                        <img
                            v-if="monitor.favicon"
                            :src="monitor.favicon"
                            alt="Favicon"
                            class="h-4 w-4 rounded-full"
                            @click.stop.prevent="openMonitorUrl(monitor.url)"
                            @keydown.stop
                        />
                        <Link
                            v-if="monitor.is_public && monitor.uptime_check_enabled"
                            :href="route('monitor.public.show', { domain: getDomainFromUrl(monitor.url) })"
                            class="transition-colors hover:text-blue-600 dark:hover:text-blue-400"
                            @click.stop
                        >
                            {{ getDomainFromUrl(monitor.url) }}
                        </Link>
                        <span v-else>
                            {{ getDomainFromUrl(monitor.url) }}
                        </span>
                    </h3>
                    <span
                        class="block truncate text-xs text-blue-500 hover:underline"
                        @click.stop.prevent="openMonitorUrl(monitor.url)"
                        @keydown.stop
                    >
                        {{ monitor.url }}
                    </span>
                </div>
                <div class="ml-2 flex items-center">
                    <span
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.uptime_status === 'up',
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.uptime_status === 'down',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400':
                                monitor.uptime_status !== 'up' && monitor.uptime_status !== 'down',
                        }"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                    >
                        <Icon :name="getStatusIcon(monitor.uptime_status)" size="12" />
                        {{ getStatusText(monitor.uptime_status) }}
                    </span>
                </div>
            </div>

            <!-- Today's Uptime Percentage -->
            <div v-if="showUptimePercentage && monitor.today_uptime_percentage !== undefined" class="mb-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Today's Uptime:</span>
                    <span :class="getUptimePercentageColor(monitor.today_uptime_percentage)" class="text-xs font-medium">
                        {{ formatUptimePercentage(monitor.today_uptime_percentage) }}%
                    </span>
                </div>
                <!-- Progress bar -->
                <div class="mt-1 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        :class="{
                            'bg-green-500': monitor.today_uptime_percentage >= 99.5,
                            'bg-yellow-500': monitor.today_uptime_percentage >= 95 && monitor.today_uptime_percentage < 99.5,
                            'bg-red-500': monitor.today_uptime_percentage < 95,
                        }"
                        class="h-1.5 rounded-full transition-all duration-300"
                        :style="{ width: `${monitor.today_uptime_percentage}%` }"
                    ></div>
                </div>
            </div>

            <div class="space-y-1 text-xs text-gray-500">
                <!-- Certificate Status -->
                <div v-if="showCertificateStatus && monitor.certificate_check_enabled" class="flex items-center gap-1">
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <p
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':
                                            monitor.certificate_status === 'valid',
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.certificate_status === 'invalid',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400':
                                            monitor.certificate_status === 'not applicable',
                                    }"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                >
                                    <span
                                        v-if="monitor.certificate_status !== undefined"
                                        :class="[
                                            'mr-1 flex items-center rounded-full py-0.5 text-xs font-semibold uppercase',
                                            monitor.certificate_status === 'valid'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                : monitor.certificate_status === 'invalid'
                                                  ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                  : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                        ]"
                                    >
                                        <Icon
                                            :name="
                                                monitor.certificate_status === 'valid'
                                                    ? 'lock'
                                                    : monitor.certificate_status === 'invalid'
                                                      ? 'lockOpen'
                                                      : 'alertTriangle'
                                            "
                                            class="h-4 w-4"
                                        />
                                    </span>
                                    SSL
                                    {{ monitor.certificate_status }}
                                </p>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-sm">
                                    {{
                                        monitor.certificate_status === 'valid'
                                            ? 'Certificate is valid'
                                            : monitor.certificate_status === 'invalid'
                                              ? 'Certificate is invalid'
                                              : 'Certificate check not applicable'
                                    }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>

                <!-- Last Checked -->
                <div
                    v-if="showLastChecked && monitor.last_check_date_human"
                    :title="`Last checked: ${monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : ''}`"
                >
                    Last checked: {{ monitor.last_check_date_human }}
                </div>
            </div>

            <div
                v-if="(showSubscribeButton && type === 'public') || (showToggleButton && monitor.is_subscribed && isAdmin && type === 'public')"
                class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700"
            >
                <div class="flex items-center gap-3">
                    <!-- Subscribe/Unsubscribe Button -->
                    <div v-if="showSubscribeButton && type === 'public'" class="flex-1">
                        <Button
                            v-if="!monitor.is_subscribed"
                            @click.stop.prevent="handleSubscribe"
                            :disabled="subscribingMonitors.has(monitor.id)"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-600 transition-colors hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50"
                            :title="isAuthenticated ? 'Subscribe to this monitor' : 'Login to subscribe'"
                        >
                            <span class="flex items-center gap-2">
                                <Plus class="h-4 w-4" />
                                <span v-if="subscribingMonitors.has(monitor.id)"> Subscribing... </span>
                                <span v-else> Subscribe </span>
                            </span>
                        </Button>
                        <Button
                            v-else
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                            @click.stop.prevent="handleUnsubscribe"
                            :disabled="unsubscribingMonitors.has(monitor.id)"
                            title="Unsubscribe from this monitor"
                        >
                            <span class="flex items-center gap-1">
                                <Minus class="h-3 w-3" />
                                <span v-if="unsubscribingMonitors.has(monitor.id)"> Unsubscribing... </span>
                                <span v-else> Unsubscribe </span>
                            </span>
                        </Button>
                    </div>

                    <!-- Toggle Uptime Check Button -->
                    <div v-if="showToggleButton && monitor.is_subscribed && isAdmin && type === 'public'" class="flex items-center gap-2">
                        <span class="text-xs whitespace-nowrap text-gray-600 dark:text-gray-400">Uptime Check:</span>
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Switch
                                        :model-value="monitor.uptime_check_enabled"
                                        :disabled="togglingMonitors.has(monitor.id)"
                                        @update:model-value="handleToggleActive"
                                        @click.stop.prevent
                                    />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p class="text-sm">
                                        {{ monitor.uptime_check_enabled ? 'Disable uptime check' : 'Enable uptime check' }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>
