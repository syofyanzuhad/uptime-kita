<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Check, ChevronDown } from 'lucide-vue-next'
import {
  SelectContent,
  SelectIcon,
  SelectItem,
  SelectItemIndicator,
  SelectItemText,
  SelectPortal,
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectViewport,
} from 'reka-ui'
import type { AcceptableValue } from 'reka-ui'
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: AcceptableValue
  items: Array<{ label: string; value: AcceptableValue }>
  placeholder?: string
  disabled?: boolean
  class?: string
}>()
const emit = defineEmits<{ (e: 'update:modelValue', value: AcceptableValue): void }>()

const EMPTY_SENTINEL = '__EMPTY_VALUE__'

const safeModelValue = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    const hasEmpty = props.items.some(opt => opt.value === '' || opt.value === null || opt.value === undefined)
    return hasEmpty ? EMPTY_SENTINEL : undefined
  }
  return String(props.modelValue)
})

function getSafeItemValue(val: AcceptableValue): string {
  if (val === '' || val === null || val === undefined) {
    return EMPTY_SENTINEL
  }
  return String(val)
}

function onUpdate(value: AcceptableValue) {
  const resolved = value === EMPTY_SENTINEL ? '' : value
  emit('update:modelValue', resolved)
}
</script>

<template>
  <SelectRoot :model-value="safeModelValue" @update:modelValue="onUpdate" :disabled="disabled">
    <SelectTrigger
      :class="cn(
        'border-input flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border bg-transparent dark:bg-input/30 dark:border-input text-foreground dark:text-foreground px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
        props.class
      )"
      :disabled="disabled"
    >
      <SelectValue :placeholder="placeholder" class="truncate text-left" />
      <SelectIcon as-child>
        <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
      </SelectIcon>
    </SelectTrigger>
    <SelectPortal>
      <SelectContent
        class="z-50 min-w-[8rem] overflow-hidden rounded-md border border-input dark:border-input bg-popover dark:bg-popover p-1 shadow-md text-popover-foreground dark:text-popover-foreground"
      >
        <SelectViewport class="p-1">
          <SelectItem
            v-for="opt in items"
            :key="String(opt.value ?? '')"
            :value="getSafeItemValue(opt.value)"
            class="relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 transition-colors"
          >
            <SelectItemText>{{ opt.label }}</SelectItemText>
            <span class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
              <SelectItemIndicator>
                <Check class="h-4 w-4" />
              </SelectItemIndicator>
            </span>
          </SelectItem>
        </SelectViewport>
      </SelectContent>
    </SelectPortal>
  </SelectRoot>
</template>
