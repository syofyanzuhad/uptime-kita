<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'
import { type AlertVariants, alertVariants } from '.'
import Icon from '@/components/Icon.vue'

interface Props extends PrimitiveProps {
  variant?: AlertVariants['variant']
  class?: HTMLAttributes['class']
  dismissible?: boolean
  onDismiss?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
  dismissible: false,
})

const emit = defineEmits<{
  dismiss: []
}>()

const handleDismiss = () => {
  emit('dismiss')
  props.onDismiss?.()
}

const getIconName = (variant: string) => {
  switch (variant) {
    case 'destructive':
      return 'alert-circle'
    case 'warning':
      return 'alert-triangle'
    case 'success':
      return 'check-circle'
    case 'info':
      return 'info'
    default:
      return 'info'
  }
}
</script>

<template>
  <Primitive
    data-slot="alert"
    :as="as"
    :as-child="asChild"
    :class="cn(alertVariants({ variant }), props.class)"
    role="alert"
  >
    <div class="flex items-start gap-3">
      <Icon
        :name="getIconName(variant || 'default')"
        class="h-5 w-5 flex-shrink-0 mt-0.5"
      />
      <div class="flex-1">
        <slot />
      </div>
      <button
        v-if="dismissible"
        @click="handleDismiss"
        class="flex-shrink-0 ml-auto -mr-1 -mt-1 h-6 w-6 rounded-md p-1 hover:bg-black/5 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-ring"
        type="button"
        aria-label="Dismiss alert"
      >
        <Icon name="x" class="h-4 w-4" />
      </button>
    </div>
  </Primitive>
</template>
