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
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-2 text-white shadow-md shadow-blue-500/20">
                    <Icon :name="statusPage.icon || 'activity'" class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-extrabold text-gray-900 dark:text-white sm:text-xl">{{ statusPage.title }}</h1>
                    <p class="line-clamp-1 text-xs text-gray-500 dark:text-gray-400" :title="statusPage.description">{{ statusPage.description }}</p>
                </div>
            </div>
        </template>

        <template #header-actions>
            <button
                @click="toggleFullscreen"
                class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/80 px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:bg-gray-700"
                :aria-label="isFullscreen ? 'Exit kiosk mode' : 'Enter kiosk mode'"
            >
                <Icon :name="isFullscreen ? 'minimize' : 'maximize'" class="h-3.5 w-3.5" />
                <span>{{ isFullscreen ? 'Exit Kiosk' : 'Kiosk Mode' }}</span>
            </button>
        </template>

        <!-- System Overall Status Hero -->
        <div
            class="mb-8 overflow-hidden rounded-3xl border p-6 sm:p-8 shadow-sm backdrop-blur-md transition-all duration-300"
            :class="[
                overallStatus.text.includes('Operational')
                    ? 'border-emerald-200/80 bg-gradient-to-r from-emerald-50/80 via-teal-50/40 to-white dark:border-emerald-900/50 dark:from-emerald-950/30 dark:via-gray-900 dark:to-gray-900'
                    : overallStatus.text.includes('Down')
                      ? 'border-rose-200/80 bg-gradient-to-r from-rose-50/80 via-orange-50/40 to-white dark:border-rose-900/50 dark:from-rose-950/30 dark:via-gray-900 dark:to-gray-900'
                      : 'border-amber-200/80 bg-gradient-to-r from-amber-50/80 via-yellow-50/40 to-white dark:border-amber-900/50 dark:from-amber-950/30 dark:via-gray-900 dark:to-gray-900',
            ]"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-md text-white"
                        :class="[
                            overallStatus.text.includes('Operational')
                                ? 'bg-emerald-500 shadow-emerald-500/20'
                                : overallStatus.text.includes('Down')
                                  ? 'bg-rose-500 shadow-rose-500/20'
                                  : 'bg-amber-500 shadow-amber-500/20',
                        ]"
                    >
                        <Icon :name="overallStatus.text.includes('Operational') ? 'checkCircle' : 'alertTriangle'" class="h-8 w-8" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                                {{ overallStatus.text }}
                            </h2>
                        </div>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 sm:text-sm">
                            Real-time service health verified across all operational nodes.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-2xl border border-gray-200/80 bg-white/90 px-4 py-2 text-center shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-800/90">
                        <span class="block text-lg font-extrabold text-gray-900 dark:text-white">
                            {{ monitors.filter(m => (latestHistory[m.id]?.uptime_status || m.uptime_status)?.toLowerCase() === 'up').length }} / {{ monitors.length }}
                        </span>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Services Online</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services List -->
        <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Tracked Services</h3>
                <span class="text-xs text-gray-400 font-medium">{{ monitors.length }} Monitored Components</span>
            </div>

            <div v-if="monitorsError" class="p-12 text-center">
                <Icon name="alertCircle" class="mx-auto h-12 w-12 text-rose-500" />
                <h4 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">{{ monitorsError === 'Status page not found' ? '404 - Page Not Found' : 'Error Loading Status' }}</h4>
                <p class="mx-auto mt-1 max-w-md text-xs text-gray-500 dark:text-gray-400">{{ monitorsError === 'Status page not found' ? 'This status page does not exist or has been removed.' : monitorsError }}</p>
            </div>

            <div v-else-if="monitorsLoading" class="divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="i in 3" :key="i" class="p-6">
                    <Skeleton class="h-5 w-1/3 rounded-lg" />
                    <Skeleton class="mt-2 h-4 w-1/2 rounded-md" />
                </div>
            </div>

            <div v-else-if="monitors.length === 0" class="p-12 text-center">
                <Icon name="inbox" class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-3 text-sm text-gray-500">No services configured on this status page yet.</p>
            </div>

            <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="monitor in monitors" :key="monitor.id" class="group p-5 sm:p-6 transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <img
                                v-if="monitor.favicon"
                                :src="monitor.favicon"
                                class="h-6 w-6 rounded-lg object-contain drop-shadow-sm"
                                alt=""
                                @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')"
                            />
                            <div v-else class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 font-mono text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ monitor.name?.slice(0, 2).toUpperCase() || 'UK' }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Link :href="'/m/' + monitor.host" class="text-base font-bold text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400 transition-colors">
                                        {{ monitor.name }}
                                    </Link>
                                    <span v-if="monitor.certificate_check_enabled && monitor.certificate_status" class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="getCertStatusColor(monitor.certificate_status)">
                                        SSL {{ monitor.certificate_status }}
                                    </span>
                                    <span v-if="monitor.domain_expiration_check_enabled && monitor.domain_expiration_date" class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="getDomainExpirationColor(monitor.domain_expiration_date)">
                                        {{ getDomainExpirationLabel(monitor.domain_expiration_date) }}
                                    </span>
                                </div>
                                <a class="block truncate text-xs text-gray-400 hover:text-blue-500 transition-colors" :href="monitor.url" target="_blank" rel="noopener">{{ monitor.url }}</a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                            <div class="text-left sm:text-right">
                                <div class="text-sm font-bold" :class="getStatusTextColor(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status)">
                                    {{ getStatusText(latestHistory[monitor.id]?.uptime_status || monitor.uptime_status) }}
                                </div>
                                <div v-if="latestHistory[monitor.id]?.created_at || monitor.last_check_date" class="text-[11px] text-gray-400" :title="formatDate(latestHistory[monitor.id]?.created_at || monitor.last_check_date || undefined)">
                                    Checked {{ timeAgo(latestHistory[monitor.id]?.created_at || monitor.last_check_date || undefined) }}
                                </div>
                            </div>

                            <span
                                role="status"
                                :class="[
                                    'flex h-3.5 w-3.5 items-center justify-center rounded-full',
                                    (latestHistory[monitor.id]?.uptime_status || monitor.uptime_status) === 'up'
                                        ? 'bg-emerald-500 ring-4 ring-emerald-500/20'
                                        : (latestHistory[monitor.id]?.uptime_status || monitor.uptime_status) === 'down'
                                          ? 'bg-rose-500 ring-4 ring-rose-500/20 animate-ping'
                                          : 'bg-amber-500 ring-4 ring-amber-500/20',
                                ]"
                            />
                        </div>
                    </div>

                    <!-- 90-Day Daily Chart -->
                    <div class="mt-4">
                        <DailyUptimeChart :monitor-id="monitor.id" :is-authenticated="props.isAuthenticated" :uptimes-daily="uptimesDaily[monitor.id]" />
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
