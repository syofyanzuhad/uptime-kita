<template>
  <Head title="Public Monitors - Uptime Kita" />

  <div class="min-h-full bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white fixed top-0 w-full dark:bg-gray-800 shadow z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="w-6 h-6 sm:w-10 sm:h-10 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <Link href="/">
                <img src="/images/uptime-kita.jpg" alt="Uptime Kita" class="w-6 h-6 sm:w-10 sm:h-10 rounded object-cover" />
              </Link>
            </div>
            <div class="min-w-0 flex-1">
              <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">
                Public Monitors
              </h1>
              <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                Discover and monitor public websites
              </p>
            </div>
          </div>

          <!-- Theme Toggle -->
          <div class="flex items-center justify-center sm:justify-end space-x-2">
            <button
              @click="toggleTheme"
              class="p-2 rounded-full cursor-pointer bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
              :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
              <Icon
                :name="isDark ? 'sun' : 'moon'"
                class="w-4 h-4 text-gray-600 dark:text-gray-300"
              />
            </button>
            <!-- dashboard button -->
            <Link 
              href="/dashboard" 
              class="p-2 rounded-full cursor-pointer bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
              aria-label="Go to dashboard"
            >
              <Icon name="home" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 mt-24">
      <!-- Stats Overview -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <Card>
          <CardContent class="p-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_public }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Total Public</div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.up }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Operational</div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.down }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Down</div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ Math.round((stats.up / stats.total_public) * 100) || 0 }}%</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Uptime</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card class="mb-6 p-2">
        <CardContent class="p-4">
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
              <label for="search-monitors" class="sr-only">Search monitors</label>
              <input
                id="search-monitors"
                v-model="searchQuery"
                type="text"
                placeholder="Search monitors..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @input="debounceSearch"
              />
            </div>

            <!-- Status Filter -->
            <div class="sm:w-48">
              <label for="status-filter" class="sr-only">Filter by status</label>
              <select
                id="status-filter"
                v-model="statusFilter"
                @change="applyFilters"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Status</option>
                <option value="up">Operational</option>
                <option value="down">Down</option>
              </select>
            </div>

            <!-- Create Button -->
            <div class="sm:w-auto">
              <button
                @click="createMonitor"
                class="w-full sm:w-auto cursor-pointer px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center space-x-2"
              >
                <Icon name="plus" class="w-4 h-4" />
                <span>Create Monitor</span>
              </button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Monitors Grid -->
      <div v-if="monitorsData.length === 0" class="text-center py-12">
        <Icon name="search" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No monitors found</h2>
        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search or filters</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <Card
          v-for="monitor in monitorsData"
          :key="monitor.id"
          class="hover:shadow-lg transition-shadow cursor-pointer p-0"
          @click="viewMonitor(monitor)"
        >
          <CardContent class="p-4">
            <div class="flex items-start space-x-4">
              <!-- Favicon -->
              <img
                v-if="monitor.favicon"
                :src="monitor.favicon"
                :alt="`${monitor.name} favicon`"
                class="w-6 h-6 rounded flex-shrink-0 drop-shadow-md dark:drop-shadow-white/30"
                @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
              />
              <div v-else class="w-6 h-6 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                <Icon name="globe" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
              </div>

              <!-- Monitor Info -->
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                  {{ monitor.host }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                  {{ monitor.url }}
                </p>

                <!-- Status Badge -->
                <div class="flex items-center space-x-2 mt-3">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      monitor.uptime_status === 'up'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                        : monitor.uptime_status === 'down'
                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                    ]"
                  >
                    <Icon
                      :name="getStatusIcon(monitor.uptime_status)"
                      class="w-3 h-3 inline mr-1"
                    />
                    {{ getStatusText(monitor.uptime_status) }}
                  </span>

                  <span v-if="monitor.today_uptime_percentage" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ monitor.today_uptime_percentage }}% uptime
                  </span>
                </div>

                <!-- Last Check -->
                <div v-if="monitor.last_check_date_human" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                  Last checked {{ monitor.last_check_date_human }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Load More Button -->
      <div v-if="monitorsMeta.current_page < monitorsMeta.last_page" class="mt-8 text-center">
        <button
          @click="loadMore"
          :disabled="isLoading"
          class="inline-flex items-center cursor-pointer px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors"
        >
          <Icon
            v-if="isLoading"
            name="loader"
            class="w-4 h-4 mr-2 animate-spin"
          />
          <span v-else>Load More Monitors</span>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <PublicFooter />
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'
import PublicFooter from '@/components/PublicFooter.vue'
import { Monitor } from '@/types/monitor'

interface PaginatorLink {
  url: string | null
  label: string
  active: boolean
}

interface Paginator<T> {
  data: T[]
  links: PaginatorLink[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}

interface Props {
  monitors: Paginator<Monitor>
  filters: {
    search: string | null
    status_filter: string
  }
  stats: {
    total: number
    up: number
    down: number
    total_public: number
  }
}

const props = defineProps<Props>()

// Reactive data for monitors
const monitorsData = ref(props.monitors.data || [])

// Clean the initial meta data (handle arrays)
const initialMeta = props.monitors.meta || { current_page: 1, last_page: 1 }
const cleanInitialMeta = {
  current_page: Array.isArray(initialMeta.current_page) ? initialMeta.current_page[0] : initialMeta.current_page,
  last_page: Array.isArray(initialMeta.last_page) ? initialMeta.last_page[0] : initialMeta.last_page,
  per_page: Array.isArray(initialMeta.per_page) ? initialMeta.per_page[0] : initialMeta.per_page,
  total: Array.isArray(initialMeta.total) ? initialMeta.total[0] : initialMeta.total,
  from: Array.isArray(initialMeta.from) ? initialMeta.from[0] : initialMeta.from,
  to: Array.isArray(initialMeta.to) ? initialMeta.to[0] : initialMeta.to,
}

const monitorsMeta = ref(cleanInitialMeta)
const monitorsLinks = ref(props.monitors.links || [])

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

// Search and filter functionality
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status_filter)
const isLoading = ref(false)

let searchTimeout: number | null = null

const debounceSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = window.setTimeout(() => {
    applyFilters()
  }, 300)
}

const applyFilters = () => {
  const params = new URLSearchParams()
  if (searchQuery.value) {
    params.append('search', searchQuery.value)
  }
  if (statusFilter.value !== 'all') {
    params.append('status_filter', statusFilter.value)
  }

  router.visit(`/public-monitors?${params.toString()}`, {
    preserveState: true,
    replace: true
  })
}

// Track active request to prevent duplicates
let activeLoadMoreRequest: AbortController | null = null

const loadMore = async () => {
  // Prevent multiple concurrent requests
  if (isLoading.value) return

  // Cancel any pending request
  if (activeLoadMoreRequest) {
    activeLoadMoreRequest.abort()
  }

  isLoading.value = true
  const nextPage = monitorsMeta.value.current_page + 1
  console.log('Monitors meta:', monitorsMeta.value)
  console.log('Next page:', nextPage)

  // Create new AbortController for this request
  activeLoadMoreRequest = new AbortController()

  const params = new URLSearchParams()
  params.append('page', nextPage.toString())
  if (searchQuery.value) {
    params.append('search', searchQuery.value)
  }
  if (statusFilter.value !== 'all') {
    params.append('status_filter', statusFilter.value)
  }

  try {
    const response = await fetch(`/public-monitors?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: activeLoadMoreRequest.signal
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    // Only update if we haven't been aborted
    if (!activeLoadMoreRequest.signal.aborted) {
      // Clean the meta data (handle arrays)
      const cleanMeta = {
        current_page: Array.isArray(data.meta.current_page) ? data.meta.current_page[0] : data.meta.current_page,
        last_page: Array.isArray(data.meta.last_page) ? data.meta.last_page[0] : data.meta.last_page,
        per_page: Array.isArray(data.meta.per_page) ? data.meta.per_page[0] : data.meta.per_page,
        total: Array.isArray(data.meta.total) ? data.meta.total[0] : data.meta.total,
        from: Array.isArray(data.meta.from) ? data.meta.from[0] : data.meta.from,
        to: Array.isArray(data.meta.to) ? data.meta.to[0] : data.meta.to,
      }

      // Append new monitors to existing data
      monitorsData.value.push(...data.data)
      monitorsMeta.value = cleanMeta
      monitorsLinks.value = data.links
    }
  } catch (error) {
    // Ignore abort errors
    if (error instanceof Error && error.name !== 'AbortError') {
      console.error('Error loading more monitors:', error)
    }
  } finally {
    isLoading.value = false
    activeLoadMoreRequest = null
  }
}

const viewMonitor = (monitor: Monitor) => {
  const domain = monitor.url.replace('https://', '').replace('http://', '')
  router.visit(`/m/${domain}`)
}

const createMonitor = () => {
  router.visit('/monitor/create')
}

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

// Watch for changes in props and update reactive data
watch(() => props.monitors, (newMonitors) => {
  monitorsData.value = newMonitors.data || []
  
  // Clean the meta data (handle arrays)
  const cleanMeta = {
    current_page: Array.isArray(newMonitors.meta.current_page) ? newMonitors.meta.current_page[0] : newMonitors.meta.current_page,
    last_page: Array.isArray(newMonitors.meta.last_page) ? newMonitors.meta.last_page[0] : newMonitors.meta.last_page,
    per_page: Array.isArray(newMonitors.meta.per_page) ? newMonitors.meta.per_page[0] : newMonitors.meta.per_page,
    total: Array.isArray(newMonitors.meta.total) ? newMonitors.meta.total[0] : newMonitors.meta.total,
    from: Array.isArray(newMonitors.meta.from) ? newMonitors.meta.from[0] : newMonitors.meta.from,
    to: Array.isArray(newMonitors.meta.to) ? newMonitors.meta.to[0] : newMonitors.meta.to,
  }
  
  monitorsMeta.value = cleanMeta
  monitorsLinks.value = newMonitors.links || []
}, { deep: true })

// Watch for changes in filters and update local state
watch(() => props.filters, (newFilters) => {
  searchQuery.value = newFilters.search || ''
  statusFilter.value = newFilters.status_filter
}, { deep: true })

onMounted(() => {
  // Check for saved theme preference or default to light mode
  const savedTheme = localStorage.getItem('theme')
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

  if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }
})
</script>
