<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import { initializeTheme } from '@/composables/useAppearance';
import { onMounted } from 'vue';

onMounted(() => {
    initializeTheme();
});
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 transition-colors duration-300 dark:bg-gray-950 dark:text-gray-100">
        <!-- Minimal Top Bar (Optional) -->
        <header class="border-b border-gray-100 bg-white/80 py-3 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/80">
            <div class="mx-auto flex max-w-full items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <img src="/favicon.svg" class="h-6 w-6" alt="Logo" />
                    <span class="text-sm font-bold tracking-tight">UPTIME <span class="text-blue-600">KITA</span></span>
                    <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                        >WALLBOARD</span
                    >
                </div>
                <div class="flex items-center gap-4 text-[10px] font-medium tracking-widest text-gray-500 uppercase">
                    {{ new Date().toLocaleDateString() }}
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            <FlashMessage />
            <slot />
        </main>

        <footer class="flex items-center justify-center p-8">
            <div id="wcb" class="carbonbadge dark:wcb-d"></div>
        </footer>
    </div>
</template>
