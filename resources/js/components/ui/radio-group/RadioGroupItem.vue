<script setup lang="ts">
import type { RadioGroupItemProps } from 'reka-ui'
import { cn } from '@/lib/utils'
import { Circle } from 'lucide-vue-next'
import { RadioGroupIndicator, RadioGroupItem, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<RadioGroupItemProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props

    return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
    <RadioGroupItem
        v-bind="forwardedProps"
        :class="
            cn(
                'border-input text-primary focus-visible:ring-ring aspect-square h-4 w-4 rounded-full border shadow-xs focus:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    >
        <RadioGroupIndicator class="flex items-center justify-center">
            <Circle class="h-3.5 w-3.5 fill-current" />
        </RadioGroupIndicator>
    </RadioGroupItem>
</template>
