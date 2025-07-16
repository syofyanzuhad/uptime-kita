<script setup lang="ts">
  import { computed } from 'vue'
  import Icon from '@/components/Icon.vue'
import { Head } from '@inertiajs/vue3';

  // --- INTERFACES (Struktur Data Anda) ---
  interface MonitorHistory {
    id: number;
    monitor_id: number;
    uptime_status: string;
    message: string;
    created_at: string;
    updated_at: string;
  }

  interface Monitor {
    id: number;
    name: string;
    url: string;
    uptime_status: string;
    uptime_check_enabled: boolean;
    favicon?: string | null;
    last_check_date?: string | null;
    certificate_check_enabled: boolean;
    certificate_status?: string | null;
    certificate_expiration_date?: string | null;
    down_for_events_count: number;
    uptime_check_interval: number;
    is_subscribed: boolean;
    is_public: boolean;
    today_uptime_percentage: number;
    uptime_status_last_change_date?: string | null;
    uptime_check_failure_reason?: string | null;
    created_at: string;
    updated_at: string;
    histories?: MonitorHistory[];
    latest_history?: MonitorHistory | null;
    uptimes_daily?: { date: string; uptime_percentage: number }[];
  }

  interface StatusPage {
    id: number;
    title: string;
    description: string;
    icon: string;
    path: string;
    created_at: string;
    updated_at: string;
    monitors: Monitor[];
  }

  interface Props {
    statusPage: StatusPage;
  }

  const props = defineProps<Props>()

  // --- HELPER FUNCTIONS (Fungsi Bantuan) ---

  const formatDate = (dateString: string, locale: string = navigator.language || 'en-US') => {
    if (!dateString) return ''
    // Mengembalikan format tanggal dan waktu yang lengkap
    return new Date(dateString).toLocaleString(locale, {
      dateStyle: 'medium',
      timeStyle: 'short'
    })
  }

  // Fungsi baru untuk format "waktu yang lalu"
  const timeAgo = (dateString: string) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      const now = new Date();
      const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

      let interval = seconds / 31536000;
      if (interval > 1) return Math.floor(interval) + " years ago";
      interval = seconds / 2592000;
      if (interval > 1) return Math.floor(interval) + " months ago";
      interval = seconds / 86400;
      if (interval > 1) return Math.floor(interval) + " days ago";
      interval = seconds / 3600;
      if (interval > 1) return Math.floor(interval) + " hours ago";
      interval = seconds / 60;
      if (interval > 1) return Math.floor(interval) + " minutes ago";
      if (seconds < 30) return "just now";
      return Math.floor(seconds) + " seconds ago";
  }


  const getStatusColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'up': return 'bg-green-500';
      case 'down': return 'bg-red-500';
      case 'warning': return 'bg-yellow-500';
      default: return 'bg-gray-400';
    }
  }

  const getStatusTextColor = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'up': return 'text-green-600';
      case 'down': return 'text-red-600';
      case 'warning': return 'text-yellow-600';
      default: return 'text-gray-600';
    }
  }

  const getStatusText = (status?: string) => {
    switch (status?.toLowerCase()) {
      case 'up': return 'Operational';
      case 'down': return 'Outage';
      case 'warning': return 'Degraded';
      default: return 'Unknown';
    }
  }

  const getCertStatusColor = (certStatus?: string | null) => {
    switch (certStatus?.toLowerCase()) {
      case 'valid': return 'bg-green-100 text-green-800';
      case 'expiring soon': return 'bg-yellow-100 text-yellow-800';
      case 'invalid':
      case 'expired': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  }

  const overallStatus = computed(() => {
    if (!props.statusPage?.monitors || props.statusPage.monitors.length === 0) {
      return { color: 'bg-green-500', text: 'All Systems Operational' };
    }
    const hasDown = props.statusPage.monitors.some(m => m.latest_history?.uptime_status?.toLowerCase() === 'down');
    const hasWarning = props.statusPage.monitors.some(m => m.latest_history?.uptime_status?.toLowerCase() === 'warning');
    if (hasDown) {
      return { color: 'bg-red-500', text: 'Some Systems Are Down' };
    }
    if (hasWarning) {
      return { color: 'bg-yellow-500', text: 'Some Systems Are Degraded' };
    }
    return { color: 'bg-green-500', text: 'All Systems Operational' };
  })

// Helper to generate the latest 100 days as YYYY-MM-DD
function getLatest100Days() {
  const dates = []
  const today = new Date()
  for (let i = 99; i >= 0; i--) {
    const d = new Date(today)
    d.setDate(today.getDate() - i)
    dates.push(d.toISOString().slice(0, 10))
  }
  return dates
}
</script>

<template>
  <Head :title="`${statusPage.title} - Status Page`" />

    <div class="min-h-screen bg-gray-50">
      <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <Icon :name="statusPage.icon" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ statusPage.title }}</h1>
              <p class="text-gray-600">{{ statusPage.description }}</p>
            </div>
          </div>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">System Status</h2>
            <div class="flex items-center space-x-3">
              <div class="w-4 h-4 rounded-full animate-pulse" :class="overallStatus.color"></div>
              <span class="text-lg font-medium text-gray-900">{{ overallStatus.text }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Services</h3>
          </div>
          <div class="divide-y divide-gray-200">
            <div v-for="monitor in statusPage.monitors" :key="monitor.id" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <img v-if="monitor.favicon" :src="monitor.favicon" class="w-5 h-5 rounded-full" alt="favicon" @error="($event.target as HTMLImageElement).style.display='none'" />
                  <div v-else class="w-5 h-5 bg-gray-200 rounded-full"></div>

                  <div class="w-3 h-3 rounded-full flex-shrink-0" :class="getStatusColor(monitor.latest_history?.uptime_status)"></div>

                  <div class="flex-grow">
                    <h4 class="font-medium text-gray-900 flex items-center">
                      {{ monitor.name }}
                      <span v-if="monitor.certificate_check_enabled && monitor.certificate_status" class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="getCertStatusColor(monitor.certificate_status)">
                        {{ monitor.certificate_status }}
                      </span>
                    </h4>
                    <p class="text-sm text-gray-500">{{ monitor.url }}</p>
                  </div>
                </div>

                <div class="text-right flex-shrink-0 ml-4">
                  <div class="text-sm font-medium" :class="getStatusTextColor(monitor.latest_history?.uptime_status)">
                    {{ getStatusText(monitor.latest_history?.uptime_status) }}
                  </div>
                  <div v-if="monitor.latest_history?.created_at" class="text-xs text-gray-500" :title="formatDate(monitor.latest_history.created_at)">
                    Last check: {{ timeAgo(monitor.latest_history.created_at) }}
                  </div>
                </div>
              </div>

              <!-- Daily History Bar Chart for Latest 100 Days -->
              <div v-if="monitor.uptimes_daily" class="mt-2">
                <div class="flex items-end h-16 bg-gray-50 rounded p-2 border border-gray-200 w-full">
                  <template v-for="date in getLatest100Days()" :key="date">
                    <template v-if="monitor.uptimes_daily.some(u => u.date === date)">
                      <div
                        v-for="uptime in monitor.uptimes_daily.filter(u => u.date === date)"
                        :key="uptime.date"
                        class="relative group flex flex-col items-center flex-1 min-w-0 mx-px"
                      >
                        <div
                          :class="[
                            'h-8 w-full rounded transition-all duration-200',
                            uptime.uptime_percentage >= 99 ? 'bg-green-500' : uptime.uptime_percentage >= 90 ? 'bg-yellow-400' : 'bg-red-500'
                          ]"
                        ></div>
                        <div class="absolute bottom-full mb-1 hidden group-hover:block bg-white text-xs text-gray-700 px-2 py-1 rounded shadow z-10 whitespace-nowrap">
                          {{ uptime.date }}<br />{{ uptime.uptime_percentage.toFixed(2) }}%
                        </div>
                      </div>
                    </template>
                    <template v-else>
                      <div class="relative group flex flex-col items-center flex-1 min-w-0 mx-px">
                        <div class="h-8 w-full bg-gray-300 rounded"></div>
                        <div class="absolute bottom-full mb-1 hidden group-hover:block bg-white text-xs text-gray-700 px-2 py-1 rounded shadow z-10 whitespace-nowrap">
                          {{ date }}<br />No data
                        </div>
                      </div>
                    </template>
                  </template>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                  <span>{{ getLatest100Days()[0] }}</span>
                  <span>{{ getLatest100Days()[getLatest100Days().length-1] }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 text-center text-sm text-gray-500">
          <p>Powered by <a href="https://uptime.syofyanzuhad.dev" target="_blank" class="text-blue-600 hover:text-blue-800">Uptime Kita</a></p>
        </div>
      </main>
    </div>
</template>
