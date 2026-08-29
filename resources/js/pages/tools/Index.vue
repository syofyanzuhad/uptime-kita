<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Tool {
    slug: string;
    title: string;
    description: string;
    icon: string;
    color: string;
    badge: string;
    href: string;
}

interface Props {
    tools: Tool[];
    appUrl: string;
}

const props = defineProps<Props>();

const pageTitle = 'Free Developer & Uptime Tools - Uptime Kita';
const pageDescription =
    'Free, instant web diagnostic tools for developers and sysadmins. Check SSL certificates, DNS records, security headers, and generate live status badges.';
const shareUrl = `${props.appUrl}/tools`;
const shareText = 'Explore free developer diagnostic tools on Uptime Kita (SSL Checker, DNS Lookup, Security Headers, and Badges).';
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-6xl">
        <!-- Header Banner -->
        <div class="mb-8 text-center sm:mb-10">
            <div
                class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300"
            >
                <Icon name="zap" class="h-3.5 w-3.5 text-amber-500" />
                <span>100% Free & No Sign-up Required</span>
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-4xl dark:text-white">Web Diagnostic & Developer Tools</h1>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-500 sm:text-base dark:text-gray-400">
                Fast, real-time diagnostic utilities to check your infrastructure, SSL certificates, DNS propagation, and web security.
            </p>
        </div>

        <!-- Tools Grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:gap-6">
            <Link
                v-for="tool in tools"
                :key="tool.slug"
                :href="tool.href"
                class="group flex flex-col justify-between rounded-3xl border border-gray-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm transition-all hover:-translate-y-1 hover:border-blue-400 hover:shadow-lg dark:border-gray-800/80 dark:bg-gray-900/80 dark:hover:border-blue-600"
            >
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-md transition-transform group-hover:scale-110"
                            :class="tool.color"
                        >
                            <Icon :name="tool.icon" class="h-6 w-6" />
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ tool.badge }}
                        </span>
                    </div>

                    <h2 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                        {{ tool.title }}
                    </h2>
                    <p class="mt-1.5 text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-gray-400">
                        {{ tool.description }}
                    </p>
                </div>

                <div class="mt-6 flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400">
                    <span>Launch tool</span>
                    <Icon name="arrowRight" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </div>
            </Link>
        </div>

        <!-- CTA Callout -->
        <div
            class="mt-10 overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 p-6 text-center text-white shadow-lg sm:p-8"
        >
            <h3 class="text-lg font-bold sm:text-2xl">Need continuous automated monitoring?</h3>
            <p class="mx-auto mt-2 max-w-xl text-xs text-blue-100 sm:text-sm">
                Get alerted the second your website or API goes down. Track 24/7 uptime, SSL expiration, response times, and create public status
                pages.
            </p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                <Link
                    href="/register"
                    class="rounded-xl bg-white px-5 py-2.5 text-xs font-bold text-blue-700 shadow-md transition-all hover:bg-blue-50 active:scale-95 sm:text-sm"
                >
                    Start Monitoring for Free
                </Link>
                <Link
                    href="/status/demo"
                    class="rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 text-xs font-bold text-white transition-all hover:bg-white/20 active:scale-95 sm:text-sm"
                >
                    View Status Page Demo
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
