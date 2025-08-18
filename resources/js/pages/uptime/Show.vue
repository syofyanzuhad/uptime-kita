<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { Monitor, MonitorHistory } from '@/types/monitor';
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import Tooltip from '@/components/ui/tooltip/Tooltip.vue';
import TooltipTrigger from '@/components/ui/tooltip/TooltipTrigger.vue';
import TooltipContent from '@/components/ui/tooltip/TooltipContent.vue';
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import axios from 'axios';

const props = defineProps<{
  monitor: { data: Monitor };
}>();

const monitorData = computed(() => props.monitor.data);

const breadcrumbs = [
  { title: 'Uptime Monitor', href: '/monitor' },
  { title: getDomainFromUrl(monitorData.value.url), href: '#' },
];

// Computed properties for better data handling
const domainName = computed(() => getDomainFromUrl(monitorData.value.url));
const statusColor = computed(() => {
  switch (monitorData.value.uptime_status) {
    case 'up': return 'text-green-600 dark:text-green-400';
    case 'down': return 'text-red-600 dark:text-red-400';
    default: return 'text-yellow-600 dark:text-yellow-400';
  }
});

const statusBgColor = computed(() => {
  switch (monitorData.value.uptime_status) {
    case 'up': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'down': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    default: return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  }
});

const certificateColor = computed(() => {
  switch (monitorData.value.certificate_status) {
    case 'valid': return 'text-green-600 dark:text-green-400';
    case 'invalid': return 'text-red-600 dark:text-red-400';
    default: return 'text-gray-600 dark:text-gray-400';
  }
});

const uptimePercentageColor = computed(() => {
  if (!monitorData.value.today_uptime_percentage) return 'text-gray-600 dark:text-gray-400';
  if (monitorData.value.today_uptime_percentage >= 99.5) return 'text-green-600 dark:text-green-400';
  if (monitorData.value.today_uptime_percentage >= 95) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
});

// Get histories from monitor object with fallback
const histories = computed(() => {
  return monitorData.value.histories || [];
});

// Function to get last 100 minutes
function getLast100Minutes() {
  const now = new Date();
  return Array.from({ length: 100 }, (_, i) => {
    const d = new Date(now);
    d.setMinutes(now.getMinutes() - (99 - i));
    d.setSeconds(0, 0);
    return d;
  });
}

// Make last100Minutes reactive
const last100Minutes = ref(getLast100Minutes());

// Map history by minute (YYYY-MM-DDTHH:MM) with null check
const historyMinuteMap = ref(Object.fromEntries(
  (histories.value || []).map(h => [h.created_at.slice(0, 16), h])
));

function getMinuteStatus(date: Date) {
  const key = date.toISOString().slice(0, 16);
  const h = historyMinuteMap.value[key];
  if (!h) return null;
  return h;
}

// Function to fetch fresh history data
async function updateHistoryData() {
  try {
    const response = await axios.get(route('monitor.history', { monitor: monitorData.value.id }));
    if (response.data.histories) {
      historyMinuteMap.value = Object.fromEntries(
        response.data.histories.map((h: MonitorHistory) => [h.created_at.slice(0, 16), h])
      );
    }
  } catch (error) {
    console.error('Failed to fetch history data:', error);
  }
}

// Auto reload every 1 minute
let intervalId: ReturnType<typeof setInterval> | null = null;

// Countdown state
const checkIntervalSeconds = computed(() => (monitorData.value.uptime_check_interval || 1) * 60);
const countdown = ref(checkIntervalSeconds.value);

// Add refresh countdown state
const refreshCountdown = ref(60);

// Utility for next check countdown
function getSecondsUntilNextCheck(lastCheckDate: string | null, intervalSeconds: number) {
  if (!lastCheckDate) return intervalSeconds;
  const lastCheck = new Date(lastCheckDate).getTime();
  const now = Date.now();
  const elapsed = Math.floor((now - lastCheck) / 1000);
  const remaining = intervalSeconds - elapsed;
  return remaining > 0 ? remaining : 0;
}

// Next check countdown state
const nextCheckCountdown = ref(
  getSecondsUntilNextCheck(monitorData.value.last_check_date, checkIntervalSeconds.value)
);

let refreshIntervalId: ReturnType<typeof setInterval> | null = null;
let nextCheckIntervalId: ReturnType<typeof setInterval> | null = null;

// Utility functions
function getDomainFromUrl(url: string) {
  try {
    const domain = new URL(url).hostname;
    return domain.replace('www.', '');
  } catch {
    return url;
  }
}

function formatDate(dateString: string | null) {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString();
}

function formatUptimePercentage(percentage: number | undefined) {
  if (percentage === undefined) return 'N/A';
  return `${percentage.toFixed(1)}%`;
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'up': return 'checkCircle';
    case 'down': return 'xCircle';
    default: return 'clock';
  }
}

function getStatusText(status: string) {
  switch (status) {
    case 'up': return 'Online';
    case 'down': return 'Offline';
    default: return 'Checking...';
  }
}

// New functions for history table status colors
function getHistoryStatusColor(status: string) {
  switch (status) {
    case 'up': return 'text-green-600 dark:text-green-400';
    case 'down': return 'text-red-600 dark:text-red-400';
    default: return 'text-yellow-600 dark:text-yellow-400';
  }
}

function getHistoryStatusBgColor(status: string) {
  switch (status) {
    case 'up': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'down': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    default: return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  }
}

onMounted(() => {
  intervalId = setInterval(() => {
    router.reload({ preserveUrl: true });
    countdown.value = checkIntervalSeconds.value;
  }, checkIntervalSeconds.value * 1000); // Use dynamic interval

  // Countdown seconds and update bar
  const countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    }
    // Update bar every interval
    if (countdown.value === 0) {
      last100Minutes.value = getLast100Minutes();
      updateHistoryData(); // Fetch fresh history data
      countdown.value = checkIntervalSeconds.value; // Reset countdown
    }
  }, 1000);

  // Watch for changes in check interval and reset countdown
  watch(checkIntervalSeconds, (newVal) => {
    countdown.value = newVal;
    if (intervalId) clearInterval(intervalId);
    intervalId = setInterval(() => {
      router.reload({ preserveUrl: true });
      countdown.value = checkIntervalSeconds.value;
    }, checkIntervalSeconds.value * 1000);
  });

  // Refresh countdown (always 1 minute)
  refreshIntervalId = setInterval(() => {
    if (refreshCountdown.value > 0) {
      refreshCountdown.value--;
    }
    if (refreshCountdown.value === 0) {
      router.reload({ preserveUrl: true });
      refreshCountdown.value = 60;
    }
  }, 1000);

  // Next check countdown (dynamic)
  nextCheckIntervalId = setInterval(() => {
    nextCheckCountdown.value = getSecondsUntilNextCheck(monitorData.value.last_check_date, checkIntervalSeconds.value);
  }, 1000);

  // Cleanup
  onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    clearInterval(countdownInterval);
    if (refreshIntervalId) clearInterval(refreshIntervalId);
    if (nextCheckIntervalId) clearInterval(nextCheckIntervalId);
  });
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Monitor: ${domainName}`" />


    <div class="py-8">
      <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Monitor Overview Card -->
        <Card>
          <CardHeader>
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-3">
                <img
                  v-if="monitorData.favicon"
                  :src="monitorData.favicon"
                  :alt="`${domainName} favicon`"
                  class="w-8 h-8 rounded"
                />
                <div>
                  <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ domainName }}
                  </h2>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ monitorData.url }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  @click="router.reload({ preserveUrl: true })"
                  :disabled="countdown < checkIntervalSeconds"
                >
                  <Icon name="refresh-cw" class="w-4 h-4 mr-2" />
                  Refresh
                </Button>
                <Link :href="route('monitor.edit', monitorData.id)">
                  <Button size="sm">
                    <Icon name="edit" class="w-4 h-4 mr-2" />
                    Edit Monitor
                  </Button>
                </Link>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Status -->
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Current Status</h4>
                <div class="flex items-center gap-2">
                  <Icon :name="getStatusIcon(monitorData.uptime_status)" class="w-5 h-5" :class="statusColor" />
                  <span :class="statusBgColor" class="px-3 py-1 rounded-full text-sm font-medium">
                    {{ getStatusText(monitorData.uptime_status) }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Last checked: {{ formatDate(monitorData.last_check_date) }}
                </p>
              </div>

              <!-- Uptime Percentage -->
              <div class="space-y-2" v-if="monitorData.today_uptime_percentage !== undefined">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Today's Uptime</h4>
                <div class="flex items-center gap-2">
                  <span :class="uptimePercentageColor" class="text-2xl font-bold">
                    {{ formatUptimePercentage(monitorData.today_uptime_percentage) }}
                  </span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    :class="{
                      'bg-green-500': monitorData.today_uptime_percentage >= 99.5,
                      'bg-yellow-500': monitorData.today_uptime_percentage >= 95 && monitorData.today_uptime_percentage < 99.5,
                      'bg-red-500': monitorData.today_uptime_percentage < 95
                    }"
                    class="h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${monitorData.today_uptime_percentage}%` }"
                  ></div>
                </div>
              </div>

              <!-- Certificate Status -->
              <div class="space-y-2" v-if="monitorData.certificate_check_enabled">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">SSL Certificate</h4>
                <div class="flex items-center gap-2">
                  <Icon
                    :name="monitorData.certificate_status === 'valid' ? 'lock' : 'lockOpen'"
                    class="w-5 h-5"
                    :class="certificateColor"
                  />
                  <span :class="certificateColor" class="font-medium">
                    {{ monitorData.certificate_status }}
                  </span>
                </div>
                <p v-if="monitorData.certificate_expiration_date" class="text-xs text-gray-500 dark:text-gray-400">
                  Expires: {{ new Date(monitorData.certificate_expiration_date).toLocaleDateString() }}
                </p>
              </div>

              <!-- Check Interval -->
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Check Interval</h4>
                <p class="text-lg font-semibold">{{ monitorData.uptime_check_interval }} minutes</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Next check in {{ nextCheckCountdown }} seconds
                </p>
              </div>

              <!-- Down Events -->
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Down Events</h4>
                <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                  {{ monitorData.down_for_events_count }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total incidents</p>
              </div>

              <!-- Monitor Info -->
              <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Monitor Info</h4>
                <div class="space-y-1 text-sm">
                  <p><span class="text-gray-500">Created:</span> {{ formatDate(monitorData.created_at) }}</p>
                  <p><span class="text-gray-500">Updated:</span> {{ formatDate(monitorData.updated_at) }}</p>
                  <p v-if="monitorData.uptime_status_last_change_date">
                    <span class="text-gray-500">Status changed:</span> {{ formatDate(monitorData.uptime_status_last_change_date) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Failure Reason -->
            <div v-if="monitorData.uptime_check_failure_reason" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
              <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-1">Last Failure Reason</h4>
              <p class="text-sm text-red-700 dark:text-red-300">{{ monitorData.uptime_check_failure_reason }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Timeline Card -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Icon name="clock" class="w-5 h-5" />
                Last 100 Minutes Timeline
              </div>
              <div class="flex flex-col items-end">
                <span class="text-sm text-gray-500">Refresh in {{ refreshCountdown }} seconds</span>
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <TooltipProvider>
              <div class="flex w-full h-16 items-end gap-[1px]">
                <Tooltip v-for="(date, i) in last100Minutes" :key="i">
                  <TooltipTrigger
                    class="h-full rounded cursor-pointer transition-colors hover:opacity-80"
                    :style="{
                      width: 'calc(100% / 100)',
                      background: getMinuteStatus(date)?.uptime_status === 'up'
                        ? '#22c55e'
                        : getMinuteStatus(date)?.uptime_status === 'down'
                          ? '#ef4444'
                          : '#d1d5db'
                    }"
                  />
                  <TooltipContent>
                    <div class="text-xs whitespace-nowrap">
                      <div class="font-medium">{{ date.toLocaleString() }}</div>
                      <div>
                        Status: <span v-if="getMinuteStatus(date)">{{ getStatusText(getMinuteStatus(date)!.uptime_status) }}</span>
                        <span v-else>No data</span>
                      </div>
                      <div v-if="getMinuteStatus(date)?.message" class="text-gray-500">
                        {{ getMinuteStatus(date)!.message }}
                      </div>
                    </div>
                  </TooltipContent>
                </Tooltip>
              </div>
            </TooltipProvider>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
              <span>{{ last100Minutes[0].toLocaleString() }}</span>
              <span>{{ last100Minutes[last100Minutes.length - 1].toLocaleString() }}</span>
            </div>
          </CardContent>
        </Card>

        <!-- History Card -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon name="list" class="w-5 h-5" />
              Recent History
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="!histories || histories.length === 0" class="text-center py-8 text-gray-600 dark:text-gray-400">
              <Icon name="inbox" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
              <p>No history available yet.</p>
            </div>
            <div v-else class=" max-h-[50vh] overflow-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Message</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="history in histories" :key="history.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                      {{ formatDate(history.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center gap-2">
                        <Icon :name="getStatusIcon(history.uptime_status)" class="w-4 h-4" :class="getHistoryStatusColor(history.uptime_status)" />
                        <span :class="getHistoryStatusBgColor(history.uptime_status)" class="px-2.5 py-0.5 rounded-full text-sm font-medium">
                          {{ getStatusText(history.uptime_status) }}
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                      {{ history.message || '-' }}
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
