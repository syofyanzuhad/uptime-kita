<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'h5',
})
</script>

<template>
  <Primitive
    data-slot="alert-title"
    :as="as"
    :as-child="asChild"
    :class="cn('mb-1 font-medium leading-none tracking-tight', props.class)"
  >
    <slot />
  </Primitive>
</template>
