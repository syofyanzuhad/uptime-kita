<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useBookmarks } from '@/composables/useBookmarks';
import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bookmark,
    CheckCircle2,
    Clock,
    ExternalLink,
    Globe,
    Lock,
    Minus,
    Plus,
    ShieldAlert,
    ShieldCheck,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
const faviconError = ref(false);

const isAuthenticated = computed(() => {
    return !!page.props.auth.user;
});

const isAdmin = computed(() => {
    return page.props.auth.user?.is_admin || false;
});

const isCurrentlyPinned = computed(() => {
    return props.isPinned ?? isMonitorPinned(props.monitor.id);
});

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
    if (percentage >= 99.5) return 'text-emerald-600 dark:text-emerald-400 font-semibold';
    if (percentage >= 95) return 'text-amber-600 dark:text-amber-400 font-semibold';
    return 'text-rose-600 dark:text-rose-400 font-semibold';
};

const getUptimeBarColor = (percentage: number) => {
    if (percentage >= 99.5) return 'bg-emerald-500';
    if (percentage >= 95) return 'bg-amber-500';
    return 'bg-rose-500';
};

const openMonitorUrl = (url: string) => {
    window.open(url, '_blank');
};

const handleTogglePin = async () => {
    if (!isAuthenticated.value) {
        window.location.href = '/login';
        return;
    }

    try {
        await togglePin(props.monitor.id);
    } catch (error) {
        console.error('Failed to toggle pin:', error);
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
    <div
        class="group relative flex flex-col justify-between overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/90 dark:hover:border-gray-700"
    >
        <Link
            :href="route('monitor.show', monitor.id)"
            class="flex flex-1 flex-col p-4 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            style="text-decoration: none; color: inherit"
        >
            <!-- Top Bar: Domain & Action Icons -->
            <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <!-- Favicon or Placeholder -->
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                        <img
                            v-if="monitor.favicon && !faviconError"
                            :src="monitor.favicon"
                            alt="Favicon"
                            class="h-4 w-4 rounded-sm object-contain"
                            @error="faviconError = true"
                            @click.stop.prevent="openMonitorUrl(monitor.url)"
                        />
                        <Globe v-else class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                    </div>

                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400">
                            {{ getDomainFromUrl(monitor.url) }}
                        </h3>
                        <span
                            class="block truncate text-xs text-gray-500 transition-colors hover:text-blue-500 hover:underline dark:text-gray-400 dark:hover:text-blue-400"
                            @click.stop.prevent="openMonitorUrl(monitor.url)"
                        >
                            {{ monitor.url }}
                        </span>
                    </div>
                </div>

                <!-- Top Right Status & Action Pill -->
                <div class="flex shrink-0 items-center gap-1">
                    <!-- Status Badge with Pulse Dot -->
                    <span
                        v-if="monitor.uptime_status === 'up'"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50/80 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-400"
                    >
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Online
                    </span>
                    <span
                        v-else-if="monitor.uptime_status === 'down'"
                        class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/80 bg-rose-50/80 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-400"
                    >
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                        </span>
                        Offline
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-200/80 bg-amber-50/80 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-400"
                    >
                        <Clock class="h-3 w-3 text-amber-500 animate-spin" />
                        Checking
                    </span>

                    <!-- Public Status Page Link -->
                    <TooltipProvider :delay-duration="200">
                        <Tooltip v-if="monitor.is_public && monitor.uptime_check_enabled">
                            <TooltipTrigger as-child>
                                <Link
                                    :href="route('monitor.public.show', { domain: getDomainFromUrl(monitor.url) })"
                                    @click.stop
                                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" />
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-xs">View public status page</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <!-- Pin / Bookmark Button -->
                    <TooltipProvider :delay-duration="200">
                        <Tooltip v-if="showPinButton && isAuthenticated">
                            <TooltipTrigger as-child>
                                <button
                                    @click.stop.prevent="handleTogglePin"
                                    :disabled="props.loadingMonitors.has(monitor.id)"
                                    class="rounded-lg p-1.5 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-gray-800"
                                    :class="isCurrentlyPinned ? 'text-amber-500' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                >
                                    <Bookmark
                                        class="h-3.5 w-3.5"
                                        :class="isCurrentlyPinned ? 'fill-amber-500 text-amber-500' : ''"
                                    />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-xs">{{ isCurrentlyPinned ? 'Unpin monitor' : 'Pin to top' }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>

            <!-- Middle: Today's Uptime Percentage & Progress Bar -->
            <div v-if="showUptimePercentage && monitor.today_uptime_percentage !== undefined" class="my-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Today's Uptime</span>
                    <span :class="getUptimePercentageColor(monitor.today_uptime_percentage)">
                        {{ formatUptimePercentage(monitor.today_uptime_percentage) }}%
                    </span>
                </div>
                <!-- Progress track -->
                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div
                        :class="getUptimeBarColor(monitor.today_uptime_percentage)"
                        class="h-full rounded-full transition-all duration-500 ease-out"
                        :style="{ width: `${Math.min(Math.max(monitor.today_uptime_percentage, 0), 100)}%` }"
                    ></div>
                </div>
            </div>

            <!-- Badges & Metadata Row -->
            <div class="mt-auto flex flex-wrap items-center gap-2 pt-2 text-xs text-gray-500 dark:text-gray-400">
                <!-- Certificate Status -->
                <TooltipProvider v-if="showCertificateStatus && monitor.certificate_check_enabled" :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-medium transition-colors',
                                    monitor.certificate_status === 'valid'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                        : monitor.certificate_status === 'invalid'
                                          ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                                          : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                ]"
                            >
                                <ShieldCheck v-if="monitor.certificate_status === 'valid'" class="h-3 w-3 text-emerald-500" />
                                <ShieldAlert v-else-if="monitor.certificate_status === 'invalid'" class="h-3 w-3 text-rose-500" />
                                <Lock v-else class="h-3 w-3 text-gray-400" />
                                <span>SSL {{ monitor.certificate_status }}</span>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">
                                {{
                                    monitor.certificate_status === 'valid'
                                        ? 'SSL Certificate is valid'
                                        : monitor.certificate_status === 'invalid'
                                          ? 'SSL Certificate is invalid or expired'
                                          : 'Certificate check not applicable'
                                }}
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <!-- Last Checked -->
                <div
                    v-if="showLastChecked && monitor.last_check_date_human"
                    class="flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500"
                    :title="`Last checked: ${monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : ''}`"
                >
                    <Clock class="h-3 w-3" />
                    <span>{{ monitor.last_check_date_human }}</span>
                </div>
            </div>
        </Link>

        <!-- Bottom Action Bar (if public subscribe / admin toggle active) -->
        <div
            v-if="(showSubscribeButton && type === 'public') || (showToggleButton && monitor.is_subscribed && isAdmin && type === 'public')"
            class="border-t border-gray-100 bg-gray-50/50 px-4 py-2.5 dark:border-gray-800/80 dark:bg-gray-900/40"
        >
            <div class="flex items-center justify-between gap-3">
                <!-- Subscribe/Unsubscribe Button -->
                <div v-if="showSubscribeButton && type === 'public'" class="flex-1">
                    <Button
                        v-if="!monitor.is_subscribed"
                        size="sm"
                        variant="outline"
                        @click.stop.prevent="handleSubscribe"
                        :disabled="subscribingMonitors.has(monitor.id)"
                        class="h-8 w-full border-emerald-200 bg-emerald-50/50 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400 dark:hover:bg-emerald-950/60"
                        :title="isAuthenticated ? 'Subscribe to notifications' : 'Login to subscribe'"
                    >
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        <span v-if="subscribingMonitors.has(monitor.id)">Subscribing...</span>
                        <span v-else>Subscribe</span>
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        variant="outline"
                        @click.stop.prevent="handleUnsubscribe"
                        :disabled="unsubscribingMonitors.has(monitor.id)"
                        class="h-8 w-full border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="Unsubscribe from this monitor"
                    >
                        <Minus class="mr-1.5 h-3.5 w-3.5" />
                        <span v-if="unsubscribingMonitors.has(monitor.id)">Unsubscribing...</span>
                        <span v-else>Subscribed</span>
                    </Button>
                </div>

                <!-- Toggle Uptime Check Switch -->
                <div
                    v-if="showToggleButton && monitor.is_subscribed && isAdmin && type === 'public'"
                    class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"
                >
                    <span class="whitespace-nowrap">Monitor:</span>
                    <TooltipProvider :delay-duration="200">
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
                                <p class="text-xs">
                                    {{ monitor.uptime_check_enabled ? 'Disable active uptime checks' : 'Enable active uptime checks' }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>
        </div>
    </div>
</template>

