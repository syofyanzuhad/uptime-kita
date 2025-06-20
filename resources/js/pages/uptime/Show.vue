<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { Monitor, MonitorHistory } from '@/types/monitor';
import { onMounted, onUnmounted, ref } from 'vue';
import Tooltip from '@/components/ui/tooltip/Tooltip.vue';
import TooltipTrigger from '@/components/ui/tooltip/TooltipTrigger.vue';
import TooltipContent from '@/components/ui/tooltip/TooltipContent.vue';
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import axios from 'axios';

const props = defineProps<{
  monitor: Monitor;
  histories: MonitorHistory[];
}>();

const breadcrumbs = [
  { title: 'Uptime Monitor', href: '/monitor' },
  { title: props.monitor.url, href: '#' },
];

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

// Map history by menit (YYYY-MM-DDTHH:MM)
const historyMinuteMap = ref(Object.fromEntries(
  props.histories.map(h => [h.created_at.slice(0, 16), h])
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
    const response = await axios.get(route('monitor.history', props.monitor.id));
    if (response.data.histories) {
      historyMinuteMap.value = Object.fromEntries(
        response.data.histories.map((h: MonitorHistory) => [h.created_at.slice(0, 16), h])
      );
    }
  } catch (error) {
    console.error('Failed to fetch history data:', error);
  }
}

// Auto reload setiap 1 menit
let intervalId: ReturnType<typeof setInterval> | null = null;

// Countdown state
const countdown = ref(60);

onMounted(() => {
  intervalId = setInterval(() => {
    router.reload({ preserveUrl: true });
    countdown.value = 60;
  }, 60000); // 60 detik

  // Countdown detik dan update bar
  const countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    }
    // Update bar setiap menit
    if (countdown.value === 0) {
      last100Minutes.value = getLast100Minutes();
      updateHistoryData(); // Fetch fresh history data
    }
  }, 1000);

  // Cleanup
  onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    clearInterval(countdownInterval);
  });
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Monitor: ${props.monitor.url}`" />
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Monitor Detail
        </h2>
        <Link :href="route('monitor.edit', props.monitor.id)" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md">
          Edit Monitor
        </Link>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Monitor Info</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><strong>URL:</strong> <a :href="props.monitor.url" target="_blank" class="text-blue-600 hover:underline">{{ props.monitor.url }}</a></div>
            <div><strong>Status Uptime:</strong> <span :class="{
              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': props.monitor.uptime_status === 'up',
              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': props.monitor.uptime_status === 'down',
              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': props.monitor.uptime_status === 'not yet checked',
            }" class="px-2.5 py-0.5 rounded-full text-sm font-medium">{{ props.monitor.uptime_status }}</span></div>
            <div><strong>Terakhir Dicek:</strong> {{ props.monitor.last_check_date ? new Date(props.monitor.last_check_date).toLocaleString() : '-' }}</div>
            <div><strong>Sertifikat:</strong> <span v-if="props.monitor.certificate_check_enabled">
              <span :class="{
                'text-green-600 dark:text-green-400': props.monitor.certificate_status === 'valid',
                'text-red-600 dark:text-red-400': props.monitor.certificate_status === 'invalid',
                'text-gray-600 dark:text-gray-400': props.monitor.certificate_status === 'not applicable',
              }">{{ props.monitor.certificate_status }}</span>
              <span v-if="props.monitor.certificate_expiration_date" class="text-xs text-gray-500 dark:text-gray-400 block">
                Expired: {{ new Date(props.monitor.certificate_expiration_date).toLocaleDateString() }}
              </span>
            </span>
            <span v-else class="text-gray-400 dark:text-gray-500">Tidak dicek</span></div>
            <div><strong>Interval Cek:</strong> {{ props.monitor.uptime_check_interval }} menit</div>
            <div><strong>Jumlah Down Event:</strong> {{ props.monitor.down_for_events_count }}</div>
          </div>
        </div>

        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
            Timeline 100 Menit Terakhir
            <span class="ml-2 text-xs text-gray-400">Refresh dalam {{ countdown }} detik</span>
          </h4>
          <TooltipProvider>
            <div class="flex w-full h-16 items-end gap-[1px]">
              <Tooltip v-for="(date, i) in last100Minutes" :key="i">
                <TooltipTrigger
                  class="h-full rounded cursor-pointer"
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
                    <div>{{ date.toLocaleString() }}</div>
                    <div>
                      Status: <span v-if="getMinuteStatus(date)">{{ getMinuteStatus(date)!.uptime_status }}</span>
                      <span v-else>kosong</span>
                    </div>
                    <div v-if="getMinuteStatus(date)?.message">Keterangan: {{ getMinuteStatus(date)!.message }}</div>
                  </div>
                </TooltipContent>
              </Tooltip>
            </div>
          </TooltipProvider>
          <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>{{ last100Minutes[0].toLocaleString() }}</span>
            <span>{{ last100Minutes[last100Minutes.length - 1].toLocaleString() }}</span>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">History</h3>
          <div v-if="props.histories.length === 0" class="text-gray-600 dark:text-gray-400">Belum ada history.</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Uptime</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="history in props.histories" :key="history.id">
                  <td class="px-6 py-4 whitespace-nowrap">{{ new Date(history.created_at).toLocaleString() }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="{
                      'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': history.uptime_status === 'up',
                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': history.uptime_status === 'down',
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': history.uptime_status === 'not yet checked',
                    }" class="px-2.5 py-0.5 rounded-full text-sm font-medium">{{ history.uptime_status }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ history.message ?? '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
