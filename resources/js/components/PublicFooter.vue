<template>
    <div class="bottom-0 mt-20 w-full border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between space-y-2 sm:flex-row sm:space-y-0">
                <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex items-center space-x-2">
                        Powered by
                        <a
                            :href="poweredByUrl"
                            target="_blank"
                            class="ml-1 font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            {{ poweredByText }}
                        </a>
                        <span class="text-gray-500 dark:text-gray-400">|</span>
                        <a
                            href="https://github.com/syofyanzuhad/uptime-kita"
                            target="_blank"
                            class="text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            title="View on GitHub"
                        >
                            <Icon name="github" class="h-4 w-4" />
                        </a>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ tagline }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    poweredByText?: string;
    poweredByUrl?: string;
    tagline?: string;
}

withDefaults(defineProps<Props>(), {
    poweredByText: 'Uptime Kita',
    poweredByUrl: '/',
    tagline: 'Real-time uptime monitoring',
});
</script>
