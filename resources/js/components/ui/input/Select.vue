<script setup lang="ts">
import {
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectIcon,
  SelectPortal,
  SelectContent,
  SelectViewport,
  SelectItem,
  SelectItemText,
  SelectItemIndicator,
} from 'reka-ui'
import type { AcceptableValue } from 'reka-ui'
import { toRefs } from 'vue'

const props = defineProps<{
  modelValue: AcceptableValue
  items: Array<{ label: string; value: AcceptableValue }>
  placeholder?: string
  disabled?: boolean
  class?: string
}>()
const emit = defineEmits<{ (e: 'update:modelValue', value: AcceptableValue): void }>()

const { modelValue, items, placeholder, disabled, class: className } = toRefs(props)

function onUpdate(value: AcceptableValue) {
  emit('update:modelValue', value)
}
</script>

<template>
  <SelectRoot :model-value="modelValue" @update:modelValue="onUpdate" :disabled="disabled">
    <SelectTrigger
      :class="[
        'border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent dark:bg-input/30 dark:border-input text-foreground dark:text-foreground px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        className
      ]"
      :disabled="disabled"
    >
      <SelectValue :placeholder="placeholder" />
      <SelectIcon />
    </SelectTrigger>
    <SelectPortal>
      <SelectContent
        class="z-50 min-w-[8rem] rounded-md border border-input dark:border-input bg-background dark:bg-input p-1 shadow-md text-foreground dark:text-foreground"
      >
        <SelectViewport>
          <SelectItem
            v-for="opt in items"
            :key="String(opt.value ?? '')"
            :value="opt.value ?? ''"
            class="cursor-pointer select-none rounded px-2 py-1.5 text-sm text-foreground dark:text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-input focus:bg-accent focus:text-accent-foreground dark:focus:bg-input/50 transition-colors"
          >
            <SelectItemText>{{ opt.label }}</SelectItemText>
            <SelectItemIndicator />
          </SelectItem>
        </SelectViewport>
      </SelectContent>
    </SelectPortal>
  </SelectRoot>
</template>
