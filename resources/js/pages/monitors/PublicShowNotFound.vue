<template>
  <Head :title="`${domain} - Monitor Not Found`" />

  <div class="min-h-full bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white fixed top-0 w-full dark:bg-gray-800 shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex sm:flex-row sm:items-center justify-between space-y-4 sm:space-y-0">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
              <Icon name="monitor" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            </div>
            <div class="min-w-0 flex-1">
              <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white truncate max-w-[200px] sm:max-w-none">
                {{ domain }}
              </h1>
              <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate max-w-[200px] sm:max-w-none">
                {{ suggestedUrl }}
              </p>
            </div>
          </div>

          <!-- Theme Toggle -->
          <div class="flex items-center justify-center sm:justify-end space-x-2">
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 mt-24">
      <div class="text-center py-12 sm:py-16">
        <!-- Icon -->
        <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
          <Icon name="search" class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
        </div>

        <!-- Title -->
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-4">
          Monitor Not Found
        </h2>

        <!-- Description -->
        <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-2xl mx-auto">
          We couldn't find a public monitor for <span class="font-semibold text-gray-900 dark:text-white">{{ domain }}</span>.
          This monitor might be private or doesn't exist yet.
        </p>

        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto mb-8">
          <!-- Create Your Own Monitor -->
          <Card class="text-left">
            <CardContent class="p-6">
              <div class="flex items-start space-x-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Icon name="plus" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Create Your Own Monitor
                  </h3>
                  <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Start monitoring your website's uptime and performance. Get instant alerts when your site goes down.
                  </p>
                  <a
                    href="https://uptime.syofyanzuhad.dev"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                  >
                    <Icon name="externalLink" class="w-4 h-4 mr-2" />
                    Get Started
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Check Public Monitors -->
          <Card class="text-left">
            <CardContent class="p-6">
              <div class="flex items-start space-x-4">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Icon name="globe" class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Browse Public Monitors
                  </h3>
                  <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Explore other public monitors to see what's being tracked and get inspired.
                  </p>
                  <a
                    href="https://uptime.syofyanzuhad.dev/public-monitors"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                  >
                    <Icon name="externalLink" class="w-4 h-4 mr-2" />
                    View Public Monitors
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Additional Info -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 max-w-2xl mx-auto">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
            Why create a monitor?
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="flex items-center space-x-2">
              <Icon name="checkCircle" class="w-4 h-4 text-green-600 dark:text-green-400" />
              <span class="text-gray-600 dark:text-gray-300">Real-time uptime monitoring</span>
            </div>
            <div class="flex items-center space-x-2">
              <Icon name="checkCircle" class="w-4 h-4 text-green-600 dark:text-green-400" />
              <span class="text-gray-600 dark:text-gray-300">Instant alerts & notifications</span>
            </div>
            <div class="flex items-center space-x-2">
              <Icon name="checkCircle" class="w-4 h-4 text-green-600 dark:text-green-400" />
              <span class="text-gray-600 dark:text-gray-300">Performance analytics</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-white bottom-0 w-full dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Powered by
            <a
              href="https://uptime.syofyanzuhad.dev"
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
import { ref, onMounted, onUnmounted } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'

interface Props {
  domain: string
  suggestedUrl: string
}

const props = defineProps<Props>()

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
</script>
