<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

interface FlashMessage {
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}

const page = usePage();

// State for flash message
const showFlash = ref(false);
const flashMessage = ref('');
const flashType = ref<'success' | 'error' | 'warning' | 'info'>('info');
const autoDismiss = ref(true);
const dismissTimeout = ref<number | null>(null);

// Watch for flash messages from the page props
watch(
    () => page.props.flash as FlashMessage | undefined,
    (newFlash) => {
        if (newFlash) {
            flashMessage.value = newFlash.message;
            flashType.value = newFlash.type || 'info';
            showFlash.value = true;

            // Auto dismiss after 5 seconds for success/info, 10 seconds for warning/error
            if (autoDismiss.value) {
                const timeout = flashType.value === 'success' || flashType.value === 'info' ? 5000 : 10000;

                if (dismissTimeout.value) {
                    clearTimeout(dismissTimeout.value);
                }

                dismissTimeout.value = window.setTimeout(() => {
                    showFlash.value = false;
                }, timeout);
            }
        }
    },
    { deep: true, immediate: true },
);

// Manual dismiss function
const dismissFlash = () => {
    showFlash.value = false;
    if (dismissTimeout.value) {
        clearTimeout(dismissTimeout.value);
        dismissTimeout.value = null;
    }
};

// Clean up timeout on component unmount
onMounted(() => {
    return () => {
        if (dismissTimeout.value) {
            clearTimeout(dismissTimeout.value);
        }
    };
});
</script>

<template>
    <Alert v-if="showFlash" :variant="flashType === 'error' ? 'destructive' : flashType" class="relative mb-1">
        <AlertTitle class="capitalize">
            {{ flashType === 'success' ? 'Success' : flashType === 'error' ? 'Error' : flashType === 'warning' ? 'Warning' : 'Information' }}
        </AlertTitle>
        <AlertDescription>
            {{ flashMessage }}
        </AlertDescription>

        <!-- Dismiss button -->
        <button
            @click="dismissFlash"
            class="absolute top-2 right-2 rounded-md p-1 transition-colors hover:bg-gray-200 dark:hover:bg-gray-700"
            aria-label="Dismiss"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </Alert>
</template>
