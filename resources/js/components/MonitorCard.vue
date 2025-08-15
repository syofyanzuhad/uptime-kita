<script setup lang="ts">
import { computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Switch } from '@/components/ui/switch';
import Button from './ui/button/Button.vue';
import { Plus, Minus } from 'lucide-vue-next';
import type { Monitor } from '@/types/monitor';
import { Link, usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import { useBookmarks } from '@/composables/useBookmarks';

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

// Initialize bookmarks on component mount
onMounted(() => {
    // Initialize bookmarks if not already done
    if (typeof window !== 'undefined') {
        const { initialize } = useBookmarks();
        initialize();
    }
});

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
    <div class="relative group border rounded-lg hover:shadow-md transition-shadow cursor-pointer p-0">
        <Link
            :href="route('monitor.show', monitor.id)"
            class="block p-4 w-full h-full focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg"
            style="text-decoration: none; color: inherit;"
        >
            <!-- Pin Button - Top Right -->
            <button
                v-if="showPinButton && isAuthenticated"
                @click.stop.prevent="handleTogglePin"
                :disabled="props.loadingMonitors.has(monitor.id)"
                :class="{
                    'text-yellow-500': isMonitorPinned(monitor.id),
                    'text-gray-400 hover:text-gray-600': !isMonitorPinned(monitor.id),
                    'opacity-50 cursor-not-allowed': props.loadingMonitors.has(monitor.id)
                }"
                class="absolute top-2 right-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :title="isMonitorPinned(monitor.id) ? 'Unpin this monitor' : 'Pin this monitor'"
            >
                <Icon
                    name="bookmark"
                    :class="isMonitorPinned(monitor.id) ? 'fill-current' : ''"
                    size="16"
                />
            </button>

            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <!-- favicon -->
                    <h3 class="font-medium text-sm truncate flex items-center gap-2">
                        <img
                            v-if="monitor.favicon"
                            :src="monitor.favicon"
                            alt="Favicon"
                            class="w-4 h-4 rounded-full"
                            @click.stop.prevent="openMonitorUrl(monitor.url)"
                            @keydown.stop
                        />
                        {{ getDomainFromUrl(monitor.url) }}
                    </h3>
                    <span
                        class="text-xs text-blue-500 hover:underline truncate block"
                        @click.stop.prevent="openMonitorUrl(monitor.url)"
                        @keydown.stop
                    >
                        {{ monitor.url }}
                    </span>
                </div>
                <div class="flex items-center ml-2">
                    <span
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.uptime_status === 'up',
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.uptime_status === 'down',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': monitor.uptime_status !== 'up' && monitor.uptime_status !== 'down'
                        }"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                    >
                        <Icon
                            :name="getStatusIcon(monitor.uptime_status)"
                            size="12"
                        />
                        {{ getStatusText(monitor.uptime_status) }}
                    </span>
                </div>
            </div>

            <!-- Today's Uptime Percentage -->
            <div v-if="showUptimePercentage && monitor.today_uptime_percentage !== undefined" class="mb-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Today's Uptime:</span>
                    <span
                        :class="getUptimePercentageColor(monitor.today_uptime_percentage)"
                        class="text-xs font-medium"
                    >
                        {{ formatUptimePercentage(monitor.today_uptime_percentage) }}%
                    </span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-1">
                    <div
                        :class="{
                            'bg-green-500': monitor.today_uptime_percentage >= 99.5,
                            'bg-yellow-500': monitor.today_uptime_percentage >= 95 && monitor.today_uptime_percentage < 99.5,
                            'bg-red-500': monitor.today_uptime_percentage < 95
                        }"
                        class="h-1.5 rounded-full transition-all duration-300"
                        :style="{ width: `${monitor.today_uptime_percentage}%` }"
                    ></div>
                </div>
            </div>

            <div class="text-xs text-gray-500 space-y-1">
                <!-- Certificate Status -->
                <div v-if="showCertificateStatus && monitor.certificate_check_enabled" class="flex items-center gap-1">
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <p
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': monitor.certificate_status === 'valid',
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': monitor.certificate_status === 'invalid',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': monitor.certificate_status === 'not applicable'
                                    }"
                                    class="inline-flex uppercase items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                >
                                    <span
                                        v-if="monitor.certificate_status !== undefined"
                                        :class="[
                                            'py-0.5 rounded-full text-xs font-semibold uppercase flex items-center mr-1',
                                            monitor.certificate_status === 'valid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                            monitor.certificate_status === 'invalid' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                        ]"
                                    >
                                        <Icon :name="
                                            monitor.certificate_status === 'valid' ? 'lock' :
                                            monitor.certificate_status === 'invalid' ? 'lockOpen' :
                                            'alertTriangle'
                                        " class="w-4 h-4" />
                                    </span>
                                    SSL
                                    {{ monitor.certificate_status }}
                                </p>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-sm">
                                    {{ monitor.certificate_status === 'valid' ? 'Certificate is valid' :
                                        monitor.certificate_status === 'invalid' ? 'Certificate is invalid' :
                                        'Certificate check not applicable' }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>

                <!-- Last Checked -->
                <div v-if="showLastChecked && monitor.last_check_date_human" :title="`Last checked: ${monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : ''}`">
                    Last checked: {{ monitor.last_check_date_human }}
                </div>
            </div>

            <div v-if="(showSubscribeButton && type === 'public') || (showToggleButton && monitor.is_subscribed && isAdmin && type === 'public')" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <!-- Subscribe/Unsubscribe Button -->
                    <div v-if="showSubscribeButton && type === 'public'" class="flex-1">
                        <Button
                            v-if="!monitor.is_subscribed"
                            @click.stop.prevent="handleSubscribe"
                            :disabled="subscribingMonitors.has(monitor.id)"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-600 dark:text-green-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :title="isAuthenticated ? 'Subscribe to this monitor' : 'Login to subscribe'"
                        >
                            <span class="flex items-center gap-2">
                                <Plus class="h-4 w-4" />
                                <span v-if="subscribingMonitors.has(monitor.id)">
                                    Subscribing...
                                </span>
                                <span v-else>
                                    Subscribe
                                </span>
                            </span>
                        </Button>
                        <Button
                            v-else
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg"
                            @click.stop.prevent="handleUnsubscribe"
                            :disabled="unsubscribingMonitors.has(monitor.id)"
                            title="Unsubscribe from this monitor"
                        >
                            <span class="flex items-center gap-1">
                                <Minus class="h-3 w-3" />
                                <span v-if="unsubscribingMonitors.has(monitor.id)">
                                    Unsubscribing...
                                </span>
                                <span v-else>
                                    Unsubscribe
                                </span>
                            </span>
                        </Button>
                    </div>

                    <!-- Toggle Uptime Check Button -->
                    <div v-if="showToggleButton && monitor.is_subscribed && isAdmin && type === 'public'" class="flex items-center gap-2">
                        <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">Uptime Check:</span>
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
