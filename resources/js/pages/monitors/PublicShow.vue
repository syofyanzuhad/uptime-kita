<template>
  <Head :title="`${monitor.name} - Monitor Status`" />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <img
              v-if="monitor.favicon"
              :src="monitor.favicon"
              :alt="`${monitor.name} favicon`"
              class="w-6 h-6 sm:w-8 sm:h-8 rounded flex-shrink-0"
              @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
            >
            <div class="min-w-0 flex-1">
              <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white truncate max-w-[200px] sm:max-w-none">
                {{ monitor.name }}
              </h1>
              <a
                :href="monitor.url"
                target="_blank"
                class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 truncate block max-w-[200px] sm:max-w-none"
              >
                {{ monitor.url }}
              </a>
            </div>
          </div>

                    <!-- Current Status Badge and Theme Toggle -->
          <div class="flex items-center justify-center sm:justify-end space-x-2">
            <!-- Mobile: Icon only -->
            <span
              :class="[
                'sm:hidden p-2 rounded-full',
                monitor.uptime_status === 'up'
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                  : monitor.uptime_status === 'down'
                  ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                  : monitor.uptime_status === 'not yet checked'
                  ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                  : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
              ]"
            >
              <Icon
                :name="getStatusIcon(monitor.uptime_status)"
                class="w-5 h-5"
              />
            </span>

            <!-- Desktop: Icon with text -->
            <span
              :class="[
                'hidden sm:inline-flex px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap items-center',
                monitor.uptime_status === 'up'
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                  : monitor.uptime_status === 'down'
                  ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                  : monitor.uptime_status === 'not yet checked'
                  ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                  : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
              ]"
            >
              <Icon
                :name="getStatusIcon(monitor.uptime_status)"
                class="w-4 h-4 mr-1"
              />
              {{ getStatusText(monitor.uptime_status) }}
            </span>

            <!-- Theme Toggle -->
            <button
              @click="toggleTheme"
              class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
              :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
              <Icon
                :name="isDark ? 'sun' : 'moon'"
                class="w-4 h-4 text-gray-600 dark:text-gray-300"
              />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column - Stats -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Uptime Statistics -->
          <Card>
            <CardHeader>
              <CardTitle>Uptime Statistics</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="monitor.uptime_status === 'not yet checked'" class="text-center py-6 sm:py-8">
                <Icon name="clock" class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 mx-auto mb-3 sm:mb-4" />
                <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">No uptime data available yet</p>
                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Monitor has not been checked yet</p>
              </div>
              <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div v-for="(value, period) in uptimeStats" :key="period" class="text-center">
                  <div class="text-xl sm:text-2xl font-bold" :class="getUptimeColor(value)">
                    {{ value }}%
                  </div>
                  <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{ getPeriodLabel(period) }}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Response Time Stats -->
          <Card>
            <CardHeader>
              <CardTitle>Response Time (Last 24 Hours)</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="monitor.uptime_status === 'not yet checked'" class="text-center py-6 sm:py-8">
                <Icon name="clock" class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 mx-auto mb-3 sm:mb-4" />
                <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">No response time data available yet</p>
                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Monitor has not been checked yet</p>
              </div>
              <div v-else class="space-y-4">
                <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                  <div>
                    <div class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">
                      {{ avgResponseTime }}ms
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Average</div>
                  </div>
                  <div>
                    <div class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">
                      {{ minResponseTime }}ms
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Min</div>
                  </div>
                  <div>
                    <div class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">
                      {{ maxResponseTime }}ms
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Max</div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Uptime Graph -->
          <Card>
            <CardHeader>
              <CardTitle>Uptime History (Last 90 Days)</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="monitor.uptime_status === 'not yet checked'" class="text-center py-6 sm:py-8">
                <Icon name="clock" class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 mx-auto mb-3 sm:mb-4" />
                <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">No uptime history available yet</p>
                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Monitor has not been checked yet</p>
              </div>
              <div v-else class="space-y-2">
                <div class="flex items-center justify-between text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                  <span>{{ getDateRange() }}</span>
                  <span>Today</span>
                </div>
                <div class="grid grid-cols-90 gap-0.5 h-16 sm:h-20 overflow-x-auto">
                  <div
                    v-for="day in getUptimeDays()"
                    :key="day.date"
                    :title="`${day.date}: ${day.uptime}% uptime`"
                    class="relative group"
                  >
                    <div
                      v-if="day.uptime"
                      :class="[
                        'h-full rounded-sm transition-all',
                        day.uptime === 100 ? 'bg-green-500' :
                        day.uptime >= 99.5 ? 'bg-green-300' :
                        day.uptime >= 95 ? 'bg-yellow-400' :
                        'bg-red-500'
                      ]"
                    />
                    <div
                      v-else
                      class="h-full rounded-sm bg-gray-300 dark:bg-gray-700"
                    />
                    <!-- Tooltip -->
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                      <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                        {{ day.date }}: {{ day.uptime }}%
                      </div>
                    </div>
                  </div>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-4 text-xs text-gray-600 dark:text-gray-400">
                  <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-green-500 rounded-sm"></div>
                    <span class="text-xs">100% Uptime</span>
                  </div>
                  <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-yellow-500 rounded-sm"></div>
                    <span class="text-xs">Partial Outage</span>
                  </div>
                  <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 sm:w-3 sm:h-3 bg-red-500 rounded-sm"></div>
                    <span class="text-xs">Major Outage</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column - Info -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Monitor Details -->
          <Card>
            <CardHeader>
              <CardTitle>Monitor Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Check Interval</div>
                <div class="text-sm sm:text-base font-medium">Every {{ monitor.uptime_check_interval }} minutes</div>
              </div>

              <div v-if="monitor.last_check_date">
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Last Checked</div>
                <div class="text-sm sm:text-base font-medium">{{ formatDate(monitor.last_check_date) }}</div>
              </div>

              <div v-if="monitor.uptime_status_last_change_date">
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Status Since</div>
                <div class="text-sm sm:text-base font-medium">{{ formatDate(monitor.uptime_status_last_change_date) }}</div>
              </div>

              <div v-if="monitor.certificate_check_enabled">
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">SSL Certificate</div>
                <div v-if="monitor.certificate_status === 'not yet checked'" class="flex items-center space-x-2">
                  <Icon
                    name="clock"
                    class="w-4 h-4 text-gray-400"
                  />
                  <span class="text-sm sm:text-base font-medium text-gray-500">
                    Not Yet Checked
                  </span>
                </div>
                <div v-else class="flex items-center space-x-2">
                  <Icon
                    :name="getCertificateIcon(monitor.certificate_status)"
                    class="w-4 h-4"
                    :class="getCertificateColor(monitor.certificate_status)"
                  />
                  <span class="text-sm sm:text-base font-medium">
                    {{ getCertificateText(monitor.certificate_status) }}
                  </span>
                </div>
                <div v-if="monitor.certificate_expiration_date && monitor.certificate_status && monitor.certificate_status !== 'not yet checked'" class="text-xs text-gray-500 mt-1">
                  Expires: {{ formatDate(monitor.certificate_expiration_date) }}
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Recent Incidents -->
          <Card>
            <CardHeader>
              <CardTitle>Recent Incidents</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="monitor.uptime_status === 'not yet checked'" class="text-center py-6 sm:py-8">
                <Icon name="clock" class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 mx-auto mb-3 sm:mb-4" />
                <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">No incidents data available yet</p>
                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">Monitor has not been checked yet</p>
              </div>
              <div v-else-if="recentIncidents.length > 0" class="space-y-3">
                <div
                  v-for="incident in recentIncidents"
                  :key="incident.id"
                  class="border-l-4 pl-3 py-2"
                  :class="[
                    incident.uptime_status === 'down'
                      ? 'border-red-500'
                      : 'border-yellow-500'
                  ]"
                >
                  <div class="text-xs sm:text-sm font-medium">
                    {{ incident.uptime_status === 'down' ? 'Downtime' : 'Degraded' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ incident.created_at ? formatDate(incident.created_at) : '' }}
                  </div>
                  <div v-if="incident.reason" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ incident.reason }}
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                No recent incidents
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-white fixed bottom-0 w-full dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Powered by
            <a
              href="https://uptime-kita.com"
              target="_blank"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
            >
              Uptime Kita
            </a>
          </div>
          <div class="text-xs text-gray-400 dark:text-gray-500">
            Real-time uptime monitoring
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'
import type { Monitor, MonitorHistory } from '@/types/monitor'

interface Props {
  monitor: { data: Monitor }
  histories: MonitorHistory[]
  uptimeStats: {
    '24h': number
    '7d': number
    '30d': number
    '90d': number
  }
  responseTimeStats: {
    average: number
    min: number
    max: number
  }
  recentIncidents: any[]
}

const props = defineProps<Props>()
const monitor = computed(() => props.monitor.data)

// Theme toggle functionality
const isDark = ref(false)

const toggleTheme = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

onMounted(() => {
  // Check for saved theme preference or default to light mode
  const savedTheme = localStorage.getItem('theme')
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

  if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }
})

// console.log('%cresources/js/pages/monitors/PublicShow.vue:291 monitor', 'color: #007acc;', monitor.value);

const recentIncidents = computed(() => {
  // If monitor hasn't been checked yet, return empty array
  if (monitor.value.uptime_status === 'not yet checked') {
    return []
  }

  return props.histories
    .filter(h => h.uptime_status !== 'up')
    .slice(0, 5)
})

// Calculate response time stats for last 24 hours
const last24HoursHistories = computed(() => {
  const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000)
  return props.histories.filter(h =>
    h.response_time && new Date(h.created_at) > oneDayAgo
  )
})

const avgResponseTime = computed(() => {
  // If monitor hasn't been checked yet, return 0
  if (monitor.value.uptime_status === 'not yet checked') {
    return 0
  }

  // Use the responseTimeStats from the server if available, otherwise calculate from histories
  if (props.responseTimeStats?.average) {
    return Math.round(props.responseTimeStats.average)
  }

  const histories = last24HoursHistories.value
  if (histories.length === 0) return 0
  const sum = histories.reduce((acc, h) => acc + (h.response_time || 0), 0)
  return Math.round(sum / histories.length)
})

const minResponseTime = computed(() => {
  // If monitor hasn't been checked yet, return 0
  if (monitor.value.uptime_status === 'not yet checked') {
    return 0
  }

  if (props.responseTimeStats?.min) {
    return Math.round(props.responseTimeStats.min)
  }

  const histories = last24HoursHistories.value
  if (histories.length === 0) return 0
  return Math.min(...histories.map(h => h.response_time || 0))
})

const maxResponseTime = computed(() => {
  // If monitor hasn't been checked yet, return 0
  if (monitor.value.uptime_status === 'not yet checked') {
    return 0
  }

  if (props.responseTimeStats?.max) {
    return Math.round(props.responseTimeStats.max)
  }

  const histories = last24HoursHistories.value
  if (histories.length === 0) return 0
  return Math.max(...histories.map(h => h.response_time || 0))
})

const getStatusIcon = (status: string): string => {
  switch (status) {
    case 'up':
      return 'checkCircle'
    case 'down':
      return 'xCircle'
    case 'not yet checked':
      return 'clock'
    default:
      return 'alertCircle'
  }
}

const getStatusText = (status: string): string => {
  switch (status) {
    case 'up':
      return 'Operational'
    case 'down':
      return 'Down'
    case 'not yet checked':
      return 'Not Yet Checked'
    default:
      return 'Degraded'
  }
}

const getCertificateIcon = (status: string | null): string => {
  switch (status) {
    case 'valid':
      return 'shieldCheck'
    case 'invalid':
      return 'shieldAlert'
    case 'not yet checked':
      return 'clock'
    case 'not applicable':
      return 'minus-circle'
    default:
      return 'clock'
  }
}

const getCertificateColor = (status: string | null): string => {
  switch (status) {
    case 'valid':
      return 'text-green-600'
    case 'invalid':
      return 'text-red-600'
    case 'not yet checked':
      return 'text-gray-600'
    case 'not applicable':
      return 'text-gray-400'
    default:
      return 'text-gray-600'
  }
}

const getCertificateText = (status: string | null): string => {
  switch (status) {
    case 'valid':
      return 'Valid'
    case 'invalid':
      return 'Invalid'
    case 'not yet checked':
      return 'Not Yet Checked'
    case 'not applicable':
      return 'Not Applicable'
    default:
      return 'Not Yet Checked'
  }
}

const getUptimeColor = (percentage: number): string => {
  if (percentage >= 99.5) return 'text-green-600 dark:text-green-400'
  if (percentage >= 95) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

const getPeriodLabel = (period: string): string => {
  const labels: Record<string, string> = {
    '24h': 'Last 24 Hours',
    '7d': 'Last 7 Days',
    '30d': 'Last 30 Days',
    '90d': 'Last 90 Days'
  }
  return labels[period] || period
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString()
}

const getDateRange = (): string => {
  const date = new Date()
  date.setDate(date.getDate() - 89)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const getUptimeDays = () => {
  const days = []
  const today = new Date()

  for (let i = 89; i >= 0; i--) {
    const date = new Date(today)
    date.setDate(date.getDate() - i)
    const dateStr = date.toISOString().split('T')[0]

    const dayData = monitor.value.uptimes_daily?.find(d => d.date === dateStr)

    days.push({
      date: dateStr,
      uptime: dayData?.uptime_percentage || 0
    })
  }

  return days
}
</script>
