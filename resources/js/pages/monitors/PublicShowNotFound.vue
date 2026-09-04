<template>
    <PublicLayout
        :title="`${props.domain} - Monitor Not Found`"
        description="Public monitor not found on Uptime Kita"
        :share-url="`/m/${props.domain}`"
        share-text="Check out Uptime Kita"
    >
        <template #header-left>
            <div class="flex items-center gap-3">
                <Link
                    href="/"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200/80 bg-white p-2 text-gray-600 shadow-sm hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <Icon name="arrowLeft" class="h-4 w-4" />
                </Link>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-extrabold text-gray-900 sm:text-xl dark:text-white">
                        {{ props.domain }}
                    </h1>
                    <p class="truncate text-xs text-gray-400">Monitor Not Found</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-4xl py-12 text-center sm:py-16">
            <!-- Icon -->
            <div
                class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-blue-50 text-blue-600 shadow-inner dark:bg-blue-950/40 dark:text-blue-400"
            >
                <Icon name="search" class="h-10 w-10" />
            </div>

            <!-- Title -->
            <h2 class="mb-3 text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">Monitor Not Found</h2>

            <!-- Description -->
            <p class="mx-auto mb-10 max-w-xl text-base text-gray-600 dark:text-gray-300">
                We couldn't find a public monitor for <span class="font-bold text-gray-900 dark:text-white">{{ props.domain }}</span
                >. This monitor might be private or hasn't been set up yet.
            </p>

            <!-- Action Cards -->
            <div class="mb-10 grid grid-cols-1 gap-6 text-left md:grid-cols-2">
                <!-- Create Monitor Card -->
                <Card
                    class="rounded-3xl border border-gray-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm transition-all hover:border-blue-400 hover:shadow-md dark:border-gray-800/80 dark:bg-gray-900/80"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/20"
                        >
                            <Icon name="plus" class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="mb-1.5 text-lg font-bold text-gray-900 dark:text-white">Start Monitoring This Site</h3>
                            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                                Set up automated checks, SSL expiration tracking, and multi-channel instant outage alerts.
                            </p>
                            <Link
                                :href="`/monitors/create?url=${encodeURIComponent(props.suggestedUrl || 'https://' + props.domain)}`"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95"
                            >
                                <span>Monitor {{ props.domain }}</span>
                                <Icon name="arrowRight" class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </Card>

                <!-- Browse Public Directory Card -->
                <Card
                    class="rounded-3xl border border-gray-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm transition-all hover:border-emerald-400 hover:shadow-md dark:border-gray-800/80 dark:bg-gray-900/80"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-500/20"
                        >
                            <Icon name="globe" class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="mb-1.5 text-lg font-bold text-gray-900 dark:text-white">Explore Public Directory</h3>
                            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                                Discover community monitors, check global web health, and inspect response time metrics.
                            </p>
                            <Link
                                href="/public-monitors"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <span>Browse Monitors</span>
                                <Icon name="arrowRight" class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';

interface Props {
    domain: string;
    suggestedUrl: string;
}

const props = defineProps<Props>();
</script>
