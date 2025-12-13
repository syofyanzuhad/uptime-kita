<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import type { ToastNotification } from '@/composables/useToastNotifications';

const props = defineProps<{
    toast: ToastNotification;
}>();

const emit = defineEmits<{
    close: [];
}>();

const progress = ref(100);
let progressInterval: ReturnType<typeof setInterval> | null = null;

const isStatusUp = computed(() => props.toast.type === 'status-up' || props.toast.type === 'success');
const isStatusDown = computed(() => props.toast.type === 'status-down' || props.toast.type === 'error');

const containerClass = computed(() => {
    if (isStatusUp.value) return 'border-green-200 dark:border-green-800';
    if (isStatusDown.value) return 'border-red-200 dark:border-red-800';
    return 'border-gray-200 dark:border-gray-700';
});

const iconContainerClass = computed(() => {
    if (isStatusUp.value) return 'bg-green-100 dark:bg-green-900/30';
    if (isStatusDown.value) return 'bg-red-100 dark:bg-red-900/30';
    return 'bg-gray-100 dark:bg-gray-700';
});

const iconName = computed(() => {
    if (isStatusUp.value) return 'CheckCircle';
    if (isStatusDown.value) return 'AlertCircle';
    return 'Info';
});

const iconClass = computed(() => {
    if (isStatusUp.value) return 'text-green-600 dark:text-green-400';
    if (isStatusDown.value) return 'text-red-600 dark:text-red-400';
    return 'text-gray-600 dark:text-gray-400';
});

const closeButtonClass = computed(() => {
    if (isStatusUp.value) return 'text-green-400 hover:text-green-500 focus:ring-green-500';
    if (isStatusDown.value) return 'text-red-400 hover:text-red-500 focus:ring-red-500';
    return 'text-gray-400 hover:text-gray-500 focus:ring-gray-500';
});

const progressClass = computed(() => {
    if (isStatusUp.value) return 'bg-green-500';
    if (isStatusDown.value) return 'bg-red-500';
    return 'bg-blue-500';
});

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString();
};

onMounted(() => {
    if (props.toast.duration && props.toast.duration > 0) {
        const startTime = Date.now();
        const duration = props.toast.duration;

        progressInterval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            progress.value = Math.max(0, 100 - (elapsed / duration) * 100);
        }, 50);
    }
});

onUnmounted(() => {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});
</script>

<template>
    <div
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border bg-white shadow-lg dark:bg-gray-800"
        :class="containerClass"
    >
        <div class="p-4">
            <div class="flex items-start">
                <!-- Status Icon -->
                <div class="shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full" :class="iconContainerClass">
                        <Icon :name="iconName" class="h-5 w-5" :class="iconClass" />
                    </div>
                </div>

                <!-- Content -->
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ toast.title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ toast.message }}
                    </p>
                    <p v-if="toast.data?.changed_at" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ formatTime(toast.data.changed_at as string) }}
                    </p>
                </div>

                <!-- Close Button -->
                <div class="ml-4 shrink-0">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="closeButtonClass"
                    >
                        <span class="sr-only">Close</span>
                        <Icon name="X" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress bar for auto-dismiss -->
        <div
            v-if="toast.duration && toast.duration > 0"
            class="h-1 transition-all duration-100"
            :class="progressClass"
            :style="{ width: `${progress}%` }"
        />
    </div>
</template>
