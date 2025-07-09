<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
})
</script>

<template>
  <Primitive
    data-slot="alert-description"
    :as="as"
    :as-child="asChild"
    :class="cn('text-sm [&_p]:leading-relaxed', props.class)"
  >
    <slot />
  </Primitive>
</template>
