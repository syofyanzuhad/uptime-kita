<script setup lang="ts">
import { computed } from 'vue'
import Icon from '@/components/Icon.vue'
import { isExternalUrl, getExternalLinkAttributes } from '@/lib/link-utils'

interface Props {
  href: string
  label?: string
  showIcon?: boolean
  iconSize?: 'sm' | 'md' | 'lg'
  className?: string
  iconClassName?: string
  ariaLabel?: string
  forceExternal?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true,
  iconSize: 'sm',
  className: 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline decoration-blue-300 dark:decoration-blue-600 underline-offset-2 transition-colors duration-200',
  iconClassName: 'text-gray-400 dark:text-gray-500',
  ariaLabel: undefined,
  forceExternal: false
})

// Check if link is external
const isExternal = computed(() => props.forceExternal || isExternalUrl(props.href))

// Generate link attributes
const linkAttributes = computed(() => {
  return getExternalLinkAttributes(props.href, props.ariaLabel || props.label)
})

// Get icon size classes
const iconSizeClasses = computed(() => {
  switch (props.iconSize) {
    case 'sm':
      return 'w-3 h-3'
    case 'md':
      return 'w-4 h-4'
    case 'lg':
      return 'w-5 h-5'
    default:
      return 'w-3 h-3'
  }
})
</script>

<template>
  <a
    v-bind="linkAttributes"
    :class="className"
    class="inline-flex items-center gap-1"
  >
    <slot>
      <span v-if="label">{{ label }}</span>
      <span v-else>{{ href }}</span>
    </slot>
    
    <Icon
      v-if="showIcon && isExternal"
      name="externalLink"
      :class="[iconSizeClasses, iconClassName]"
      aria-hidden="true"
    />
  </a>
</template>
