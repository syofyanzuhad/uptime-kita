<script setup lang="ts">
import StatusToast from './StatusToast.vue';
import { globalToasts } from '@/composables/useToastNotifications';

const { toasts, removeToast } = globalToasts;
</script>

<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6">
        <div class="flex w-full flex-col items-center gap-4 sm:items-end">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                move-class="transition-transform duration-300"
            >
                <StatusToast v-for="toast in toasts" :key="toast.id" :toast="toast" @close="removeToast(toast.id)" />
            </TransitionGroup>
        </div>
    </div>
</template>
