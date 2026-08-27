<template>
    <footer class="mt-auto border-t border-gray-200/80 bg-white/50 backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/50">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                <div class="flex flex-col items-center gap-2 text-center md:items-start md:text-left">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                        <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span>{{ poweredByText }}</span>
                        <span class="text-gray-300 dark:text-gray-700">•</span>
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ tagline }}</span>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400 md:justify-start">
                        <Link href="/public-monitors" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Public Monitors</Link>
                        <Link href="/monitors" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Wallboard</Link>
                        <Link href="/status/demo" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Demo Status Page</Link>
                        <a href="https://github.com/syofyanzuhad/uptime-kita" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors">
                            <Icon name="github" class="h-3.5 w-3.5" />
                            <span>GitHub</span>
                        </a>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-3 sm:flex-row md:items-end">
                    <div id="wcb" class="carbonbadge dark:wcb-d"></div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    poweredByText?: string;
    poweredByUrl?: string;
    tagline?: string;
}

withDefaults(defineProps<Props>(), {
    poweredByText: 'Uptime Kita',
    poweredByUrl: '/',
    tagline: 'Open-source real-time uptime monitoring',
});
</script>
