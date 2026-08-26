<script setup lang="ts">
import DailyUptimeChart from '@/components/DailyUptimeChart.vue';
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { formatRelativeTime, getStatusColor, getStatusText, getStatusTextColor } from '@/composables/useMonitorHelpers';
import { useMonitorStatusStream } from '@/composables/useMonitorStatusStream';
import { globalToasts } from '@/composables/useToastNotifications';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface MonitorHistory { id: number; monitor_id: number; uptime_status: string; message: string; created_at: string; updated_at: string; }
interface Monitor { id: number; name: string; url: string; host: string; uptime_status: string; uptime_check_enabled: boolean; favicon?: string | null; last_check_date?: string | null; certificate_check_enabled: boolean; certificate_status?: string | null; certificate_expiration_date?: string | null; domain_expiration_check_enabled: boolean; domain_expiration_date?: string | null; domain_expiration_lookup_error?: string | null; down_for_events_count: number; uptime_check_interval: number; is_subscribed: boolean; is_public: boolean; today_uptime_percentage: number; uptime_status_last_change_date?: string | null; created_at: string; updated_at: string; histories?: MonitorHistory[]; latest_history?: MonitorHistory | null; uptimes_daily?: { date: string; uptime_percentage: number }[]; }
interface StatusPage { id: number; title: string; description: string; icon: string; path: string; created_at: string; updated_at: string; monitors: Monitor[]; }
interface Props { statusPage: StatusPage; isAuthenticated: boolean; appUrl?: string; }
const props = defineProps<Props>();

const appUrl = computed(() => props.appUrl || window.location.origin);
const pageTitle = computed(() => `${props.statusPage.title} - Status Page | Uptime Kita`);
const pageDescription = computed(() => props.statusPage.description || `Status page for ${props.statusPage.title}. Real-time service status.`);
const shareUrl = computed(() => `${appUrl.value}/status/${props.statusPage.path}`);
const shareText = computed(() => {
    const up = monitors.value.filter((m) => (latestHistory.value[m.id]?.uptime_status || m.uptime_status)?.toLowerCase() === 'up').length;
    return `${props.statusPage.title}: ${overallStatus.value.text} (${up}/${monitors.value.length} services up)`;
});
const ogImage = computed(() => `${appUrl.value}/og/status/${props.statusPage.path}.png`);
const jsonLd = computed(() => ({ '@context': 'https://schema.org', '@type': 'WebPage', name: props.statusPage.title, description: pageDescription.value, url: shareUrl.value }));

const monitors = ref<Monitor[]>([]);
const monitorsLoading = ref(true);
const monitorsError = ref<string | null>(null);
const uptimesDaily = ref<Record<number, { date: string; uptime_percentage: number }[]>>({});
const latestHistory = ref<Record<number, MonitorHistory | null>>({});

useMonitorStatusStream({
    statusPageId: props.statusPage.id, enabled: true,
    onStatusChange: (change) => {
        globalToasts.addStatusChangeToast(change);
        const idx = monitors.value.findIndex((m) => m.id === change.monitor_id);
        if (idx !== -1) monitors.value[idx] = { ...monitors.value[idx], uptime_status: change.new_status };
        if (latestHistory.value[change.monitor_id]) latestHistory.value[change.monitor_id] = { ...latestHistory.value[change.monitor_id]!, uptime_status: change.new_status };
    },
});

async function fetchMonitors() {
    monitorsLoading.value = true; monitorsError.value = null;
    try {
        const res = await fetch(`/status/${props.statusPage.path}/monitors`);
        if (res.status === 404) throw new Error('Status page not found');
        if (!res.ok) throw new Error('Failed to load monitors');
        const data = await res.json();
        const raw: any[] = Array.isArray(data) ? data : data.data || [];
        monitors.value = raw;
        raw.forEach((m: any) => {
            if (m.latest_history) latestHistory.value[m.id] = m.latest_history;
            if (m.uptimes_daily) uptimesDaily.value[m.id] = m.uptimes_daily;
        });
    } catch (e: any) { monitorsError.value = e.message || 'Unknown error'; }
    finally { monitorsLoading.value = false; }
}

const formatDate = (d?: string) => d ? new Date(d).toLocaleString(navigator.language || 'en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '';
const timeAgo = (d?: string) => {
    if (!d) return '';
    const s = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
    if (s < 30) return 'just now';
    if (s < 60) return `${s} seconds ago`;
    if (s < 3600) return `${Math.floor(s / 60)} minutes ago`;
    if (s < 86400) return `${Math.floor(s / 3600)} hours ago`;
    if (s < 2592000) return `${Math.floor(s / 86400)} days ago`;
    if (s < 31536000) return `${Math.floor(s / 2592000)} months ago`;
    return `${Math.floor(s / 31536000)} years ago`;
};
const getCertStatusColor = (s?: string | null) => {
    switch (s?.toLowerCase()) { case 'valid': return 'bg-green-100 text-green-800'; case 'expiring soon': return 'bg-yellow-100 text-yellow-800'; case 'invalid': case 'expired': return 'bg-red-100 text-red-800'; default: return 'bg-gray-100 text-gray-800'; }
};
const getDomainExpirationColor = (d?: string | null) => {
    if (!d) return 'bg-gray-100 text-gray-800';
    const days = Math.ceil((new Date(d).getTime() - Date.now()) / 86400000);
    if (days < 0) return 'bg-red-100 text-red-800'; if (days <= 30) return 'bg-yellow-100 text-yellow-800'; return 'bg-green-100 text-green-800';
};
const getDomainExpirationLabel = (d?: string | null) => {
    if (!d) return ''; const days = Math.ceil((new Date(d).getTime() - Date.now()) / 86400000);
    if (days < 0) return 'Domain expired'; if (days === 0) return 'Domain expires today'; return `Domain ${days}d`;
};

const overallStatus = computed(() => {
    if (!monitors.value.length) return { color: 'bg-green-500', text: 'All Systems Operational' };
    const hasDown = monitors.value.some((m) => (latestHistory.value[m.id]?.uptime_status || m.uptime_status)?.toLowerCase() === 'down');
    const hasWarning = monitors.value.some((m) => (latestHistory.value[m.id]?.uptime_status || m.uptime_status)?.toLowerCase() === 'warning');
    if (hasDown) return { color: 'bg-red-500', text: 'Some Systems Are Down' };
    if (hasWarning) return { color: 'bg-yellow-500', text: 'Some Systems Are Degraded' };
    return { color: 'bg-green-500', text: 'All Systems Operational' };
});

const isFullscreen = ref(false);
function toggleFullscreen() {
    const el = document.documentElement as any;
    if (!isFullscreen.value) { (el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen)?.call(el); }
    else { (document.exitFullscreen || (document as any).webkitExitFullscreen || (document as any).msExitFullscreen)?.call(document); }
}
function onFsChange() { isFullscreen.value = !!(document.fullscreenElement || (document as any).webkitFullscreenElement); }

onMounted(() => {
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);
    fetchMonitors();
});
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :og-image="ogImage" :canonical-url="shareUrl" :share-url="shareUrl" :share-text="shareText" :json-ld="jsonLd">
        <template #header-left>
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900"><Icon :name="statusPage.icon" class="h-5 w-5 text-blue-600 dark:text-blue-400" /></div>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-gray-900 dark:text-gray-100 sm:text-2xl">{{ statusPage.title }}</h1>
                <p class="line-clamp-2 text-sm text-gray-600 dark:text-gray-300" :title="statusPage.description">{{ statusPage.description }}</p>
            </div>
            <button @click="toggleFullscreen" class="ml-2 rounded-full border border-gray-200 bg-white p-2 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700" :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"><Icon :name="isFullscreen ? 'minimize' : 'maximize'" class="h-4 w-4 text-gray-600 dark:text-gray-200" /></button>
        </template>

        <!-- System Status -->
        <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">System Status</h2>
            <div class="flex items-center gap-3">
                <div role="status" :aria-label="overallStatus.text" class="h-4 w-4 animate-pulse rounded-full" :class="overallStatus.color" />
                <span class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ overallStatus.text }}</span>
            </div>
        </div>

        <!-- Services -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Services</h3></div>

            <div v-if="monitorsError" class="p-12 text-center">
                <Icon name="alert-circle" class="mx-auto h-16 w-16 text-red-500" />
                <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ monitorsError === 'Status page not found' ? '404 - Page Not Found' : 'Error' }}</h3>
                <p class="mx-auto mt-2 max-w-md text-gray-600 dark:text-gray-400">{{ monitorsError === 'Status page not found' ? 'Status page does not exist or has been removed.' : monitorsError }}</p>
            </div>

            <div v-else-if="monitorsLoading" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="i in 3" :key="i" class="px-4 py-4 sm:px-6"><Skeleton class="h-5 w-1/3" /><Skeleton class="mt-2 h-4 w-1/2" /></div>
            </div>

            <div v-else-if="monitors.length === 0" class="p-12 text-center">
                <Icon name="inbox" class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-4 text-gray-600 dark:text-gray-400">No services configured for this status page.</p>
            </div>

            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="monitor in monitors" :key="monitor.id" class="group relative px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <img v-if="monitor.favicon" :src="monitor.favicon" class="h-5 w-5 rounded-full" alt="" @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')" />
                            <div v-else class="h-5 w-5 rounded-full bg-gray-200 dark:bg-gray-700" />
                            <div role="status" :aria-label="getStatusText(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status)" class="h-3 w-3 flex-shrink-0 animate-pulse rounded-full" :class="getStatusColor(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status)" />
                            <div class="min-w-0 flex-1">
                                <h4 class="flex flex-wrap items-center font-medium text-gray-900 dark:text-gray-100">
                                    <Link :href="'/m/' + monitor.host" class="after:absolute after:inset-0 hover:text-blue-600 dark:hover:text-blue-400">{{ monitor.name }}</Link>
                                    <span v-if="monitor.certificate_check_enabled && monitor.certificate_status" class="ml-2 inline-flex items-center gap-1 rounded-full px-1 py-0.5 text-xs font-semibold uppercase" :class="getCertStatusColor(monitor.certificate_status)"><span class="sr-only">SSL {{ monitor.certificate_status }}</span>{{ monitor.certificate_status }}</span>
                                    <span v-if="monitor.domain_expiration_check_enabled && monitor.domain_expiration_date" class="ml-2 inline-flex items-center gap-1 rounded-full px-1 py-0.5 text-xs font-semibold uppercase" :class="getDomainExpirationColor(monitor.domain_expiration_date)">{{ getDomainExpirationLabel(monitor.domain_expiration_date) }}</span>
                                </h4>
                                <a class="relative z-20 block break-all text-sm text-gray-500 hover:underline dark:text-gray-400" :href="monitor.url" target="_blank">{{ monitor.url }}</a>
                            </div>
                        </div>
                        <div class="ml-0 flex-shrink-0 text-right sm:ml-4">
                            <div class="text-sm font-medium" :class="getStatusTextColor(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status)">{{ getStatusText(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status) }}</div>
                            <div v-if="latestHistory[monitor.id]?.created_at || monitor.last_check_date" class="text-xs text-gray-500 dark:text-gray-400" :title="formatDate(latestHistory[monitor.id]?.created_at || monitor.last_check_date || undefined)">Last check: {{ timeAgo(latestHistory[monitor.id]?.created_at || monitor.last_check_date || undefined) }}</div>
                        </div>
                    </div>
                    <DailyUptimeChart :monitor-id="monitor.id" :is-authenticated="props.isAuthenticated" :uptimes-daily="uptimesDaily[monitor.id]" />
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
