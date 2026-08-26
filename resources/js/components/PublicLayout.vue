<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicFooter from '@/components/PublicFooter.vue';
import ServerStatsBadge from '@/components/ServerStatsBadge.vue';
import ShareDropdown from '@/components/ShareDropdown.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import ToastContainer from '@/components/ToastContainer.vue';
import { useTheme } from '@/composables/useTheme';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    title: string;
    description: string;
    ogImage?: string;
    canonicalUrl?: string;
    shareUrl: string;
    shareText: string;
    showServerStats?: boolean;
    jsonLd?: Record<string, any>;
}>();

const { isDark, toggleTheme } = useTheme();
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
function onOnline() { isOnline.value = true; }
function onOffline() { isOnline.value = false; }
onMounted(() => { window.addEventListener('online', onOnline); window.addEventListener('offline', onOffline); });
onUnmounted(() => { window.removeEventListener('online', onOnline); window.removeEventListener('offline', onOffline); });

const jsonLdString = computed(() => props.jsonLd ? JSON.stringify(props.jsonLd) : '');
</script>

<template>
    <OfflineBanner v-if="!isOnline" />
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta v-if="ogImage" property="og:image" :content="ogImage" />
        <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
        <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />
        <!-- eslint-disable-next-line vue/no-v-text-v-html-on-component -- JSON-LD needs raw innerHTML inside <script> -->
        <component :is="'script'" v-if="jsonLdString" type="application/ld+json" v-html="jsonLdString" />
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="sticky top-0 z-10 border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <slot name="header-left">
                            <Link href="/" class="flex h-8 w-8 items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30">
                                <img src="/images/uptime-kita.jpg" alt="Uptime Kita" class="h-6 w-6 rounded object-cover sm:h-8 sm:w-8" />
                            </Link>
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-bold text-gray-900 dark:text-white sm:text-xl">{{ title }}</h1>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400 sm:text-sm">{{ description }}</p>
                            </div>
                        </slot>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <ServerStatsBadge v-if="showServerStats" class="hidden sm:block" />
                        <ShareDropdown :url="shareUrl" :text="shareText" />
                        <button @click="toggleTheme" class="cursor-pointer rounded-full bg-gray-100 p-2 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                            <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                        </button>
                        <Link href="/dashboard" class="rounded-full bg-gray-100 p-2 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600" aria-label="Dashboard"><Icon name="home" class="h-4 w-4 text-gray-600 dark:text-gray-300" /></Link>
                        <slot name="header-actions" />
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>

        <PublicFooter />
        <ToastContainer />
    </div>
</template>
