<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import PublicFooter from '@/components/PublicFooter.vue';
import ServerStatsBadge from '@/components/ServerStatsBadge.vue';
import ShareDropdown from '@/components/ShareDropdown.vue';
import ToastContainer from '@/components/ToastContainer.vue';
import { useTheme } from '@/composables/useTheme';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        ogImage?: string;
        canonicalUrl?: string;
        shareUrl: string;
        shareText: string;
        showServerStats?: boolean;
        jsonLd?: Record<string, any>;
        containerClass?: string;
    }>(),
    {
        containerClass: 'max-w-7xl',
        showServerStats: true,
    },
);

const { isDark, toggleTheme } = useTheme();
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
function onOnline() {
    isOnline.value = true;
}
function onOffline() {
    isOnline.value = false;
}
onMounted(() => {
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
});
onUnmounted(() => {
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
});

const jsonLdString = computed(() => (props.jsonLd ? JSON.stringify(props.jsonLd) : ''));
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

    <div
        class="flex min-h-screen flex-col bg-gray-50/50 font-sans text-gray-900 antialiased selection:bg-blue-500 selection:text-white dark:bg-gray-950 dark:text-gray-100"
    >
        <header
            class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/80 backdrop-blur-md transition-colors dark:border-gray-800/80 dark:bg-gray-900/80"
        >
            <div class="mx-auto max-w-7xl px-4 py-3.5 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <slot name="header-left">
                            <Link href="/" class="group flex items-center gap-2.5 transition-transform active:scale-95">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-0.5 shadow-sm shadow-blue-500/20"
                                >
                                    <img src="/images/uptime-kita.jpg" alt="Uptime Kita" class="h-full w-full rounded-[10px] object-cover" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="truncate text-base font-bold tracking-tight text-gray-900 group-hover:text-blue-600 sm:text-lg dark:text-white dark:group-hover:text-blue-400"
                                        >
                                            {{ title }}
                                        </span>
                                    </div>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ description }}</p>
                                </div>
                            </Link>
                        </slot>
                    </div>

                    <div class="flex flex-shrink-0 items-center gap-1.5 sm:gap-2">
                        <slot name="header-nav" />

                        <ServerStatsBadge v-if="showServerStats" class="hidden md:block" />

                        <ShareDropdown :url="shareUrl" :text="shareText" />

                        <button
                            @click="toggleTheme"
                            class="cursor-pointer rounded-xl border border-gray-200/80 bg-white/80 p-2 text-gray-600 transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        >
                            <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 rotate-0 transition-transform duration-300 dark:-rotate-12" />
                        </button>

                        <Link
                            href="/tools"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/80 px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                            <Icon name="zap" class="h-3.5 w-3.5 text-amber-500" />
                            <span class="hidden sm:inline">Tools</span>
                        </Link>

                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/80 px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white"
                        >
                            <Icon name="home" class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Dashboard</span>
                        </Link>

                        <slot name="header-actions" />
                    </div>
                </div>
            </div>
        </header>

        <main :class="['mx-auto w-full flex-1 px-4 py-6 sm:px-6 lg:px-8', props.containerClass]">
            <slot />
        </main>

        <PublicFooter />
        <ToastContainer />
    </div>
</template>
