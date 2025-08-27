<template>
    <Head :title="`${props.domain} - Monitor Not Found`" />

    <div class="min-h-full bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <div class="fixed top-0 w-full bg-white shadow dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                <div class="flex justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <div class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 sm:h-8 sm:w-8 dark:bg-gray-700">
                            <Icon name="monitor" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="max-w-[200px] truncate text-lg font-bold text-gray-900 sm:max-w-none sm:text-xl lg:text-2xl dark:text-white">
                                {{ props.domain }}
                            </h1>
                            <p class="max-w-[200px] truncate text-xs text-gray-500 sm:max-w-none sm:text-sm dark:text-gray-400">
                                {{ props.suggestedUrl }}
                            </p>
                        </div>
                    </div>

                    <!-- Theme Toggle -->
                    <div class="flex items-center justify-center space-x-2 sm:justify-end">
                        <button
                            @click="toggleTheme"
                            class="rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        >
                            <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto mt-24 max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
            <div class="py-12 text-center sm:py-16">
                <!-- Icon -->
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 sm:h-20 sm:w-20 dark:bg-gray-800">
                    <Icon name="search" class="h-8 w-8 text-gray-400 sm:h-10 sm:w-10" />
                </div>

                <!-- Title -->
                <h2 class="mb-4 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white">Monitor Not Found</h2>

                <!-- Description -->
                <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-600 dark:text-gray-300">
                    We couldn't find a public monitor for <span class="font-semibold text-gray-900 dark:text-white">{{ props.domain }}</span
                    >. This monitor might be private or doesn't exist yet.
                </p>

                <!-- Action Cards -->
                <div class="mx-auto mb-8 grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Create Your Own Monitor -->
                    <Card class="text-left">
                        <CardContent class="p-6">
                            <div class="flex items-start space-x-4">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                    <Icon name="plus" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Create Your Own Monitor</h3>
                                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                                        Start monitoring your website's uptime and performance. Get instant alerts when your site goes down.
                                    </p>
                                    <a
                                        href="https://uptime.syofyanzuhad.dev"
                                        target="_blank"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <Icon name="externalLink" class="mr-2 h-4 w-4" />
                                        Get Started
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Check Public Monitors -->
                    <Card class="text-left">
                        <CardContent class="p-6">
                            <div class="flex items-start space-x-4">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                    <Icon name="globe" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Browse Public Monitors</h3>
                                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                                        Explore other public monitors to see what's being tracked and get inspired.
                                    </p>
                                    <a
                                        href="/public-monitors"
                                        target="_blank"
                                        class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                                    >
                                        <Icon name="externalLink" class="mr-2 h-4 w-4" />
                                        View Public Monitors
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Additional Info -->
                <div class="mx-auto max-w-2xl rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Why create a monitor?</h3>
                    <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div class="flex items-center space-x-2">
                            <Icon name="checkCircle" class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <span class="text-gray-600 dark:text-gray-300">Real-time uptime monitoring</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Icon name="checkCircle" class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <span class="text-gray-600 dark:text-gray-300">Instant alerts & notifications</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Icon name="checkCircle" class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <span class="text-gray-600 dark:text-gray-300">Performance analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <PublicFooter />
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicFooter from '@/components/PublicFooter.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    domain: string;
    suggestedUrl: string;
}

const props = defineProps<Props>();

// Theme toggle functionality
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>
