<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

// --- INTERFACES ---
interface Uptime {
  date: string;
  uptime_percentage: number;
}

// --- PROPS ---
const scrollContainer = ref<HTMLDivElement | null>(null)

const props = defineProps<{
  monitorId: number;
  isAuthenticated: boolean;
  uptimesDaily?: Uptime[];
  isLoading: boolean;
  error?: string | null;
}>()

// Auto-scroll to right when uptimesDaily or isLoading changes
watch(
  () => [props.uptimesDaily, props.isLoading],
  () => {
    setTimeout(() => {
      if (scrollContainer.value) {
        scrollContainer.value.scrollLeft = scrollContainer.value.scrollWidth
      }
    }, 0)
  },
  { immediate: true }
)

onMounted(() => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollLeft = scrollContainer.value.scrollWidth
  }
})

// --- HELPER FUNCTIONS ---
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

const firstDay = computed(() => getLatest100Days()[0] || '')
const lastDay = computed(() => getLatest100Days()[getLatest100Days().length - 1] || '')
</script>

<template>
  <div class="mt-2">
    <template v-if="isAuthenticated">
      <div v-if="isLoading" class="text-xs text-gray-400">Loading uptime history...</div>
      <div v-else-if="error" class="text-xs text-red-400">{{ error }}</div>
      <div v-else-if="uptimesDaily && uptimesDaily.length > 0">
          <div ref="scrollContainer" class="flex overflow-x-auto items-end h-16 bg-gray-50 dark:bg-gray-900 rounded p-2 border border-gray-200 dark:border-gray-700 w-full min-w-[320px]">
            <template v-for="date in getLatest100Days()" :key="date">
              <template v-if="uptimesDaily.some(u => u.date === date)">
                <div
                  v-for="uptime in uptimesDaily.filter(u => u.date === date)"
                  :key="uptime.date"
                  class="relative group flex flex-col items-center flex-1 min-w-1.5 mx-px"
                >
                  <div
                    :class="[
                      'h-8 w-full rounded transition-all duration-200 min-w-1.5',
                      uptime.uptime_percentage >= 99 ? 'bg-green-500' : uptime.uptime_percentage >= 90 ? 'bg-yellow-400' : 'bg-red-500'
                    ]"
                  ></div>
                  <div class="absolute bottom-full mb-1 hidden group-hover:block bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-2 py-1 rounded shadow z-10 whitespace-nowrap">
                    {{ uptime.date }}<br />{{ uptime.uptime_percentage.toFixed(2) }}%
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="relative group flex flex-col items-center flex-1 min-w-1.5 mx-px">
                  <div class="h-8 w-full bg-gray-300 dark:bg-gray-700 rounded min-w-1.5"></div>
                  <div class="absolute bottom-full mb-1 hidden group-hover:block bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-2 py-1 rounded shadow z-10 whitespace-nowrap">
                    {{ date }}<br />No data
                  </div>
                </div>
              </template>
            </template>
          </div>
        <div class="flex justify-between text-[10px] text-gray-400 dark:text-gray-500 mt-1">
          <span>{{ firstDay }}</span>
          <span>{{ lastDay }}</span>
        </div>
      </div>
    </template>
    <template v-else>
      <p class="text-xs text-gray-400 italic mt-2">
        <Link :href="route('login')" class="text-blue-600 cursor-pointer dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Login</Link> to see uptime history
      </p>
    </template>
  </div>
</template>
