<script setup lang="ts">
import { Button } from '@/components/ui/button';
defineProps({
  channel: { type: Object, required: true }
});
defineEmits(['edit', 'delete', 'toggle']);
</script>

<template>
  <div class="flex items-center justify-between border rounded p-3 mb-2">
    <div>
      <div class="font-semibold">
        {{ channel.type }}
        <span v-if="!channel.is_enabled" class="text-xs text-gray-400">(disabled)</span>
      </div>
      <div class="text-sm text-gray-500">{{ channel.destination }}</div>
    </div>
    <div class="flex items-center space-x-2">
      <Button @click="$emit('edit', channel)" variant="outline" size="sm">Edit</Button>
      <Button @click="$emit('delete', channel)" variant="destructive" size="sm">Delete</Button>
      <Button @click="$emit('toggle', channel)" variant="outline" size="sm">{{ channel.is_enabled ? 'Disable' : 'Enable' }}</Button>
    </div>
  </div>
</template>
