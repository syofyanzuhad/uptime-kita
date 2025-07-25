<script setup lang="ts">
  import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
  import Icon from '@/components/Icon.vue'
import { Head } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme'
import DailyUptimeChart from '@/components/DailyUptimeChart.vue'
import OfflineBanner from '@/components/OfflineBanner.vue'

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
    isAuthenticated: boolean;
  }

  const props = defineProps<Props>()

  // --- MONITORS ASYNC LOADING ---
  const monitors = ref<Monitor[]>([])
  const monitorsLoading = ref(true)
  const monitorsError = ref<string | null>(null)

  // --- UPTIMES DAILY PER MONITOR ---
  const uptimesDaily = ref<Record<number, { date: string; uptime_percentage: number }[]>>({})
  const uptimesDailyLoading = ref<Record<number, boolean>>({})
  const uptimesDailyError = ref<Record<number, string | null>>({})

  // --- LATEST HISTORY PER MONITOR ---
  const latestHistory = ref<Record<number, MonitorHistory | null>>({})
  const latestHistoryLoading = ref<Record<number, boolean>>({})
  const latestHistoryError = ref<Record<number, string | null>>({})

  async function fetchMonitors() {
    monitorsLoading.value = true
    monitorsError.value = null
    try {
      const res = await fetch(`/status/${props.statusPage.path}/monitors`)
      if (!res.ok) throw new Error('Failed to load monitors')
      const data = await res.json()
      // If data is wrapped in {data: [...]}, unwrap
      monitors.value = Array.isArray(data) ? data : data.data || []
    } catch (e: any) {
      monitorsError.value = e.message || 'Unknown error'
    } finally {
      monitorsLoading.value = false
    }
  }

  async function fetchUptimesDaily(monitorId: number, date?: string) {
    uptimesDailyLoading.value[monitorId] = true
    uptimesDailyError.value[monitorId] = null
    try {
      let url = `/monitor/${monitorId}/uptimes-daily`
      if (date) {
        url += `?date=${date}`
      }
      const res = await fetch(url)
      if (!res.ok) throw new Error('Failed to load uptimes')
      const data = await res.json()
      if (date) {
        // Only update today's entry
        const todayEntry = (data.uptimes_daily || [])[0]
        const arr = uptimesDaily.value[monitorId] || []
        if (todayEntry) {
          const idx = arr.findIndex(d => d.date === date)
          if (idx !== -1) {
            arr[idx] = todayEntry
          } else {
            arr.push(todayEntry)
          }
          uptimesDaily.value[monitorId] = arr
        }
      } else {
        // Initial load: set the whole array
        uptimesDaily.value[monitorId] = data.uptimes_daily || []
      }
    } catch (e: any) {
      uptimesDailyError.value[monitorId] = e.message || 'Unknown error'
    } finally {
      uptimesDailyLoading.value[monitorId] = false
    }
  }

  async function fetchLatestHistory(monitorId: number) {
    latestHistoryLoading.value[monitorId] = true
    latestHistoryError.value[monitorId] = null
    try {
      const res = await fetch(`/monitor/${monitorId}/latest-history`)
      if (!res.ok) throw new Error('Failed to load latest history')
      const data = await res.json()
      latestHistory.value[monitorId] = data.latest_history || null
    } catch (e: any) {
      latestHistoryError.value[monitorId] = e.message || 'Unknown error'
    } finally {
      latestHistoryLoading.value[monitorId] = false
    }
  }

  // Fetch latestHistory for all monitors after loading
  watch(monitors, (newMonitors) => {
    newMonitors.forEach(monitor => {
      if (latestHistory.value[monitor.id] === undefined) {
        fetchLatestHistory(monitor.id)
      }
    })
  })

  // Remove uptimesDaily fetch from watchers to avoid double-fetching
  watch(monitors, (newMonitors) => {
    newMonitors.forEach(monitor => {
      if (uptimesDaily.value[monitor.id] === undefined) {
        fetchUptimesDaily(monitor.id)
      }
    })
  })

  // --- HELPER FUNCTIONS (Fungsi Bantuan) ---

  const formatDate = (dateString?: string, locale: string = navigator.language || 'en-US') => {
    if (!dateString) return ''
    // Mengembalikan format tanggal dan waktu yang lengkap
    return new Date(dateString).toLocaleString(locale, {
      dateStyle: 'medium',
      timeStyle: 'short'
    })
  }

  // Fungsi baru untuk format "waktu yang lalu"
  const timeAgo = (dateString?: string) => {
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
    if (!monitors.value || monitors.value.length === 0) {
      return { color: 'bg-green-500', text: 'All Systems Operational' };
    }
    const hasDown = monitors.value.some(m => latestHistory.value[m.id]?.uptime_status?.toLowerCase() === 'down');
    const hasWarning = monitors.value.some(m => latestHistory.value[m.id]?.uptime_status?.toLowerCase() === 'warning');
    if (hasDown) {
      return { color: 'bg-red-500', text: 'Some Systems Are Down' };
    }
    if (hasWarning) {
      return { color: 'bg-yellow-500', text: 'Some Systems Are Degraded' };
    }
    return { color: 'bg-green-500', text: 'All Systems Operational' };
  })

// --- AUTO REFRESH COUNTDOWN ---
const countdown = ref(60)
let intervalId: number | undefined

const isOnline = ref(navigator.onLine)

function updateOnlineStatus() {
  isOnline.value = navigator.onLine
}

function startCountdown() {
  intervalId = window.setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      if (isOnline.value) {
        refetchStatusPage()
      }
      countdown.value = 60
    }
  }, 1000)
}

function refetchStatusPage() {
  if (!isOnline.value) return
  fetchMonitors()
  monitors.value.forEach(monitor => {
    fetchLatestHistory(monitor.id)
  })
  if (props.isAuthenticated) {
    const today = new Date().toISOString().slice(0, 10)
    monitors.value.forEach(monitor => {
      fetchUptimesDaily(monitor.id, today)
    })
  }
}

const isFullscreen = ref(false)

function toggleFullscreen() {
  const elem = document.documentElement
  if (!isFullscreen.value) {
    if (elem.requestFullscreen) {
      elem.requestFullscreen()
    } else if ((elem as any).webkitRequestFullscreen) {
      (elem as any).webkitRequestFullscreen()
    } else if ((elem as any).msRequestFullscreen) {
      (elem as any).msRequestFullscreen()
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
    } else if ((document as any).webkitExitFullscreen) {
      (document as any).webkitExitFullscreen()
    } else if ((document as any).msExitFullscreen) {
      (document as any).msExitFullscreen()
    }
  }
}

function fullscreenChangeHandler() {
  isFullscreen.value = !!(
    document.fullscreenElement ||
    (document as any).webkitFullscreenElement ||
    (document as any).msFullscreenElement
  )
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
  document.addEventListener('fullscreenchange', fullscreenChangeHandler)
  document.addEventListener('webkitfullscreenchange', fullscreenChangeHandler)
  document.addEventListener('msfullscreenchange', fullscreenChangeHandler)
  fetchMonitors()
  if (props.isAuthenticated) {
    // Initial fetch for uptimesDaily (all days)
    monitors.value.forEach(monitor => {
      fetchUptimesDaily(monitor.id)
    })
  }
  startCountdown()
})
onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
  document.removeEventListener('fullscreenchange', fullscreenChangeHandler)
  document.removeEventListener('webkitfullscreenchange', fullscreenChangeHandler)
  document.removeEventListener('msfullscreenchange', fullscreenChangeHandler)
})

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <OfflineBanner v-if="!isOnline" />
  <Head
    :title="`${statusPage.title} - Status Page`"
    :description="statusPage.description || 'Status Page'"
  />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex items-center space-x-4 justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                <Icon :name="statusPage.icon" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ statusPage.title }}</h1>
                <p class="text-gray-600 dark:text-gray-300">{{ statusPage.description }}</p>
              </div>
            </div>
            <div>
                <!-- Theme Toggle Button -->
                <button @click="toggleTheme" class="ml-4 p-2 rounded-full cursor-pointer border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                  <Icon v-if="isDark" name="sun" class="h-5 w-5 text-yellow-400" />
                  <Icon v-else name="moon" class="h-5 w-5 text-gray-600 dark:text-gray-200" />
                </button>
                <button @click="toggleFullscreen" class="ml-2 p-2 rounded-full cursor-pointer border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors" :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'" :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'">
                  <Icon v-if="isFullscreen" name="minimize" class="h-5 w-5 text-gray-600 dark:text-gray-200" />
                  <Icon v-else name="maximize" class="h-5 w-5 text-gray-600 dark:text-gray-200" />
                </button>
            </div>
          </div>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-8">
        <div class="mb-8">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">System Status</h2>
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full animate-pulse"
                  :class="[
                    overallStatus.color,
                    overallStatus.text === 'All Systems Operational' ? 'shadow-[0_0_10px_3px_rgba(34,197,94,0.7)]' :
                    overallStatus.text === 'Some Systems Are Down' ? 'shadow-[0_0_10px_3px_rgba(239,68,68,0.7)]' :
                    overallStatus.text === 'Some Systems Are Degraded' ? 'shadow-[0_0_10px_3px_rgba(250,204,21,0.7)]' :
                    'shadow-[0_0_10px_3px_rgba(156,163,175,0.5)]'
                  ]"
                ></div>
                <span class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ overallStatus.text }}</span>
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-1" title="Auto refresh">
                <Icon name="clock" class="h-4 w-4" />
                <span>{{ countdown }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
          <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Services</h3>
          </div>
          <div v-if="monitorsError" class="p-6 text-center text-red-500">{{ monitorsError }}</div>
          <div v-else class="divide-y divide-gray-200 dark:divide-gray-700 relative">
            <div v-if="monitorsLoading" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center z-10">
              <span class="text-gray-500 dark:text-gray-400">Refreshing...</span>
            </div>
            <div v-for="monitor in monitors" :key="monitor.id" class="px-4 sm:px-6 py-4">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-4 w-full min-w-0">
                  <img v-if="monitor.favicon" :src="monitor.favicon" class="w-5 h-5 rounded-full" alt="favicon" @error="($event.target as HTMLImageElement).style.display='none'" />
                  <div v-else class="w-5 h-5 bg-gray-200 dark:bg-gray-700 rounded-full"></div>

                  <div class="w-3 h-3 rounded-full flex-shrink-0 animate-pulse"
                    :class="[
                      getStatusColor(latestHistory[monitor.id]?.uptime_status),
                      latestHistory[monitor.id]?.uptime_status?.toLowerCase() === 'up' ? 'shadow-[0_0_8px_2px_rgba(34,197,94,0.7)]' :
                      latestHistory[monitor.id]?.uptime_status?.toLowerCase() === 'down' ? 'shadow-[0_0_8px_2px_rgba(239,68,68,0.7)]' :
                      latestHistory[monitor.id]?.uptime_status?.toLowerCase() === 'warning' ? 'shadow-[0_0_8px_2px_rgba(250,204,21,0.7)]' :
                      'shadow-[0_0_8px_2px_rgba(156,163,175,0.5)]'
                    ]"
                  ></div>

                  <div class="flex-grow min-w-0">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center flex-wrap">
                      {{ monitor.name }}
                      <span
                        v-if="monitor.certificate_check_enabled && monitor.certificate_status"
                        class="ml-2 px-1 py-0.5 rounded-full text-xs font-semibold uppercase flex items-center gap-1"
                        :class="getCertStatusColor(monitor.certificate_status)"
                        :title="'SSL is ' + monitor.certificate_status"
                      >
                        <Icon
                          v-if="monitor.certificate_status.toLowerCase() === 'valid'"
                          name="lock"
                          class="w-4 h-4 inline-block"
                        />
                        <Icon
                          v-else-if="['invalid', 'expired'].includes(monitor.certificate_status.toLowerCase())"
                          name="lock-open"
                          class="w-4 h-4 inline-block"
                        />
                        <Icon
                          v-else-if="monitor.certificate_status.toLowerCase() === 'expiring soon'"
                          name="lock"
                          class="w-4 h-4 inline-block"
                        />
                        <Icon
                          v-else
                          name="clock"
                          class="w-4 h-4 inline-block"
                        />
                        <span class="sr-only">SSL {{ monitor.certificate_status }}</span>
                      </span>
                    </h4>
                    <a class="block break-all text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:underline" :href="monitor.url" target="_blank">{{ monitor.url }}</a>
                  </div>
                </div>

                <div class="text-right flex-shrink-0 ml-0 sm:ml-4 w-full sm:w-auto">
                  <div class="text-sm font-medium" :class="getStatusTextColor(latestHistory[monitor.id]?.uptime_status) + ' dark:text-inherit'">
                    <template v-if="latestHistoryLoading[monitor.id]">Loading...</template>
                    <template v-else-if="latestHistoryError[monitor.id]">Error</template>
                    <template v-else>{{ getStatusText(latestHistory[monitor.id]?.uptime_status) }}</template>
                  </div>
                  <div v-if="latestHistory[monitor.id]?.created_at" class="text-xs text-gray-500 dark:text-gray-400" :title="formatDate(latestHistory[monitor.id]?.created_at)">
                    Last check: {{ timeAgo(latestHistory[monitor.id]?.created_at) }}
                  </div>
                </div>
              </div>

              <!-- Daily History Bar Chart for Latest 100 Days -->
              <DailyUptimeChart
                :monitor-id="monitor.id"
                :is-authenticated="props.isAuthenticated"
                :uptimes-daily="uptimesDaily[monitor.id]"
                :is-loading="uptimesDailyLoading[monitor.id]"
                :error="uptimesDailyError[monitor.id]"
              />
            </div>
          </div>
        </div>

        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
          <p>Powered by <a href="https://uptime.syofyanzuhad.dev" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Uptime Kita</a></p>
        </div>
      </main>
    </div>
</template>
