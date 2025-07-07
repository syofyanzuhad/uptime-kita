<template>
    <SwitchRoot
      v-bind="forwarded"
      :data-state="forwarded.modelValue ? 'checked' : 'unchecked'"
      data-slot="switch"
      :class="
        cn(
          'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
          'disabled:cursor-not-allowed disabled:opacity-50',
          // Use computed classes based on state
          switchBackgroundClasses,
          switchHoverClasses,
          props.class
        )
      "
    >
      <SwitchThumb
        data-slot="switch-thumb"
        :class="[
          'pointer-events-none block h-5 w-5 rounded-full bg-white dark:bg-gray-200 shadow-lg ring-0 transition-transform',
          'data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0',
        ]"
        :data-state="forwarded.modelValue ? 'checked' : 'unchecked'"
      />
    </SwitchRoot>
  </template>

  <script setup lang="ts">
  import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui'
  import { SwitchRoot, SwitchThumb, useForwardPropsEmits } from 'reka-ui'
  import { cn } from '@/lib/utils'
  import { computed, type HTMLAttributes } from 'vue'

  const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class'] }>()
  const emits = defineEmits<SwitchRootEmits>()

  // Pisahkan class dari props lainnya
  const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props
    return delegated
  })

  // Gabungkan props dan emits agar dikirim ke <SwitchRoot>
  const forwarded = useForwardPropsEmits(delegatedProps, emits)

  // Compute background classes based on state
  const switchBackgroundClasses = computed(() => {
    return props.modelValue
      ? 'bg-green-500'
      : 'bg-gray-300 dark:bg-gray-600'
  })

  // Compute hover classes based on state
  const switchHoverClasses = computed(() => {
    return props.modelValue
      ? 'hover:bg-green-600'
      : 'hover:bg-gray-400 dark:hover:bg-gray-500'
  })
  </script>
