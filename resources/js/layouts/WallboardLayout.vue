<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';

const page = usePage();

onMounted(() => {
    initializeTheme();
});
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">
        <!-- Minimal Top Bar (Optional) -->
        <header class="border-b border-gray-100 bg-white/80 py-3 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/80">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="/favicon.svg" class="h-6 w-6" alt="Logo" />
                    <span class="text-sm font-bold tracking-tight">UPTIME <span class="text-blue-600">KITA</span></span>
                    <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">WALLBOARD</span>
                </div>
                <div class="flex items-center gap-4 text-[10px] text-gray-500 uppercase tracking-widest font-medium">
                    {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            <FlashMessage />
            <slot />
        </main>
    </div>
</template>
