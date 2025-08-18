<template>
  <div class="bg-white bottom-0 w-full dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
        <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            Powered by
            <ExternalLink
              :href="poweredByUrl"
              :label="poweredByText"
              referrer-source="footer"
              referrer-campaign="powered_by"
              class-name="text-blue-600 ml-1 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
              :show-icon="false"
            />
            <span class="text-gray-500 dark:text-gray-400">|</span>
            <ExternalLink
              href="https://github.com/syofyanzuhad/uptime-kita"
              referrer-source="footer"
              referrer-campaign="github"
              class-name="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
              aria-label="View on GitHub"
              :show-icon="false"
            >
              <Icon name="github" class="w-4 h-4" />
            </ExternalLink>
          </div>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          {{ tagline }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue'
import ExternalLink from '@/components/ExternalLink.vue'

interface Props {
  poweredByText?: string
  poweredByUrl?: string
  tagline?: string
}

withDefaults(defineProps<Props>(), {
  poweredByText: 'Uptime Kita',
  poweredByUrl: '/',
  tagline: 'Real-time uptime monitoring'
})
</script>
