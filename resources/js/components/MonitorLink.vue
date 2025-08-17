<script setup lang="ts">
import { computed } from 'vue'
import ExternalLink from '@/components/ExternalLink.vue'
import { Monitor } from '@/types/monitor'

interface Props {
  monitor: Monitor
  showFavicon?: boolean
  showStatus?: boolean
  className?: string
  linkClassName?: string
  referrerParam?: string
  referrerSource?: string
  referrerCampaign?: string
  autoReferrer?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showFavicon: true,
  showStatus: false,
  className: '',
  linkClassName: 'text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400',
  referrerParam: undefined,
  referrerSource: undefined,
  referrerCampaign: undefined,
  autoReferrer: true
})

// Generate display text
const displayText = computed(() => {
  return props.monitor.host || props.monitor.name || props.monitor.url
})

// Generate aria label for accessibility
const ariaLabel = computed(() => {
  const status = props.monitor.uptime_status === 'up' ? 'operational' : 
                 props.monitor.uptime_status === 'down' ? 'down' : 'unknown status'
  return `Visit ${displayText.value} (${status})`
})
</script>

<template>
  <div :class="['flex items-center gap-2', className]">
    <!-- Favicon -->
    <img
      v-if="showFavicon && monitor.favicon"
      :src="monitor.favicon"
      :alt="`${displayText} favicon`"
      class="w-4 h-4 rounded flex-shrink-0 drop-shadow-sm dark:drop-shadow-white/30"
      @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
    />
    
    <!-- External Link -->
    <ExternalLink
      :href="monitor.url"
      :label="displayText"
      :aria-label="ariaLabel"
      :class-name="linkClassName"
      :referrer-param="referrerParam"
      :referrer-source="referrerSource"
      :referrer-campaign="referrerCampaign"
      :auto-referrer="autoReferrer"
      show-icon
      icon-size="sm"
    />
    
    <!-- Status Badge (optional) -->
    <span
      v-if="showStatus"
      :class="[
        'px-2 py-1 rounded-full text-xs font-medium',
        monitor.uptime_status === 'up'
          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
          : monitor.uptime_status === 'down'
          ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
          : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
      ]"
    >
      {{ monitor.uptime_status === 'up' ? 'Operational' : 
         monitor.uptime_status === 'down' ? 'Down' : 'Unknown' }}
    </span>
  </div>
</template>
